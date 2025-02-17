<?php

declare(strict_types=1);

namespace JMS\SerializerBundle\Tests\DependencyInjection;

use JMS\SerializerBundle\DependencyInjection\Compiler\TwigExtensionPass;
use JMS\SerializerBundle\DependencyInjection\JMSSerializerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TwigExtensionPassTest extends TestCase
{
    /**
     * @return ContainerBuilder
     */
    private function getContainer(array $bundles = ['TwigBundle' => TwigBundle::class])
    {
        $loader = new JMSSerializerExtension();
        $container = new ContainerBuilder();

        $container->setParameter('kernel.debug', true);
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir() . '/serializer');
        $container->setParameter('kernel.bundles', $bundles);
        $container->setParameter('kernel.bundles_metadata', array_map(static function (string $class): array {
            return [
                'path' => (new $class())->getPath(),
                'namespace' => (new \ReflectionClass($class))->getNamespaceName(),
            ];
        }, $bundles));

        $loader->load([[]], $container);

        return $container;
    }

    public function testNotLoadedWhenNoBundle()
    {
        $container = $this->getContainer([]);

        $pass = new TwigExtensionPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('jms_serializer.twig_extension.serializer'));
        $this->assertFalse($container->hasDefinition('jms_serializer.twig_extension.serializer_runtime_helper'));
        $this->assertFalse($container->hasDefinition('jms_serializer.twig_extension.serializer_runtime_helper'));
    }

    public function testStandardExtension()
    {
        $container = $this->getContainer();
        $container->register('twig');

        $pass = new TwigExtensionPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('jms_serializer.twig_extension.serializer'));
        $this->assertFalse($container->hasDefinition('jms_serializer.twig_extension.serializer_runtime_helper'));
    }

    public function testLazyExtension()
    {
        $container = $this->getContainer();
        $container->register('twig');
        $container->register('twig.runtime_loader');

        $pass = new TwigExtensionPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('jms_serializer.twig_extension.serializer'));
        $this->assertTrue($container->hasDefinition('jms_serializer.twig_extension.serializer_runtime_helper'));
    }
}
