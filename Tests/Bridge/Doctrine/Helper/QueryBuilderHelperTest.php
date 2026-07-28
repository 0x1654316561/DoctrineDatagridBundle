<?php

namespace Spyrit\Bundle\DoctrineDatagridBundle\Tests\Bridge\Doctrine\Helper;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Spyrit\Bundle\DoctrineDatagridBundle\Bridge\Doctrine\Helper\QueryBuilderHelper;

class QueryBuilderHelperTest extends TestCase
{
    public function testAddLeftJoinWhenAliasDoesNotExist(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('getDQLParts')
            ->willReturn(['join' => []]);

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('u.profile', 'p');

        $result = QueryBuilderHelper::addLeftJoin('u.profile', 'p', $qb);
        $this->assertSame($qb, $result);
    }

    public function testAddLeftJoinWhenAliasAlreadyExists(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $joinObject = new \stdClass();
        $joinObject->alias = 'p';

        $qb->expects($this->once())
            ->method('getDQLParts')
            ->willReturn([
                'join' => [
                    'root' => [$joinObject],
                ],
            ]);

        $qb->expects($this->never())
            ->method('leftJoin');

        $result = QueryBuilderHelper::addLeftJoin('u.profile', 'p', $qb);
        $this->assertSame($qb, $result);
    }
}
