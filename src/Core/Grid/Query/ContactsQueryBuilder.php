<?php
/**
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Core\Grid\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

/**
 * Class ContactsQueryBuilder is responsible for building queries for Contacts grid data.
 */
final class ContactsQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * @var DoctrineSearchCriteriaApplicatorInterface
     */
    private $searchCriteriaApplicator;

    /**
     * @var int
     */
    private $languageId;

    /**
     * @var array
     */
    private $contextShopsIds;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     * @param DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator
     * @param int $languageId
     * @param array $contextShopsIds
     */
    public function __construct(
        Connection $connection,
        $dbPrefix,
        DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator,
        $languageId,
        array $contextShopsIds
    ) {
        parent::__construct($connection, $dbPrefix);

        $this->searchCriteriaApplicator = $searchCriteriaApplicator;
        $this->languageId = $languageId;
        $this->contextShopsIds = $contextShopsIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());
        $qb->select('c.id_contact, c.email, cl.name, cl.description');

        $this->searchCriteriaApplicator
            ->applySorting($searchCriteria, $qb)
            ->applyPagination($searchCriteria, $qb)
        ;

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters())
            ->select('SQL_CALC_FOUND_ROWS')
            ->select('FOUND_ROWS()')
        ;

        return $qb;
    }

    /**
     * Get generic query builder.
     *
     * @param array $filters
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder(array $filters)
    {
        $qb = $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix . 'contact', 'c')
            ->innerJoin('c', $this->dbPrefix . 'contact_lang', 'cl', 'c.id_contact = cl.id_contact')
            ->innerJoin('c', $this->dbPrefix . 'contact_shop', 'cs', 'c.id_contact = cs.id_contact')
            ->andWhere('cl.`id_lang`= :language')
            ->andWhere('cs.`id_shop` IN (:shops)')
            ->setParameter('language', $this->languageId)
            ->setParameter('shops', $this->contextShopsIds, Connection::PARAM_INT_ARRAY)
            ->groupBy('c.id_contact')
        ;

        foreach ($filters as $name => $value) {
            if ('id_contact' === $name) {
                $qb->andWhere('c.`id_contact` = :' . $name);
                $qb->setParameter($name, $value);

                continue;
            }

            $qb->andWhere("$name LIKE :$name");
            $qb->setParameter($name, '%' . $value . '%');
        }

        return $qb;
    }
}
