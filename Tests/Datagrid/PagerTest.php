<?php

namespace Spyrit\Bundle\DoctrineDatagridBundle\Tests\Datagrid;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Spyrit\Bundle\DoctrineDatagridBundle\Datagrid\DoctrineDatagrid;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

#[AllowMockObjectsWithoutExpectations]
class PagerTest extends TestCase
{
    private function createDatagrid(string $name = 'test_datagrid', array $params = []): DoctrineDatagrid
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $requestStack = new RequestStack();
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        $requestStack->push($request);
        $formFactory = $this->createMock(FormFactory::class);
        $router = $this->createMock(RouterInterface::class);

        return new DoctrineDatagrid($doctrine, $requestStack, $formFactory, $router, $name, $params);
    }

    public function testDefaultCurrentPage(): void
    {
        $datagrid = $this->createDatagrid();
        $this->assertEquals(1, $datagrid->getCurrentPage());
    }

    public function testSetCurrentPage(): void
    {
        $datagrid = $this->createDatagrid();
        $datagrid->setCurrentPage(4);
        $this->assertEquals(4, $datagrid->getCurrentPage());
    }

    public function testDefaultMaxPerPage(): void
    {
        $datagrid = $this->createDatagrid();
        $this->assertEquals(30, $datagrid->getDefaultMaxPerPage());

        $datagrid->setDefaultMaxPerPage(50);
        $this->assertEquals(50, $datagrid->getDefaultMaxPerPage());
    }

    public function testAvailableMaxPerPage(): void
    {
        $datagrid = $this->createDatagrid();
        $this->assertEquals([15, 30, 50], $datagrid->getAvailableMaxPerPage());
    }

    public function testGetAndSetMaxPerPage(): void
    {
        $datagrid = $this->createDatagrid();
        $this->assertEquals(30, $datagrid->getMaxPerPage());

        $datagrid->setMaxPerPage(15);
        $this->assertEquals(15, $datagrid->getMaxPerPage());
    }

    public function testGetPaginationPath(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $requestStack = new RequestStack();
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        $requestStack->push($request);
        $formFactory = $this->createMock(FormFactory::class);
        $router = $this->createMock(RouterInterface::class);

        $router->expects($this->once())
            ->method('generate')
            ->with(
                'app_user_index',
                [
                    DoctrineDatagrid::ACTION => DoctrineDatagrid::ACTION_PAGE,
                    DoctrineDatagrid::ACTION_DATAGRID => 'user_grid',
                    DoctrineDatagrid::PARAM1 => 2,
                    'foo' => 'bar',
                ]
            )
            ->willReturn('/user?action=page&datagrid=user_grid&param1=2&foo=bar');

        $datagrid = new DoctrineDatagrid($doctrine, $requestStack, $formFactory, $router, 'user_grid');
        $path = $datagrid->getPaginationPath('app_user_index', 2, ['foo' => 'bar']);

        $this->assertEquals('/user?action=page&datagrid=user_grid&param1=2&foo=bar', $path);
    }

    public function testGetMaxPerPagePath(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $requestStack = new RequestStack();
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        $requestStack->push($request);
        $formFactory = $this->createMock(FormFactory::class);
        $router = $this->createMock(RouterInterface::class);

        $router->expects($this->once())
            ->method('generate')
            ->with(
                'app_user_index',
                [
                    DoctrineDatagrid::ACTION => DoctrineDatagrid::ACTION_LIMIT,
                    DoctrineDatagrid::ACTION_DATAGRID => 'user_grid',
                    DoctrineDatagrid::PARAM1 => 50,
                ]
            )
            ->willReturn('/user?action=limit&datagrid=user_grid&param1=50');

        $datagrid = new DoctrineDatagrid($doctrine, $requestStack, $formFactory, $router, 'user_grid');
        $path = $datagrid->getMaxPerPagePath('app_user_index', 50);

        $this->assertEquals('/user?action=limit&datagrid=user_grid&param1=50', $path);
    }
}
