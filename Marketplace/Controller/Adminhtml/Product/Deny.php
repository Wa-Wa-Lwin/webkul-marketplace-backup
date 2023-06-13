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

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Webkul\Marketplace\Model\ProductFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Helper\Notification as NotificationHelper;
use Magento\Catalog\Model\CategoryFactory;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use MIT\Customer\Helper\SMSSender;

/**
 * Class Deny used to deny the product.
 */
class Deny extends \Magento\Backend\App\Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;
    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Catalog\Model\Indexer\Product\Price\Processor
     */
    protected $_productPriceIndexerProcessor;

    /**
     * @var ProductFactory
     */
    protected $productModel;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $productAction;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var NotificationHelper
     */
    protected $notificationHelper;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var MpEmailHelper
     */
    protected $mpEmailHelper;

    /**
     * @var SMSSender
     */
    protected $smsSender;

    /**
     * @param Context                                     $context
     * @param Filter                                      $filter
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Stdlib\DateTime          $dateTime
     * @param Processor                                   $productPriceIndexerProcessor
     * @param ProductFactory                              $productModel
     * @param \Magento\Catalog\Model\Product\Action       $productAction
     * @param MpHelper                                    $mpHelper
     * @param NotificationHelper                          $notificationHelper
     * @param CategoryFactory                             $categoryFactory
     * @param MpEmailHelper                               $mpEmailHelper
     * @param SMSSender                                   $smsSender
     */
    public function __construct(
        Context $context,
        Filter $filter,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        Processor $productPriceIndexerProcessor,
        ProductFactory $productModel,
        \Magento\Catalog\Model\Product\Action $productAction,
        MpHelper $mpHelper,
        NotificationHelper $notificationHelper,
        CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Model\CustomerFactory $customerModel,
        MpEmailHelper $mpEmailHelper,
        SMSSender $smsSender
    ) {
        $this->filter = $filter;
        parent::__construct($context);
        $this->_date = $date;
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->dateTime = $dateTime;
        $this->_productPriceIndexerProcessor = $productPriceIndexerProcessor;
        $this->productModel = $productModel;
        $this->productAction = $productAction;
        $this->mpHelper = $mpHelper;
        $this->notificationHelper = $notificationHelper;
        $this->categoryFactory = $categoryFactory;
        $this->productFactory = $productFactory;
        $this->customerModel = $customerModel;
        $this->mpEmailHelper = $mpEmailHelper;
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
        $data = $this->getRequest()->getParams();
        $collection = $this->productModel->create()
        ->getCollection()
        ->addFieldToFilter('mageproduct_id', $data['mageproduct_id'])
        ->addFieldToFilter('seller_id', $data['seller_id']);
        if ($collection->getSize()) {
            $productIds = [$data['mageproduct_id']];
            $allStores = $this->_storeManager->getStores();
            $status = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
            $sellerProductStatus = \Webkul\Marketplace\Model\Product::STATUS_DENIED;

            $sellerProduct = $this->productModel->create()->getCollection();

            $coditionData = "`mageproduct_id`=".$data['mageproduct_id'];

            $sellerProduct->setProductData(
                $coditionData,
                ['status' => $sellerProductStatus, 'seller_pending_notification' => 1]
            );
            foreach ($allStores as $eachStoreId => $storeId) {
                $this->productAction->updateAttributes($productIds, ['status' => $status], $storeId);
            }

            $this->productAction->updateAttributes($productIds, ['status' => $status], 0);

            $this->_productPriceIndexerProcessor->reindexList($productIds);

            $catagoryModel = $this->categoryFactory->create();

            $helper = $this->mpHelper;

            $id = 0;

            foreach ($collection as $item) {
                $id = $item->getId();
                $this->notificationHelper->saveNotification(
                    \Webkul\Marketplace\Model\Notification::TYPE_PRODUCT,
                    $id,
                    $data['mageproduct_id']
                );
            }

            $model = $this->productFactory->create()->load($data['mageproduct_id']);

            $catarray = $model->getCategoryIds();
            $categoryname = '';
            foreach ($catarray as $keycat) {
                $categoriesy = $catagoryModel->load($keycat);
                if ($categoryname == '') {
                    $categoryname = $categoriesy->getName();
                } else {
                    $categoryname = $categoryname.','.$categoriesy->getName();
                }
            }
            $allStores = $this->_storeManager->getStores();

            $pro = $this->productModel->create()->load($id);
            $seller = $this->customerModel->create()->load($data['seller_id']);
            if (isset($data['notify_seller']) && $data['notify_seller'] == 1) {
                $helper = $this->mpHelper;
                $adminStoreEmail = $helper->getAdminEmailId();
                $adminEmail = $adminStoreEmail ? $adminStoreEmail : $helper->getDefaultTransEmailId();
                $adminUsername = $helper->getAdminName();
                $emailTempVariables['myvar1'] = $seller->getName();
                $emailTempVariables['myvar2'] = $data['product_deny_reason'];
                $emailTempVariables['myvar3'] = $model->getName();
                $emailTempVariables['myvar4'] = $categoryname;
                $emailTempVariables['myvar5'] = $model->getDescription();
                $emailTempVariables['myvar6'] = $model->getPrice();
                $senderInfo = [
                      'name' => $adminUsername,
                      'email' => $adminEmail,
                  ];
                $receiverInfo = [
                  'name' => $seller->getName(),
                  'email' => $seller->getEmail(),
                ];

                $sellerEmail = $receiverInfo['email']; 

                if (preg_match('/^\+?\d+$/', $sellerEmail))
                {

                    $message = ("Your product has been Denied.");

                    $this->smsSender->sendSMS(
                        $sellerEmail,
                        $message
                    );
                    $this->messageManager->addSuccess(__('Product has been Denied. This information is notified via phone number.'));
                }
                else 
                {
                    $this->mpEmailHelper->sendProductDenyMail(
                        $emailTempVariables,
                        $senderInfo,
                        $receiverInfo
                    );
                }
            }

            $this->_eventManager->dispatch(
                'mp_deny_product',
                ['product' => $pro, 'seller' => $seller]
            );

            $this->messageManager->addSuccess(__('Product has been Denied. aaa'));
        }

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
