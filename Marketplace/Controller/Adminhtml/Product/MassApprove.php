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
namespace Webkul\Marketplace\Controller\Adminhtml\Product;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Framework\Controller\ResultFactory;
use MIT\Customer\Helper\SMSSender;

/**
 * Class MassApprove used to mass approved.
 */
class MassApprove extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;

    /**
     * @var \Magento\Ui\Component\MassAction\Filter
     */
    protected $filter;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Price\Processor
     */
    protected $productPriceIndexerProcessor;

    /**
     * @var \Webkul\Marketplace\Model\ProductFactory
     */
    protected $mpProductFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $productAction;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Eav\Processor
     */
    protected $eavProcessor;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $mpHelper;

    /**
     * @var \Webkul\Marketplace\Helper\Email
     */
    protected $mpEmailHelper;

    /**
     * @var \Webkul\Marketplace\Helper\Notification
     */
    protected $mpNotificationHelper;

    /**
     * @var SMSSender
     */
    protected $smsSender;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param Processor $productPriceIndexerProcessor
     * @param \Webkul\Marketplace\Model\ProductFactory $mpProductFactory
     * @param \Magento\Catalog\Model\Product\Action $productAction
     * @param \Magento\Catalog\Model\Indexer\Product\Eav\Processor $eavProcessor
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Webkul\Marketplace\Helper\Data $mpHelper
     * @param \Webkul\Marketplace\Helper\Email $mpEmailHelper
     * @param \Webkul\Marketplace\Helper\Notification $mpNotificationHelper
     * @param SMSSender $smsSender
     */
    public function __construct(
        Context $context,
        Filter $filter,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        Processor $productPriceIndexerProcessor,
        \Webkul\Marketplace\Model\ProductFactory $mpProductFactory,
        \Magento\Catalog\Model\Product\Action $productAction,
        \Magento\Catalog\Model\Indexer\Product\Eav\Processor $eavProcessor,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Webkul\Marketplace\Helper\Data $mpHelper,
        \Webkul\Marketplace\Helper\Email $mpEmailHelper,
        \Webkul\Marketplace\Helper\Notification $mpNotificationHelper,
        SMSSender $smsSender
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
        $this->productPriceIndexerProcessor = $productPriceIndexerProcessor;
        $this->mpProductFactory = $mpProductFactory;
        $this->productAction = $productAction;
        $this->eavProcessor = $eavProcessor;
        $this->customerFactory = $customerFactory;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->mpHelper = $mpHelper;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->mpNotificationHelper = $mpNotificationHelper;
        $this->smsSender = $smsSender;
    }

    /**
     * Execute action.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     *
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $helper = $this->mpHelper;
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $productIds = $collection->getAllIds();
        $allStores = $this->storeManager->getStores();
        $status = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
        $sellerProduct = $this->mpProductFactory->create()->getCollection();
        $coditionArr = [];
        foreach ($productIds as $key => $id) {
            $condition = "`mageproduct_id`=".$id;
            array_push($coditionArr, $condition);
        }

        $coditionData = implode(' OR ', $coditionArr);
        $details = ['status' => $status, 'seller_pending_notification' => 1, 'is_approved' => 1];
        $sellerProduct->setProductData($coditionData, $details);

        foreach ($allStores as $store) {
            $this->productAction->updateAttributes($productIds, ['status' => $status], $store->getId());
        }

        $this->productAction->updateAttributes($productIds, ['status' => $status], 0);
        $this->productPriceIndexerProcessor->reindexList($productIds);
        $this->eavProcessor->reindexList($productIds);

        $type = \Webkul\Marketplace\Model\Notification::TYPE_PRODUCT;
        foreach ($collection as $item) {
            $this->mpNotificationHelper->saveNotification($type, $item->getId(), $item->getMageproductId());
            $pro = $this->mpProductFactory->create()->load($item->getId());
            $productModel = $this->productFactory->create()->load($item->getMageproductId());
            $catarray = $productModel->getCategoryIds();
            $categoryname = '';
            foreach ($catarray as $keycat) {
                $categoriesy = $this->categoryFactory->create()->load($keycat);
                if ($categoryname == '') {
                    $categoryname = $categoriesy->getName();
                } else {
                    $categoryname = $categoryname.','.$categoriesy->getName();
                }
            }

            $adminStoreEmail = $helper->getAdminEmailId();
            $adminEmail = $adminStoreEmail ? $adminStoreEmail : $helper->getDefaultTransEmailId();
            $adminUsername = $helper->getAdminName();
            $seller = $this->customerFactory->create()->load($item->getSellerId());
            $emailTemplateVariables = [];
            $emailTemplateVariables['myvar1'] = $productModel->getName();
            $emailTemplateVariables['myvar2'] = $productModel->getDescription();
            $emailTemplateVariables['myvar3'] = $productModel->getPrice();
            $emailTemplateVariables['myvar4'] = $categoryname;
            $emailTemplateVariables['myvar5'] = $seller->getname();
            $emailTemplateVariables['myvar6'] = 'I would like to inform you that your product has been approved.';
            $senderInfo = ['name' => $adminUsername, 'email' => $adminEmail];
            $receiverInfo = ['name' => $seller->getName(), 'email' => $seller->getEmail()];

            $sellerEmail = $receiverInfo['email']; 
            if (preg_match('/^\+?\d+$/', $sellerEmail))
            {
                $message = ("A record(s) of product has been approved.");
                $this->smsSender->sendSMS(
                    $sellerEmail,
                    $message
                );
                $this->messageManager->addSuccess(__('A record(s) for product has been approved. This information is notified via phone number.'));
            }
            else 
            {
                $this->mpEmailHelper->sendProductStatusMail($emailTemplateVariables, $senderInfo, $receiverInfo);
            }
            $this->_eventManager->dispatch('mp_approve_product', ['product' => $pro, 'seller' => $seller]);
        }

        $helper->reIndexData();
        $this->messageManager->addSuccess(
            __(
                'A total of %1 record(s) have been approved.',
                $collection->getSize()
            )
        );
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check for is allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_Marketplace::product');
    }
}
