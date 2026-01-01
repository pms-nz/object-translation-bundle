<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Tests\Unit\Command;

use PmsNz\ObjectTranslationBundle\ObjectTranslator;
use PmsNz\ObjectTranslationBundle\Tests\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ObjectTranslationExportCommandTest extends DatabaseTestCase
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

    public function testObjectTranslationExportCommand()
    {
        $reflection = new \ReflectionClass($this->objectTranslator);
        $reflection->getProperty('fallbacks')
            ->setValue($this->objectTranslator, ['es' => ['fr']]);

        $application = new Application(self::$kernel);
        $command = $application->find('object-translation:export');
        $tester = new CommandTester($command);
        $fileName = 'translation-'.rand().'.csv';
        $tester->execute(['file' => $fileName]);
        $filesystem = new Filesystem();
        $this->assertTrue($filesystem->exists($fileName));
        $contents = $filesystem->readFile($fileName);
        $this->assertStringContainsString('type,id,field,en', $contents);
        $this->assertStringContainsString('entity2,1,property21,value21-1', $contents);

        $tester->execute(['file' => $fileName, '--locale' => 'fr']);
        $contents = $filesystem->readFile($fileName);
        $this->assertStringContainsString('type,id,field,en,fr', $contents);
        $this->assertStringContainsString('entity2,1,property21,value21-1,"value21-1 in fr"', $contents);
        $this->assertStringContainsString('entity2,1,property23,value23-1,', $contents);

        $tester->execute(['file' => $fileName, '--locale' => 'es']);
        $contents = $filesystem->readFile($fileName);
        $this->assertStringContainsString('type,id,field,en,fr,es', $contents);
        $this->assertStringContainsString('entity2,1,property21,value21-1,"value21-1 in fr",', $contents);
        $this->assertStringContainsString('entity2,1,property23,value23-1,,"value23-1 in es"', $contents);
        $this->assertStringContainsString('entity2,2,property21,value21-2,,', $contents);

        $filesystem->remove($fileName);
        $this->assertFalse($filesystem->exists($fileName));
    }
}
