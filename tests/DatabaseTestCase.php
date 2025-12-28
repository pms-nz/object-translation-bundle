<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PmsNz\ObjectTranslationBundle\Tests\Fixture\Database;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;

    protected function setUp(): void
    {
        // 1. Boot the minimal kernel to initialize the container
        self::bootKernel(['environment' => 'test', 'debug' => false]);
        $container = self::$kernel->getContainer();

        // 2. Get the EntityManager
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->requestStack = $container->get('request_stack');

        // 3. Build the schema (tables) in the in-memory database
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $tool = new SchemaTool($this->entityManager);
        $tool->createSchema($metadatas);
        Database::create($this->entityManager);
    }

    protected function tearDown(): void
    {
        // Clean up and shut down the kernel
        $this->entityManager->close();
        self::$kernel->shutdown();
        parent::tearDown();
    }
}
