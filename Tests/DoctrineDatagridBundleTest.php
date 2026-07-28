<?php

namespace Spyrit\Bundle\DoctrineDatagridBundle\Tests;

use PHPUnit\Framework\TestCase;
use Spyrit\Bundle\DoctrineDatagridBundle\DoctrineDatagridBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DoctrineDatagridBundleTest extends TestCase
{
    public function testBundle(): void
    {
        $bundle = new DoctrineDatagridBundle();
        $this->assertInstanceOf(Bundle::class, $bundle);
    }
}
