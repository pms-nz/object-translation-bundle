<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use PmsNz\ObjectTranslationBundle\Model\AbstractTranslation;
use PmsNz\ObjectTranslationBundle\ObjectManager;
use PmsNz\ObjectTranslationBundle\ObjectTranslator;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class DoctrineListener
{
    private array $translatedProperties = [];
    private array $invalidations = [];

    public function __construct(
        private readonly ObjectTranslator $objectTranslator,
        private readonly ObjectManager $objectManager,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        $object = $args->getObject();

        $changeSets = $this->objectTranslator->getChangeSetsFor($object);
        if (empty($changeSets)) {
            return;
        }

        $objectId = $this->objectManager->getIdFor($object);
        $type = $this->objectManager->getTranslatableTypeFor($object);

        foreach ($changeSets as $property => $changeSet) {
            $this->logger->debug(sprintf('Translating property "%s" of type "%s" with id "%s" from "%s" to "%s".',
                $property,
                $type,
                $objectId,
                $changeSet[0],
                $changeSet[1],
            ));
            $this->propertyAccessor->setValue($object, $property, $changeSet[1]);
            // The following key only ensures there are no unnecessary repetitions
            $this->translatedProperties["$type.$objectId.$property"] = [
                'object' => $object,
                'field' => $property,
                'change' => $changeSet,
            ];
        }
    }

    public function preFlush(PreFlushEventArgs $args): void
    {
        foreach ($this->translatedProperties as $translatedProperty) {
            extract($translatedProperty);
            $this->logger->debug(sprintf(
                'Restoring property "%s" of entity "%s" with id "%s" to "%s".',
                $field, // @phpstan-ignore variable.undefined
                $object::class, // @phpstan-ignore variable.undefined
                $this->objectManager->getIdFor($object), // @phpstan-ignore variable.undefined
                $change[0], // @phpstan-ignore variable.undefined
            ));
            $this->propertyAccessor->setValue(
                $object, // @phpstan-ignore variable.undefined
                $field, // @phpstan-ignore variable.undefined
                $change[0], // @phpstan-ignore variable.undefined
            );
        }
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $ouw = $em->getUnitOfWork();

        $allChanges = array_merge(
            $ouw->getScheduledEntityUpdates(),
            $ouw->getScheduledEntityDeletions(),
            $ouw->getScheduledEntityInsertions()
        );

        foreach ($allChanges as $entity) {
            if ($entity instanceof AbstractTranslation) {
                $this->invalidations["object-translation-{$entity->objectType}"] = true;
            }
        }

        if ($this->invalidations) {
            $this->logger->debug(sprintf(
                'Preparing the tags "[%s]" to be invalidated.',
                implode(', ', array_keys($this->invalidations))
            ));
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (!$this->invalidations) {
            return;
        }

        $this->logger->debug(sprintf(
            'Invalidating the tags "[%s]".',
            implode(', ', array_keys($this->invalidations))
        ));

        $cache = $this->objectTranslator->getCache();
        if ($cache instanceof TagAwareCacheInterface) {
            $cache->invalidateTags(array_keys($this->invalidations));

            return;
        }

        $cache->clear(); // @phpstan-ignore method.notFound
    }
}
