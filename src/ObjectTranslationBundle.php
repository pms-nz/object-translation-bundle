<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use PmsNz\ObjectTranslationBundle\Model\AbstractTranslation;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ObjectTranslationBundle extends AbstractBundle
{
    protected string $extensionAlias = 'pms-nz_object_translation';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->stringNode('translation_class')
                    ->info('The class name of your Translation entity.')
                    ->example('App\Entity\Translation')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(fn ($v) => !is_a($v, AbstractTranslation::class, true))
                        ->thenInvalid('The translation_class %s must extend PmsNz\ObjectTranslationBundle\Model\AbstractTranslation.')
                    ->end()
                ->end()
                ->arrayNode('cache')
                    ->info('Cache settings for object translations.')
                    ->canBeDisabled()
                    ->children()
                        ->stringNode('pool')
                            ->info('The cache pool to use for storing object translations.')
                            ->defaultNull()
                        ->end()
                        ->integerNode('ttl')
                            ->info('The time-to-live for cached translations, in seconds. null for no expiration.')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('fallbacks')
                    ->info('Fallbacks for object translations.')
                    ->useAttributeAsKey('name')
                    ->variablePrototype()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver(
            [__DIR__.'/../config/doctrine/mapping' => 'PmsNz\ObjectTranslationBundle\Model'],
        ));
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $translatorDef = $builder->getDefinition('pms-nz.object_translation.object_translator');
        $translatorDef->setArgument(6, $config['translation_class']);
        if ($config['cache']['enabled']) {
            $translatorDef->setArgument(7, $config['cache']['pool']);
            $translatorDef->setArgument(8, $config['cache']['ttl']);
        } else {
            $translatorDef->setArgument(7, null);
            $translatorDef->setArgument(8, null);
        }
        $translatorDef->setArgument(9, $config['fallbacks']);
    }
}
