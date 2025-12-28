<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Tests\Unit;

use PmsNz\ObjectTranslationBundle\ObjectManager;
use PmsNz\ObjectTranslationBundle\Tests\DatabaseTestCase;
use PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity\Entity1;
use PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity\Entity2;

class ObjectManagerTest extends DatabaseTestCase
{
    private ObjectManager $objectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = self::getContainer()->get('pms-nz.object_translation.object_manager');
    }

    protected function tearDown(): void
    {
        unset($this->objectManager);
        parent::tearDown();
    }

    public function testInMemorySQLiteDatabase()
    {
        $entity1 = new Entity1();
        $entity1->property11 = 'value11';
        $this->entityManager->persist($entity1);
        $this->entityManager->flush();

        $retrieved = $this->entityManager->getRepository(Entity1::class)->findOneBy(['property11' => 'value11']);
        $this->assertSame('value11', $retrieved->property11);
        $this->assertSame(1, $retrieved->id);
    }

    public function testGetIdFor()
    {
        $entity1a = new Entity1();
        $entity1a->property11 = 'value11a';
        $this->entityManager->persist($entity1a);

        $entity1b = new Entity1();
        $entity1b->property11 = 'value11b';
        $this->entityManager->persist($entity1b);

        $this->entityManager->flush();

        $this->assertSame($entity1a->id, $this->objectManager->getIdFor($entity1a));
        $this->assertSame($entity1b->id, $this->objectManager->getIdFor($entity1b));
    }

    public function testIsTheCorrectTranslatableTypeReturned(): void
    {
        $this->assertSame(null, $this->objectManager->getTranslatableTypeFor(new Entity1()));
        $this->assertSame('entity2', $this->objectManager->getTranslatableTypeFor(new Entity2()));
    }

    public function testGetTranslatablePropertiesFor()
    {
        $this->assertSame([], $this->objectManager->getTranslatablePropertiesFor(new Entity1()));
        $this->assertSame(['property21', 'property23'], $this->objectManager->getTranslatablePropertiesFor(new Entity2()));
    }

    public function testAllTranslatableObjects()
    {
        $translatableObjects = [];
        foreach ($this->objectManager->allTranslatableObjects() as $object) {
            $translatableObjects[] = $object;
        }
        $this->assertCount(2, $translatableObjects);
    }

    public function testTranslatableValuesFor()
    {
        $entity = $this->entityManager->getRepository(Entity2::class)->findOneBy(['property21' => 'value21-1']);
        $translatableValues = [];
        foreach ($this->objectManager->translatableValuesFor($entity) as $field => $value) {
            $translatableValues[$field] = $value;
        }
        $this->assertCount(2, $translatableValues);
        $this->assertSame('value21-1', $translatableValues['property21']);
        $this->assertSame('value23-1', $translatableValues['property23']);
    }
}
