<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformAutomatedTranslationBundle\Handler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use EzSystems\EzPlatformAutomatedTranslationBundle\Entity\AutoTranslationActions;
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
        } else {
            $selectQuery->orderBy('created_at', 'DESC');
        }
        // Check read access to whole source subtree
        $permissionCriterion = $this->permissionCriterionResolver->getPermissionsCriterion();
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

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function getFirstPendingAction()
    {
        $queryBuilder = $this->getAllQuery(['field' => 'created_at', 'direction' => 1]);
        $queryBuilder
            ->andWhere($queryBuilder->expr()->in('at.status', ':action_status'))
            ->setParameter(
                ':action_status',
                [AutoTranslationActions::STATUS_PENDING, AutoTranslationActions::STATUS_IN_PROGRESS],
                Connection::PARAM_STR_ARRAY
            );
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->execute()->fetchAllAssociative()[0] ?? null;
    }

    public function countAll(QueryBuilder $queryBuilder): int
    {
        $queryBuilder->select('COUNT(at.id)');
        $queryBuilder->orderBy('at.id');
        $queryBuilder->setMaxResults(1);

        return (int) $queryBuilder->execute()->fetchOne();
    }

    public function getContentsWithRelationsInSubtree(string $locationPath, int $offset = 0 , int $limit = 10): array
    {
        $sql = $this->getSqlContentsWithRelationsInSubtree();
        $query = $this->connection->prepare(
            "SELECT * FROM ($sql) x LIMIT $offset,$limit");
        $query->bindValue( ':status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER);
        $query->bindValue(':path', $locationPath . '%', ParameterType::STRING);
        $result = $query->executeQuery();

        return $result->fetchAllAssociative();
    }
    public function countContentWithRelationsInSubtree(string $locationPath): int
    {
        $sql = $this->getSqlContentsWithRelationsInSubtree();
        $query = $this->connection->prepare(
            "SELECT COUNT(*) FROM ($sql) x");
        $query->bindValue( ':status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER);
        $query->bindValue(':path', $locationPath . '%', ParameterType::STRING);
        $result = $query->executeQuery();

        return $result->fetchOne();
    }
    public function getSqlContentsWithRelationsInSubtree(): string
    {
        $query =  $this->connection->createQueryBuilder()
            ->from(ContentGateway::CONTENT_ITEM_TABLE, 'c')
            ->innerJoin('c', LocationGateway::CONTENT_TREE_TABLE, 't', 't.contentobject_id = c.id')
            ->where('c.status = :status')
            ->andWhere('t.path_string LIKE :path');

        $contentsSqlQuery = $query
            ->select('DISTINCT c.id as contentId')
            ->getSQL();

        $relationContentsSqlQuery = $query
            ->select('DISTINCT c_rel.to_contentobject_id as contentId')
            ->innerJoin('c', ContentGateway::CONTENT_RELATION_TABLE, 'c_rel', 'c_rel.from_contentobject_id = c.id AND c_rel.from_contentobject_version = c.current_version')
            ->getSQL();

        return "$contentsSqlQuery UNION ALL $relationContentsSqlQuery";
    }

}

