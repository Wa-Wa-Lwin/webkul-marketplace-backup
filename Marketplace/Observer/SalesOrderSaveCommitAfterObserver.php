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

namespace Webkul\Marketplace\Observer;

use Magento\Framework\Event\ObserverInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\Marketplace\Model\OrdersFactory;
use Webkul\Marketplace\Model\SaleperpartnerFactory;
use Webkul\Marketplace\Model\SaleslistFactory;
use Webkul\Marketplace\Model\FeedbackcountFactory;

/**
 * Webkul Marketplace SalesOrderSaveCommitAfterObserver Observer Model.
 */
class SalesOrderSaveCommitAfterObserver implements ObserverInterface
{
    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var OrdersFactory
     */
    protected $ordersFactory;

    /**
     * @var SaleperpartnerFactory
     */
    protected $saleperpartnerFactory;

    /**
     * @var SaleslistFactory
     */
    protected $saleslistFactory;

    /**
     * @var FeedbackcountFactory
     */
    protected $feedbackcountFactory;

    /**
     * @param \Magento\Framework\Event\Manager            $eventManager
     * @param \Magento\Customer\Model\Session             $customerSession
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param MarketplaceHelper                           $marketplaceHelper
     * @param OrdersFactory                               $ordersFactory
     * @param SaleperpartnerFactory                       $saleperpartnerFactory
     * @param SaleslistFactory                            $saleslistFactory
     * @param FeedbackcountFactory                        $feedbackcountFactory
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        MarketplaceHelper $marketplaceHelper,
        OrdersFactory $ordersFactory,
        SaleperpartnerFactory $saleperpartnerFactory,
        SaleslistFactory $saleslistFactory,
        FeedbackcountFactory $feedbackcountFactory
    ) {
        $this->_eventManager = $eventManager;
        $this->_customerSession = $customerSession;
        $this->_date = $date;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->ordersFactory = $ordersFactory;
        $this->saleperpartnerFactory = $saleperpartnerFactory;
        $this->saleslistFactory = $saleslistFactory;
        $this->feedbackcountFactory = $feedbackcountFactory;
    }

    /**
     * Sales order save commmit after on order complete state event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $orderInstance Order */
        $order = $observer->getOrder();
        $lastOrderId = $observer->getOrder()->getId();
        $helper = $this->marketplaceHelper;
        if ($order->getState() == 'complete') {
            /*
            * Calculate cod and shipping charges if applied
            */
            $paymentCode = '';
            if ($order->getPayment()) {
                $paymentCode = $order->getPayment()->getMethod();
            }
            $ordercollection = $this->saleslistFactory->create()
            ->getCollection()
            ->addFieldToFilter('order_id', $lastOrderId)
            ->addFieldToFilter(
                'cpprostatus',
                \Webkul\Marketplace\Model\Saleslist::PAID_STATUS_PENDING
            );
            foreach ($ordercollection as $item) {
                $sellerId = $item->getSellerId();
                $taxAmount = $item['total_tax'];

                $taxToSeller = $helper->getConfigTaxManage();
                $marketplaceOrders = $this->ordersFactory->create()
                ->getCollection()
                ->addFieldToFilter('order_id', $lastOrderId)
                ->addFieldToFilter('seller_id', $item['seller_id']);
                foreach ($marketplaceOrders as $tracking) {
                    $taxToSeller = $tracking['tax_to_seller'];
                    if ($tracking->getOrderStatus() != 'closed' || $tracking->getOrderStatus() != 'canceled') {
                        $tracking->setOrderStatus('complete')->save();
                    }
                }
                if (!$taxToSeller) {
                    $taxAmount = 0;
                }

                $shippingCharges = 0;
                $codCharges = $item->getCodCharges();
                /*
                 * Calculate cod and shipping charges if applied
                 */
                if ($item->getIsShipping() == 1) {
                    $marketplaceOrders = $this->ordersFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('order_id', $lastOrderId)
                    ->addFieldToFilter('seller_id', $item['seller_id']);
                    foreach ($marketplaceOrders as $tracking) {
                        $shippingamount = $tracking->getShippingCharges();
                        $refundedShippingAmount = $tracking->getRefundedShippingCharges();
                        $shippingCharges = $shippingamount - $refundedShippingAmount;
                    }
                }
                $totalTaxShipping = $taxAmount + $codCharges + $shippingCharges;
                $actparterprocost = $item->getActualSellerAmount() + $totalTaxShipping;
                $totalamount = $item->getTotalAmount() + $totalTaxShipping;
                $codCharges = 0;

                $collectionverifyread = $this->saleperpartnerFactory->create()
                ->getCollection();
                $collectionverifyread->addFieldToFilter(
                    'seller_id',
                    $sellerId
                );
                if ($collectionverifyread->getSize() >= 1) {
                    foreach ($collectionverifyread as $verifyrow) {
                        $totalsale = $verifyrow->getTotalSale() + $totalamount;
                        $totalremain = $verifyrow->getAmountRemain() + $actparterprocost;
                        $verifyrow->setTotalSale($totalsale);
                        $verifyrow->setAmountRemain($totalremain);
                        $verifyrow->setCommissionRate($item->getCommissionRate());
                        $totalcommission = $verifyrow->getTotalCommission() +
                        ($totalamount - $actparterprocost);
                        $verifyrow->setTotalCommission($totalcommission);
                        $verifyrow->setUpdatedAt($this->_date->gmtDate());
                        $verifyrow->save();
                    }
                } else {
                    $collectionf = $this->saleperpartnerFactory->create();
                    $collectionf->setSellerId($sellerId);
                    $collectionf->setTotalSale($totalamount);
                    $collectionf->setAmountRemain($actparterprocost);
                    $collectionf->setCommissionRate($item->getCommissionRate());
                    $totalcommission = $totalamount - $actparterprocost;
                    $collectionf->setTotalCommission($totalcommission);
                    $collectionf->setCreatedAt($this->_date->gmtDate());
                    $collectionf->setUpdatedAt($this->_date->gmtDate());
                    $collectionf->save();
                }
                if ($sellerId) {
                    $ordercount = 0;
                    $feedbackcount = 0;
                    $feedcountid = 0;
                    $collectionfeed = $this->feedbackcountFactory->create()
                    ->getCollection()
                    ->addFieldToFilter(
                        'seller_id',
                        $sellerId
                    )->addFieldToFilter(
                        'buyer_id',
                        $order->getCustomerId()
                    );
                    foreach ($collectionfeed as $value) {
                        $feedcountid = $value->getEntityId();
                        $ordercount = $value->getOrderCount();
                        $feedbackcount = $value->getFeedbackCount();
                    }
                    $collectionfeed = $this->feedbackcountFactory->create()->load($feedcountid);
                    $collectionfeed->setBuyerId($order->getCustomerId());
                    $collectionfeed->setSellerId($sellerId);
                    $collectionfeed->setOrderCount($ordercount + 1);
                    $collectionfeed->setFeedbackCount($feedbackcount);
                    $collectionfeed->setCreatedAt($this->_date->gmtDate());
                    $collectionfeed->setUpdatedAt($this->_date->gmtDate());
                    $collectionfeed->save();
                }
                $item->setUpdatedAt($this->_date->gmtDate());
                $item->setCpprostatus(
                    \Webkul\Marketplace\Model\Saleslist::PAID_STATUS_COMPLETE
                )->save();
            }
        }
    }
}
