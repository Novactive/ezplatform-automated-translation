<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\PermissionCriterionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;


class AutoTranslationActionsHandler
{
    public const TABLE_NAME = 'auto_translation_actions';

    /** @var Connection */
    protected Connection $connection;

    /** @var CriteriaConverter */
    private CriteriaConverter $criteriaConverter;
    /** @var PermissionCriterionResolver */
    private PermissionCriterionResolver $permissionCriterionResolver;

    public function __construct(Connection $connection, CriteriaConverter $criteriaConverter, PermissionCriterionResolver $permissionCriterionResolver)
    {
        $this->connection = $connection;
        $this->criteriaConverter = $criteriaConverter;
        $this->permissionCriterionResolver = $permissionCriterionResolver;
    }

    public function getAllQuery(array $sort = []): QueryBuilder
    {
        $selectQuery = $this->getSelectQuery();

        $selectQuery
            ->addSelect('c.name as content_name')
            ->addSelect('us.name as user_name')
            ->addSelect('c.id as content_id')
            ->innerJoin(
                'at',
                LocationGateway::CONTENT_TREE_TABLE,
                't',
                't.node_id = at.subtree_id'
            )
            ->innerJoin(
                't',
                ContentGateway::CONTENT_ITEM_TABLE,
                'c',
                'c.id = t.contentobject_id'
            )
            ->innerJoin(
                'at',
                ContentGateway::CONTENT_ITEM_TABLE,
                'us',
                'us.id = at.user_id'
            )
            ->andWhere(
                $selectQuery->expr()->eq('c.status', ':content_status')
            )
            ->setParameter(':content_status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER)
        ;
        if (isset($sort['field'])) {
            $selectQuery->orderBy($sort['field'], ($sort['direction'] ?? 0) == 0 ? 'DESC' : 'ASC');
        }
        // Check read access to whole source subtree
        $permissionCriterion = $this->permissionCriterionResolver->getPermissionsCriterion(
            'content',
            'read'
        );
        if ($permissionCriterion !== true && $permissionCriterion !== false) {
            $query = new Query();
            $query->filter = new LogicalAnd(
                [
                    new Criterion\MatchAll(),
                    $permissionCriterion,
                ]
            );
            $selectQuery->andWhere($this->criteriaConverter->convertCriteria($selectQuery, $permissionCriterion, []));
        }

        return $selectQuery;
    }


    /**
     * @return QueryBuilder
     */
    private function getSelectQuery(): QueryBuilder
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select('at.*')
            ->from(self::TABLE_NAME, 'at')
        ;

        return $selectQuery;
    }

    public function countAll(QueryBuilder $queryBuilder): int
    {
        $queryBuilder->select('COUNT(at.id)');
        $queryBuilder->orderBy('at.id');
        $queryBuilder->limit = 0;

        return (int) $queryBuilder->execute()->fetchOne();
    }
}

