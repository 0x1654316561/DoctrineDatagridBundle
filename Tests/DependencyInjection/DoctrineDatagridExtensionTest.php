<?php

namespace Spyrit\Bundle\DoctrineDatagridBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Spyrit\Bundle\DoctrineDatagridBundle\Datagrid\DoctrineDatagridFactory;
use Spyrit\Bundle\DoctrineDatagridBundle\DependencyInjection\DoctrineDatagridExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\Yaml\Yaml;

class DoctrineDatagridExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new DoctrineDatagridExtension();

        if (!class_exists(Yaml::class)) {
            $this->expectException(RuntimeException::class);
            $extension->load([], $container);
        } else {
            $extension->load([], $container);
            $this->assertTrue($container->hasDefinition(DoctrineDatagridFactory::class));
        }
    }
}
