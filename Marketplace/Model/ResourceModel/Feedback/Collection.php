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
namespace Webkul\Marketplace\Model\ResourceModel\Feedback;

use \Webkul\Marketplace\Model\ResourceModel\AbstractCollection;
use Magento\Framework\DB\Select as DBSelect;

/**
 * Webkul Marketplace ResourceModel Feedback collection
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
            \Webkul\Marketplace\Model\Feedback::class,
            \Webkul\Marketplace\Model\ResourceModel\Feedback::class
        );
        $this->_map['fields']['entity_id'] = 'main_table.entity_id';
    }

    /**
     * Add filter by store
     *
     * @param int|array|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if (!$this->getFlag('store_filter_added')) {
            $this->performAddStoreFilter($store, $withAdmin);
        }
        return $this;
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
        $select->reset(\Magento\Framework\DB\Select::ORDER);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);

        return $select;
    }

    /**
     * Retrieve review count for collection
     *
     * @param int|string $limit
     * @param int|string $offset
     * @return array
     */
    public function getAllReviewCount($type = "feed_value", $starType = null, $limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns('count('.$type.') AS totalReview');
        $idsSelect->group('seller_id');
        $idsSelect->limit($limit, $offset);
        if ($starType) {
            $idsSelect->Where($type.' = '.$starType);
        }
        $idsSelect->resetJoinLeft();

        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }

    public function addAllRatingColumns()
    {
        $this->getSelect()->reset(DBSelect::COLUMNS);
        $fields = [
            "count" => "count(seller_id)",
            "rating" => "sum(feed_price+feed_value+feed_quality)/(count(seller_id)*3)",
            "price_rating" => "sum(feed_price)/count(seller_id)",
            "value_rating" => "sum(feed_value)/count(seller_id)",
            "quality_rating" => "sum(feed_quality)/count(seller_id)",
            "price_star_5" => "sum(feed_price = 100)",
            "price_star_4" => "sum(feed_price = 80)",
            "price_star_3" => "sum(feed_price = 60)",
            "price_star_2" => "sum(feed_price = 40)",
            "price_star_1" => "sum(feed_price = 20)",
            "value_star_5" => "sum(feed_value = 100)",
            "value_star_4" => "sum(feed_value = 80)",
            "value_star_3" => "sum(feed_value = 60)",
            "value_star_2" => "sum(feed_value = 40)",
            "value_star_1" => "sum(feed_value = 20)",
            "quality_star_5" => "sum(feed_quality = 100)",
            "quality_star_4" => "sum(feed_quality = 80)",
            "quality_star_3" => "sum(feed_quality = 60)",
            "quality_star_2" => "sum(feed_quality = 40)",
            "quality_star_1" => "sum(feed_quality = 20)"
        ];

        foreach ($fields as $field => $expression) {
            $this->addFieldToCollection($field, $expression);
        }
    }
}
