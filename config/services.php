<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use PmsNz\ObjectTranslationBundle\Command\ObjectTranslationExportCommand;
use PmsNz\ObjectTranslationBundle\EventListener\DoctrineListener;
use PmsNz\ObjectTranslationBundle\ObjectManager;
use PmsNz\ObjectTranslationBundle\ObjectTranslator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->set('pms-nz.object_translator.export_command', ObjectTranslationExportCommand::class)
            ->args([
                service('pms-nz.object_translation.object_manager'),
                service('pms-nz.object_translation.object_translator'),
                service('translation.locale_switcher'),
                param('kernel.default_locale'),
            ])
            ->tag('console.command')


        ->set('pms-nz.object_translation.object_translator', ObjectTranslator::class)
            ->public()
            ->args([
                service('translation.locale_switcher'),
                service('pms-nz.object_translation.object_manager'),
                service('property_accessor'),
                service('doctrine.orm.default_entity_manager'),
                service('logger'),
                param('kernel.default_locale'),
                abstract_arg('translation class'),
            ])

            ->alias(ObjectTranslator::class, 'pms-nz.object_translation.object_translator')

        ->set('pms-nz.object_translation.object_manager', ObjectManager::class)
            ->public()
            ->args([
                service('doctrine'),
            ])

        ->set('pms-nz.object_translation.doctrine_listener', DoctrineListener::class)
            ->args([
                service('pms-nz.object_translation.object_translator'),
                service('pms-nz.object_translation.object_manager'),
                service('property_accessor'),
                service('logger'),
            ])
            ->tag('doctrine.event_listener', [
                'event' => 'postLoad',
            ])
            ->tag('doctrine.event_listener', [
                'event' => 'preFlush',
            ])
            ->tag('doctrine.event_listener', [
                'event' => 'onFlush',
            ])
            ->tag('doctrine.event_listener', [
                'event' => 'postFlush',
            ])

            ->alias(DoctrineListener::class, 'pms-nz.object_translation.doctrine_listener')
    ;
};
