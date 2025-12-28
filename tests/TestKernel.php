<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use PmsNz\ObjectTranslationBundle\ObjectTranslationBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new ObjectTranslationBundle();
        yield new ZenstruckFoundryBundle();
    }

    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder)
    {
        $container->extension('framework', [
            'test' => true,
        ]);

        $container->extension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_sqlite',
                'url' => 'sqlite:///:memory:',
                'charset' => 'utf8',
            ],
            'orm' => [
                'mappings' => [
                    'PmsNzTestEntity' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => '%kernel.project_dir%/tests/Fixture/Entity',
                        'prefix' => 'PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity',
                    ],
                ],
            ],
        ]);

        $container->extension('pms-nz_object_translation', [
            'translation_class' => 'PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity\Translation',
            'cache' => null,
            'fallbacks' => [],
        ]);
    }
}
