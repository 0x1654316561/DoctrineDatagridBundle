<?php

namespace Spyrit\Bundle\DoctrineDatagridBundle\Tests\Datagrid;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Spyrit\Bundle\DoctrineDatagridBundle\Datagrid\DoctrineDatagrid;
use Spyrit\Bundle\DoctrineDatagridBundle\Datagrid\FilterObject;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;

#[AllowMockObjectsWithoutExpectations]
class FilterTest extends TestCase
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

    public function testFilterObjectAddAndGetters(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $factory = $this->createMock(FormFactory::class);
        $factory->expects($this->once())
            ->method('createNamedBuilder')
            ->willReturn($builder);

        $filterObject = new FilterObject($factory, 'user');
        $filterObject->add('name', TextType::class, ['required' => false], 'John');

        $this->assertEquals(TextType::class, $filterObject->getType('name'));
        $this->assertEquals(['required' => false], $filterObject->getOptions('name'));
        $this->assertFalse($filterObject->getOption('name', 'required'));
        $this->assertEquals('default_val', $filterObject->getOption('name', 'non_existent', 'default_val'));
        $this->assertNull($filterObject->getType('non_existent'));
        $this->assertNull($filterObject->getOptions('non_existent'));
        $this->assertSame($builder, $filterObject->getBuilder());
    }

    public function testFilterObjectSubmitAndGetData(): void
    {
        $form = $this->createMock(FormInterface::class);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->method('getForm')->willReturn($form);

        $factory = $this->createMock(FormFactory::class);
        $factory->method('createNamedBuilder')->willReturn($builder);

        $filterObject = new FilterObject($factory, 'user');

        $data = ['name' => 'Jane'];
        $form->expects($this->once())
            ->method('submit')
            ->with($data);

        $filterObject->submit($data);
        $this->assertEquals($data, $filterObject->getData());
        $this->assertSame($form, $filterObject->getForm());
    }

    public function testDefaultFilters(): void
    {
        $datagrid = $this->createDatagrid();
        $this->assertEquals([], $datagrid->getDefaultFilters());

        $defaults = ['status' => 'active'];
        $datagrid->setDefaultFilters($defaults);
        $this->assertEquals($defaults, $datagrid->getDefaultFilters());
    }

    public function testSetFilterValueAndReset(): void
    {
        $datagrid = $this->createDatagrid('test_grid');
        $this->assertFalse($datagrid->isFiltered());

        $datagrid->setFilterValue('status', 'pending');
        $this->assertTrue($datagrid->isFiltered());

        $datagrid->resetFilters();
        $this->assertFalse($datagrid->isFiltered());
    }

    public function testGetAllowedFilterMethods(): void
    {
        $datagridPost = $this->createDatagrid('grid_post');
        $this->assertEquals(['post'], $datagridPost->getAllowedFilterMethods());

        $datagridGet = $this->createDatagrid('grid_get', ['method' => 'get']);
        $this->assertEquals(['get'], $datagridGet->getAllowedFilterMethods());
    }
}
