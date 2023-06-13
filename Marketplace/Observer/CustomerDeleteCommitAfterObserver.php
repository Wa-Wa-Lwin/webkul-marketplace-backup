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
use Webkul\Marketplace\Model\ProductFactory as MpProductFactory;
use Webkul\Marketplace\Model\SellerFactory as MpSellerFactory;
use Webkul\Marketplace\Helper\Data as MarketplaceHelperData;

/**
 * Webkul Marketplace CustomerDeleteCommitAfterObserver Observer Model.
 */
class CustomerDeleteCommitAfterObserver implements ObserverInterface
{
    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var eventManager
     */
    protected $_eventManager;

    /**
     * @var MpProductFactory
     */
    protected $mpProductFactory;

    /**
     * @var MpSellerFactory
     */
    protected $mpSellerFactory;

    /**
     * @var MarketplaceHelperData
     */
    protected $_marketplaceHelperData;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface      $storeManager,
     * @param \Magento\Framework\Event\Manager                $eventManager
     * @param MpProductFactory                                $mpProductFactory
     * @param MpSellerFactory                                 $mpSellerFactory
     * @param MarketplaceHelperData                           $marketplaceHelperData
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Event\Manager $eventManager,
        MpProductFactory $mpProductFactory,
        MpSellerFactory $mpSellerFactory,
        MarketplaceHelperData $marketplaceHelperData,
        \Magento\Catalog\Model\Product\Action $productAction
    ) {
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->_eventManager = $eventManager;
        $this->mpProductFactory = $mpProductFactory;
        $this->mpSellerFactory = $mpSellerFactory;
        $this->_marketplaceHelperData = $marketplaceHelperData;
        $this->productAction = $productAction;
    }

    /**
     * customer Delete After event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $customer = $observer->getCustomer();
            $customerid = $customer->getId();
            $sellerId = $customerid;
            $allStores = $this->_storeManager->getStores();
            $sellerCollection = $this->mpSellerFactory->create()
                                ->getCollection()
                                ->addFieldToFilter(
                                    'seller_id',
                                    $customerid
                                );
            if ($sellerCollection->getSize()) {
                $sellerCollection->walk('delete');
            }
            if ($sellerId) {
                $status = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
                $productCollection = $this->mpProductFactory->create()
                                    ->getCollection()
                                    ->addFieldToFilter(
                                        'seller_id',
                                        $sellerId
                                    );
                $productIds = $productCollection->getAllIds();
                if (count($productIds)) {
                    $wholedata['product_mass_delete'] = $productIds;
                    $this->_eventManager->dispatch(
                        'mp_delete_product',
                        [$wholedata]
                    );
                    foreach ($allStores as $store) {
                        $this->productAction->updateAttributes($productIds, ['status' => $status], $store->getId());
                    }
                    $this->productAction->updateAttributes($productIds, ['status' => $status], 0);
                    $productCollection->walk('delete');
                }
            }
        } catch (\Exception $e) {
            $this->_marketplaceHelperData->logDataInLogger(
                "Observer_CustomerDeleteCommitAfterObserver execute : ".$e->getMessage()
            );
            $this->messageManager->addError($e->getMessage());
        }
    }
}
