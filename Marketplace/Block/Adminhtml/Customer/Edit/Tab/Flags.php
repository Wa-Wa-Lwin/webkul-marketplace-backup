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
namespace Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab;

class Flags extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Webkul\Marketplace\Model\ResourceModel\SellerFlags\CollectionFactory $flagsFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->flagsFactory = $flagsFactory;
        parent::__construct($context, $backendHelper, $data);
    }
    /**
     * Initialize grid
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('flagsGrid');
        $this->setDefaultSort('created_at');
    }
    /**
     * Prepare collection
     *
     * @return \Magento\Review\Block\Adminhtml\Grid
     */
    protected function _prepareCollection()
    {
        /** @var $collection \Webkul\Marketplace\Model\ResourceModel\SellerFlags\Collection */
        $collection = $this->flagsFactory->create();
        if ($this->getCustomerId() || $this->getRequest()->getParam('customerId', false)) {
            $customerId = $this->getCustomerId();
            if (!$customerId) {
                $customerId = $this->getRequest()->getParam('customerId');
            }
            $this->setCustomerId($customerId);
            $collection->addFieldToFilter('seller_id', $customerId);
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );
        $this->addColumn(
            'created_at',
            [
                'header' => __('Created'),
                'type' => 'datetime',
                'index' => 'review_created_at',
                'header_css_class' => 'col-date col-date-min-width',
                'column_css_class' => 'col-date'
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
                'type' => 'text',
                'truncate' => 50,
                'escape' => true
            ]
        );
        $this->addColumn(
            'email',
            [
                'header' => __('Email'),
                'index' => 'email',
                'type' => 'text',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );
        $this->addColumn(
            'reason',
            [
                'header' => __('Flag Reason'),
                'index' => 'reason',
                'type' => 'text'
            ]
        );
        $this->addColumn(
            'created_at',
            [
                'header' => __('Flag Date'),
                'type' => 'datetime',
                'index' => 'created_at',
                'header_css_class' => 'col-date col-date-min-width',
                'column_css_class' => 'col-date'
            ]
        );
        return parent::_prepareColumns();
    }
    /**
     * Get row url
     *
     * @param \Magento\Review\Model\Review|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return false;
    }
    /**
     * Determine ajax url for grid refresh
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('marketplace/seller/flags', ['_current' => true]);
    }
}
