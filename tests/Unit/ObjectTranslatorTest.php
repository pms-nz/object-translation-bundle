<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Tests\Unit;

use PmsNz\ObjectTranslationBundle\ObjectTranslator;
use PmsNz\ObjectTranslationBundle\Tests\DatabaseTestCase;
use PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity\Entity2;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ObjectTranslatorTest extends DatabaseTestCase
{
    private ?ObjectTranslator $objectTranslator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectTranslator = self::$kernel->getContainer()->get('pms-nz.object_translation.object_translator');
    }

    protected function tearDown(): void
    {
        unset($this->objectTranslator);
        parent::tearDown();
    }

    public function testIsDefaultCacheTagAware()
    {
        $this->assertInstanceOf(TagAwareCacheInterface::class, $this->objectTranslator->getCache());
    }

    public function testGetTranslations()
    {
        $translations = $this->objectTranslator->getTranslations('entity2', 'fr');
        $this->assertCount(1, $translations);
    }

    public function testGetTranslation()
    {
        $om = self::$kernel->getContainer()->get('pms-nz.object_translation.object_manager');
        $entity = $this->entityManager->getRepository(Entity2::class)->findOneBy(['property21' => 'value21-1']);
        $this->assertInstanceOf(Entity2::class, $entity);
        $translation = $this->objectTranslator->getTranslation('entity2', $om->getIdFor($entity), 'fr', 'property21');
        $this->assertSame('value21-1 in fr', $translation->value);

        $entity = $this->entityManager->getRepository(Entity2::class)->findOneBy(['property21' => 'value21-2']);
        $translation = $this->objectTranslator->getTranslation('entity2', $om->getIdFor($entity), 'fr', 'property21');
        $this->assertSame(null, $translation);
    }

    public function testGetChangeSetsForEntity()
    {
        $reflection = new \ReflectionClass($this->objectTranslator);
        $localeAware = $reflection->getProperty('localeAware')
            ->getValue($this->objectTranslator);
        $localeAware->setLocale('fr');
        $entity = $this->entityManager->getRepository(Entity2::class)->findOneBy(['property21' => 'value21-1']);
        $changeSetExpected = [
            'property21' => ['value21-1', 'value21-1 in fr'],
        ];
        $this->assertSame($changeSetExpected, $this->objectTranslator->getChangeSetsFor($entity));

        $entity = $this->entityManager->getRepository(Entity2::class)->findOneBy(['property21' => 'value21-2']);
        $this->assertInstanceOf(Entity2::class, $entity);
        $changeSetExpected = [];
        $this->assertSame($changeSetExpected, $this->objectTranslator->getChangeSetsFor($entity));
    }

    public function testFallbacks()
    {
        $reflection = new \ReflectionClass($this->objectTranslator);
        $localeAware = $reflection->getProperty('localeAware')
            ->getValue($this->objectTranslator);
        $localeAware->setLocale('es');
        $reflection->getProperty('fallbacks')
            ->setValue($this->objectTranslator, ['es' => ['fr']]);
        $entity = $this->entityManager->getRepository(Entity2::class)->findOneBy(['property21' => 'value21-1']);
        $changeSets = $this->objectTranslator->getChangeSetsFor($entity);
        $this->assertSame('value21-1 in fr', $changeSets['property21'][1]);
        $this->assertSame('value23-1 in es', $changeSets['property23'][1]);
    }
}
