<?php

namespace Spyrit\Bundle\DoctrineDatagridBundle\Tests\Datagrid;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Spyrit\Bundle\DoctrineDatagridBundle\Datagrid\DoctrineDatagrid;
use Spyrit\Bundle\DoctrineDatagridBundle\Datagrid\DoctrineDatagridFactory;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

#[AllowMockObjectsWithoutExpectations]
class DoctrineDatagridFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $requestStack = new RequestStack();
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        $requestStack->push($request);
        $formFactory = $this->createMock(FormFactory::class);
        $router = $this->createMock(RouterInterface::class);

        $factory = new DoctrineDatagridFactory($doctrine, $requestStack, $formFactory, $router);
        $datagrid = $factory->create('custom_grid', ['foo' => 'bar']);

        $this->assertInstanceOf(DoctrineDatagrid::class, $datagrid);
        $this->assertEquals('datagrid.custom_grid', $datagrid->getSessionName());
    }
}
