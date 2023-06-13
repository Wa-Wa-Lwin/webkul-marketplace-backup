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

namespace Webkul\Marketplace\Block\Order;

/*
 * Webkul Marketplace Order Salesdetail Block
 */
use Magento\Sales\Model\OrderFactory;
use Webkul\Marketplace\Model\SaleslistFactory;
use Webkul\Marketplace\Model\OrdersFactory as MpOrderModel;
use Magento\Catalog\Model\ProductFactory;

class Salesdetail extends \Magento\Framework\View\Element\Template
{
    /**
     * @var OrderFactory
     */
    protected $order;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var SaleslistFactory
     */
    protected $saleslistModel;

    /**
     * @var MpOrderModel
     */
    protected $mpOrderModel;

    /**
     * @var ProductFactory
     */
    protected $productModel;

    /** @var \Webkul\Marketplace\Model\Saleslist */
    protected $salesLists;

    /**
     * @param Context                                   $context
     * @param array                                     $data
     * @param OrderFactory                              $order
     * @param \Magento\Customer\Model\Session           $customerSession
     * @param SaleslistFactory                          $saleslistModel
     * @param MpOrderModel                              $mpOrderModel
     * @param ProductFactory                            $productModel
     */
    public function __construct(
        OrderFactory $order,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Template\Context $context,
        SaleslistFactory $saleslistModel,
        MpOrderModel $mpOrderModel,
        ProductFactory $productModel,
        array $data = []
    ) {
        $this->order = $order;
        $this->customerSession = $customerSession;
        $this->saleslistModel = $saleslistModel;
        $this->mpOrderModel = $mpOrderModel;
        $this->productModel = $productModel;
        parent::__construct($context, $data);
    }

    /**
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Orders'));
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * @return bool|\Webkul\Marketplace\Model\ResourceModel\Saleslist\Collection
     */
    public function getCollection()
    {
        if (!($customerId = $this->getCustomerId())) {
            return false;
        }
        if (!$this->salesLists) {
            $ids = [];
            $orderids = [];

            $collectionOrders = $this->saleslistModel->create()
                                ->getCollection()
                                ->addFieldToFilter(
                                    'seller_id',
                                    ['eq' => $customerId]
                                )
                                ->addFieldToFilter(
                                    'mageproduct_id',
                                    ['eq' => $this->getRequest()->getParam('id')]
                                )
                                ->addFieldToFilter(
                                    'magequantity',
                                    ['neq' => 0]
                                )
                                ->addFieldToSelect('order_id')
                                ->distinct(true);
            $collection = $this->mpOrderModel->create()
                          ->getCollection()
                          ->addFieldToFilter(
                              'order_id',
                              ['in' => $collectionOrders->getData()]
                          );
                          $collection->setOrder(
                              'entity_id',
                              'desc'
                          );
            $this->salesLists = $collection;
        }

        return $this->salesLists;
    }

    public function getOrderById($orderId = '')
    {
        return $this->order->create()->load($orderId);
    }

    public function getProduct()
    {
        $productId = (int) $this->getRequest()->getParam('id');

        return $this->productModel->create()->load($productId);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getCollection()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'marketplace.salesdetail.pager'
            )->setCollection(
                $this->getCollection()
            );
            $this->setChild('pager', $pager);
            $this->getCollection()->load();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getCurrentUrl(); // Give the current url of recently viewed page
    }
}
