<?php

namespace Spyrit\Bundle\DoctrineDatagridBundle\Tests\Datagrid;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Spyrit\Bundle\DoctrineDatagridBundle\Datagrid\DoctrineDatagrid;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

#[AllowMockObjectsWithoutExpectations]
class DoctrineDatagridTest extends TestCase
{
    private function createDatagrid(string $name = 'test_datagrid', array $params = [], ?Request $request = null): DoctrineDatagrid
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $requestStack = new RequestStack();
        if ($request === null) {
            $request = new Request();
        }
        if (!$request->hasSession()) {
            $session = new Session(new MockArraySessionStorage());
            $request->setSession($session);
        }
        $requestStack->push($request);
        $formFactory = $this->createMock(FormFactory::class);
        $router = $this->createMock(RouterInterface::class);

        return new DoctrineDatagrid($doctrine, $requestStack, $formFactory, $router, $name, $params);
    }

    public function testConstructAndCreate(): void
    {
        $datagrid = $this->createDatagrid('initial_name', ['foo' => 'bar']);
        $this->assertEquals('datagrid.initial_name', $datagrid->getSessionName());

        $datagrid->create('new_name', ['baz' => 'qux']);
        $this->assertEquals('datagrid.new_name', $datagrid->getSessionName());
    }

    public function testSetManagerName(): void
    {
        $datagrid = $this->createDatagrid();
        $result = $datagrid->setManagerName('custom_manager');
        $this->assertSame($datagrid, $result);
    }

    public function testSelectAndGroupByAndId(): void
    {
        $datagrid = $this->createDatagrid();

        $datagrid->select('u.id, u.name');
        $datagrid->groupBy('u.category_id');
        $datagrid->id('u.id');

        $this->assertSame($datagrid, $datagrid->select(['u.id', 'u.name']));
        $this->assertSame($datagrid, $datagrid->groupBy(['u.category_id']));
    }

    public function testQueryCallback(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $qb = $this->createMock(QueryBuilder::class);
        $manager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $manager->method('createQueryBuilder')->willReturn($qb);
        $doctrine->method('getManager')->willReturn($manager);

        $requestStack = new RequestStack();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);

        $formFactory = $this->createMock(FormFactory::class);
        $router = $this->createMock(RouterInterface::class);

        $datagrid = new DoctrineDatagrid($doctrine, $requestStack, $formFactory, $router, 'grid');

        $callbackExecuted = false;
        $datagrid->query(function ($builder) use (&$callbackExecuted, $qb) {
            $callbackExecuted = true;
            return $qb;
        });

        $this->assertTrue($callbackExecuted);
        $this->assertSame($qb, $datagrid->getQueryBuilder());
    }

    public function testDefaultColumnsAndAppendableColumns(): void
    {
        $datagrid = $this->createDatagrid();

        $this->assertEquals([], $datagrid->getDefaultColumns());
        $this->assertEquals([], $datagrid->getNonRemovableColumns());
        $this->assertEquals([], $datagrid->getAppendableColumns());
        $this->assertEquals([], $datagrid->getColumns());
        $this->assertEquals([], $datagrid->getAvailableAppendableColumns());
    }

    public function testBatchOperationsWithNoCookie(): void
    {
        $datagrid = $this->createDatagrid('batch_grid');

        $this->assertEquals([], $datagrid->getBatchData());
        $this->assertFalse($datagrid->isBatchChecked(1));
        $this->assertFalse($datagrid->hasAllCheckedBatch());
        $this->assertFalse($datagrid->hasCheckedBatch());
    }

    public function testBatchOperationsWithIncludeCookie(): void
    {
        $request = new Request();
        $request->cookies->set('batch_grid_batch', json_encode([
            'type' => 'include',
            'checked' => [1, 2, 3],
        ]));
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $datagrid = $this->createDatagrid('batch_grid', [], $request);

        $this->assertTrue($datagrid->isBatchChecked(1));
        $this->assertTrue($datagrid->isBatchChecked(2));
        $this->assertFalse($datagrid->isBatchChecked(4));
        $this->assertTrue($datagrid->hasCheckedBatch());
    }

    public function testBatchOperationsWithExcludeCookie(): void
    {
        $request = new Request();
        $request->cookies->set('batch_grid_batch', json_encode([
            'type' => 'exclude',
            'checked' => [3],
        ]));
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $datagrid = $this->createDatagrid('batch_grid', [], $request);

        $this->assertTrue($datagrid->isBatchChecked(1));
        $this->assertFalse($datagrid->isBatchChecked(3));
    }

    public function testRoutingHelpers(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $requestStack = new RequestStack();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);
        $formFactory = $this->createMock(FormFactory::class);
        $router = $this->createMock(RouterInterface::class);

        $router->expects($this->exactly(3))
            ->method('generate')
            ->willReturnCallback(function ($route, $params) {
                return $route . '?' . http_build_query($params);
            });

        $datagrid = new DoctrineDatagrid($doctrine, $requestStack, $formFactory, $router, 'grid');

        $resetPath = $datagrid->getResetPath('route_reset');
        $this->assertStringContainsString('action=reset', $resetPath);

        $newColPath = $datagrid->getNewColumnPath('route_add', 'colA', 'colB');
        $this->assertStringContainsString('action=add-column', $newColPath);

        $remColPath = $datagrid->getRemoveColumnPath('route_rem', 'colA');
        $this->assertStringContainsString('action=remove-column', $remColPath);
    }

    public function testReset(): void
    {
        $datagrid = $this->createDatagrid();
        $datagrid->setFilterValue('name', 'test');
        $datagrid->setCurrentPage(5);

        $this->assertTrue($datagrid->isFiltered());
        $this->assertEquals(5, $datagrid->getCurrentPage());

        $datagrid->reset();

        $this->assertFalse($datagrid->isFiltered());
        $this->assertEquals(1, $datagrid->getCurrentPage());
    }
}
