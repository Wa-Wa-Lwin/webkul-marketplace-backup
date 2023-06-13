<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Model\ResourceModel\Orders;

use \Webkul\Marketplace\Model\ResourceModel\AbstractCollection;

/**
 * Webkul Marketplace ResourceModel Orders Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @var boolean
     */
    protected $hasJoinedSellerTable = false;

    /**
     * @var string
     */
    protected $sellerTableAlias = 'mu';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Webkul\Marketplace\Model\Orders::class,
            \Webkul\Marketplace\Model\ResourceModel\Orders::class
        );
        $this->_map['fields']['entity_id'] = 'main_table.entity_id';
        $this->_map['fields']['created_at'] = 'main_table.created_at';
    }

    /**
     * Retrieve clear select
     *
     * @return \Magento\Framework\DB\Select
     */
    protected function _getClearSelect()
    {
        return $this->_buildClearSelect();
    }

    /**
     * Retrieve all Orders ids for collection
     *
     * @param int|string $limit
     * @param int|string $offset
     * @return array
     */
    public function getAllIds($limit = null, $offset = null)
    {
        $collectionIdsSelect = $this->_getClearSelect();
        $collectionIdsSelect->columns('entity_id');
        $collectionIdsSelect->limit($limit, $offset);
        $collectionIdsSelect->resetJoinLeft();

        return $this->getConnection()->fetchCol($collectionIdsSelect, $this->_bindParams);
    }

    /**
     * Build clear select
     *
     * @param \Magento\Framework\DB\Select $select
     * @return \Magento\Framework\DB\Select
     */
    protected function _buildClearSelect($select = null)
    {
        if (null === $select) {
            $select = clone $this->getSelect();
        }
        $select->reset(
            \Magento\Framework\DB\Select::ORDER
        );
        $select->reset(
            \Magento\Framework\DB\Select::LIMIT_COUNT
        );
        $select->reset(
            \Magento\Framework\DB\Select::LIMIT_OFFSET
        );
        $select->reset(
            \Magento\Framework\DB\Select::COLUMNS
        );

        return $select;
    }

    public function joinSellerTable()
    {
        $this->hasJoinedSellerTable = true;
        $alias = $this->sellerTableAlias;
        $sellerTable = $this->getTable('marketplace_userdata');
        $this->getSelect()->join([$alias => $sellerTable], $alias.'.seller_id = main_table.seller_id', ['*']);
    }

    public function addSellerTableFilter($field, $filter)
    {
        if ($this->hasJoinedSellerTable) {
            $alias = $this->sellerTableAlias;
            $this->addFieldToFilter($alias.".".$field, $filter);
        }
    }

    public function addActiveSellerFilter()
    {
        if ($this->hasJoinedSellerTable) {
            $alias = $this->sellerTableAlias;
            $this->addFieldToFilter($alias.".is_seller", 1);
        }
    }

    /**
     * Join with Customer Grid Flat Table
     */
    public function joinCustomer()
    {
        $joinTable = $this->getTable('customer_grid_flat');
        $this->getSelect()->join($joinTable.' as cgf', 'main_table.seller_id = cgf.entity_id');
    }
}
