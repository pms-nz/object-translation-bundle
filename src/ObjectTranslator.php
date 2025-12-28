<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PmsNz\ObjectTranslationBundle\Model\AbstractTranslation;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class ObjectTranslator
{
    private EntityRepository $translationRepository;

    public function __construct(
        private readonly LocaleAwareInterface $localeAware,
        private readonly ObjectManager $objectManager,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private string $defaultLocale,
        private string $translationClass,
        private ?CacheInterface $cache = null,
        private ?int $cacheTtl = null,
        private ?array $fallbacks = null,
    ) {
        $this->translationRepository = $this->entityManager->getRepository($this->translationClass);
        $this->cache = $cache ?? new TagAwareAdapter(new ArrayAdapter());
        $this->logger->debug(sprintf('Cache is "%s".', get_class($this->cache)));
    }

    public function getChangeSetsFor(object $object): array
    {
        $locale = $this->localeAware->getLocale();

        if (
            $locale === $this->defaultLocale
            || $object instanceof AbstractTranslation
            || !($type = $this->objectManager->getTranslatableTypeFor($object))
        ) {
            return [];
        }

        $objectId = $this->objectManager->getIdFor($object);
        $changeSets = [];

        foreach ($this->objectManager->getTranslatablePropertiesFor($object) as $propertyName) {
            $fallbacks = $this->fallbacks[$locale] ?? [];
            $currentLocale = $locale;
            do {
                $translation = $this->getTranslation(
                    $type,
                    $objectId,
                    $currentLocale,
                    $propertyName,
                );
                if ($translation) {
                    $changeSets[$propertyName] = [
                        $this->propertyAccessor->getValue($object, $propertyName),
                        $translation->value,
                    ];
                    break;
                }
            } while ($currentLocale = array_shift($fallbacks));
        }

        return $changeSets;
    }

    public function getTranslation(string $type, int|string $objectId, string $locale, string $propertyName): ?AbstractTranslation
    {
        $translations = $this->getTranslations($type, $locale);

        if (!$translations) {
            return null;
        }

        return $translations["$objectId.$propertyName"] ?? null;
    }

    public function getTranslations(string $type, string $locale)
    {
        return $this->cache->get("$type.$locale", function (ItemInterface $item) use ($type, $locale) {
            if ($this->cache instanceof TagAwareCacheInterface) {
                $item->tag(['object-translation', "object-translation-$type"]);
            }

            if ($this->cacheTtl) {
                $item->expiresAfter($this->cacheTtl);
            }

            $data = $this->translationRepository->findBy([
                'objectType' => $type,
                'locale' => $locale,
            ]);

            $translations = [];
            foreach ($data as $translation) {
                $translations["{$translation->objectId}.{$translation->field}"] = $translation;
            }

            return $translations;
        });
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }
}
