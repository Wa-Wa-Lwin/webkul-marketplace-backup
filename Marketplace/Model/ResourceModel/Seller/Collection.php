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
namespace Webkul\Marketplace\Model\ResourceModel\Seller;

use \Webkul\Marketplace\Model\ResourceModel\AbstractCollection;
use Magento\Store\Model\Store;
use Magento\Framework\DB\Sql\Expression as SqlExpression;
use Magento\Framework\DB\Select as DBSelect;

/**
 * Webkul Marketplace ResourceModel Seller collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Webkul\Marketplace\Model\Seller::class,
            \Webkul\Marketplace\Model\ResourceModel\Seller::class
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
     * Retrieve all entity_id for collection
     *
     * @param int|string $limit
     * @param int|string $offset
     * @return array
     */
    public function getAllIds($limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns('entity_id');
        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();

        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
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

    /**
     * Retrieve all seller_id for collection
     *
     * @param int|string $limit
     * @param int|string $offset
     * @return array
     */
    public function getAllSellerIds($limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns('seller_id');
        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();

        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }

    /**
     * Join with Customer Grid Flat Table
     */
    public function joinCustomer()
    {
        $joinTable = $this->getTable('customer_grid_flat');
        $this->getSelect()->join($joinTable.' as cgf', 'main_table.seller_id = cgf.entity_id');
    }

    /**
     * Add Seller's Url Path in Seller Collection
     *
     * @return void
     */
    public function addAllSellerUrls()
    {
        $urlDetails = [
            "profile_url" => "marketplace/seller/profile/shop/",
            "collection_url" => "marketplace/seller/collection/shop/",
            "feedback_url" => "marketplace/seller/feedback/shop/",
            "location_url" => "marketplace/seller/location/shop/",
        ];

        $storeId = $this->getStoreId();
        $urlTable = $this->getTable('url_rewrite');
        $sellerTable = $this->getTable('marketplace_userdata');

        foreach ($urlDetails as $column => $url) {
            $condition = $urlTable.".target_path = CONCAT('".$url."', main_table.shop_url) and store_id = $storeId";
            $firstExpression = $this->getFieldQuery("request_path", $urlTable, $urlTable, $condition);
            $field = "CONCAT('".$url."', main_table.shop_url)";
            $condition = "seller.seller_id = main_table.seller_id and store_id = 0";
            $secondExpression = $this->getFieldQuery($field, $sellerTable, "seller", $condition);
            $field = "IFNULL(($firstExpression), ($secondExpression))";
            $this->getSelect()->columns([$column => new SqlExpression($field)]);
        }
    }
}
