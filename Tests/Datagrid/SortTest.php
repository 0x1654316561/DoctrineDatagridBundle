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
class SortTest extends TestCase
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

    public function testDefaultSortOrder(): void
    {
        $datagrid = $this->createDatagrid();
        $this->assertEquals('ASC', $datagrid->getDefaultSortOrder());
    }

    public function testSortAndGettersWithDefaultSort(): void
    {
        $datagrid = $this->createDatagrid();
        $datagrid->setDefaultSort(['created_at' => 'desc', 'name' => 'asc']);

        $this->assertTrue($datagrid->isSortedColumn('created_at'));
        $this->assertTrue($datagrid->isSortedColumn('name'));
        $this->assertFalse($datagrid->isSortedColumn('non_existent'));

        $this->assertEquals('desc', $datagrid->getSortedColumnOrder('created_at'));
        $this->assertEquals('asc', $datagrid->getSortedColumnOrder('name'));

        $this->assertEquals(0, $datagrid->getSortedColumnPriority('created_at'));
        $this->assertEquals(1, $datagrid->getSortedColumnPriority('name'));

        $this->assertEquals(2, $datagrid->getSortCount());
    }

    public function testResetSort(): void
    {
        $datagrid = $this->createDatagrid();
        $datagrid->setDefaultSort(['name' => 'asc']);

        $this->assertEquals(1, $datagrid->getSortCount());
        $datagrid->resetSort();
        $this->assertEquals(1, $datagrid->getSortCount()); // fallback to defaultSorts
    }

    public function testSetAllowedSorts(): void
    {
        $datagrid = $this->createDatagrid();
        $allowed = ['name', 'created_at'];
        $result = $datagrid->setAllowedSorts($allowed);
        $this->assertSame($datagrid, $result);
    }

    public function testGetSortPath(): void
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
                    DoctrineDatagrid::ACTION => DoctrineDatagrid::ACTION_SORT,
                    DoctrineDatagrid::ACTION_DATAGRID => 'user_grid',
                    DoctrineDatagrid::PARAM1 => 'username',
                    DoctrineDatagrid::PARAM2 => 'asc',
                ]
            )
            ->willReturn('/user?action=sort&datagrid=user_grid&param1=username&param2=asc');

        $datagrid = new DoctrineDatagrid($doctrine, $requestStack, $formFactory, $router, 'user_grid');
        $path = $datagrid->getSortPath('app_user_index', 'username', 'asc');

        $this->assertEquals('/user?action=sort&datagrid=user_grid&param1=username&param2=asc', $path);
    }

    public function testGetRemoveSortPath(): void
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
                    DoctrineDatagrid::ACTION => DoctrineDatagrid::ACTION_REMOVE_SORT,
                    DoctrineDatagrid::ACTION_DATAGRID => 'user_grid',
                    DoctrineDatagrid::PARAM1 => 'username',
                ]
            )
            ->willReturn('/user?action=remove-sort&datagrid=user_grid&param1=username');

        $datagrid = new DoctrineDatagrid($doctrine, $requestStack, $formFactory, $router, 'user_grid');
        $path = $datagrid->getRemoveSortPath('app_user_index', 'username');

        $this->assertEquals('/user?action=remove-sort&datagrid=user_grid&param1=username', $path);
    }
}
