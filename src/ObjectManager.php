<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Proxy;
use PmsNz\ObjectTranslationBundle\Mapping\Translatable;
use PmsNz\ObjectTranslationBundle\Mapping\TranslatableProperty;

class ObjectManager
{
    private array $objectIds = [];
    private array $objectTranslatableTypes = [];
    private array $objectTranslatableProperties = [];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    public function getIdFor(object $object): mixed
    {
        return $this->objectIds[spl_object_id($object)] ??= (function (object $object) {
            $om = $this->doctrine->getManagerForClass($object::class);

            if (!$om) {
                throw new \LogicException(sprintf('No object manager found for class "%s"', $object::class));
            }

            $id = $om->getClassMetadata($object::class)->getIdentifierValues($object);

            if (count($id) > 1) {
                throw new \LogicException(sprintf('Class "%s" must have a single identifier to be translatable.', $object::class));
            }

            return reset($id);
        })($object);
    }

    public function getTranslatableTypeFor(object $object): ?string
    {
        return $this->objectTranslatableTypes[$object::class] ??= (function (object $object) {
            $class = new \ReflectionClass($object);

            if ($class->implementsInterface(Proxy::class)) {
                $class = $class->getParentClass();
            }

            return ($class->getAttributes(Translatable::class)[0] ?? null)?->newInstance()->name ?? null;
        })($object);
    }

    public function getTranslatablePropertiesFor(object $object): array
    {
        return $this->objectTranslatableProperties[$object::class] ??= (function (object $object) {
            $class = new \ReflectionClass($object);

            if (!$class->getAttributes(Translatable::class)) {
                return [];
            }

            $translatableProperties = [];
            foreach ($class->getProperties() as $property) {
                if ($property->getAttributes(TranslatableProperty::class)) {
                    if (!str_contains((string) $property->getType(), 'string')) {
                        throw new \LogicException(sprintf('The attribute #[TranslatableProperty] on property "%s" in class "%s" can only be applied to string properties.You are applying it to a "%s" property.', $property->getName(), $class->getName(), $property->getType()));
                    }
                    $translatableProperties[] = $property->getName();
                }
            }

            return $translatableProperties;
        })($object);
    }

    public function allTranslatableObjects(): iterable
    {
        foreach ($this->doctrine->getManagers() as $om) {
            foreach ($om->getMetadataFactory()->getAllMetadata() as $metadatum) {
                $class = $metadatum->getName();

                if (!(new \ReflectionClass($class))->getAttributes(Translatable::class)) {
                    continue;
                }

                yield from $this->doctrine->getRepository($class)->findAll();
            }
        }
    }

    public function translatableValuesFor(object $object): iterable
    {
        $class = new \ReflectionClass($object);

        foreach ($class->getProperties() as $property) {
            if (!$property->getAttributes(TranslatableProperty::class)) {
                continue;
            }

            yield $property->getName() => $property->getValue($object);
        }
    }
}
