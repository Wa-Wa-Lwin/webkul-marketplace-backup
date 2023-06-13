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
namespace Webkul\Marketplace\Controller\Adminhtml\Seller;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use MIT\Customer\Helper\SMSSender;

/**
 * Class MassApprove used to multiple seller approved.
 */
class MassApprove extends \Magento\Backend\App\Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

     /**
      * Store manager
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
     * @var ProductAction
     */
    protected $productAction;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var MpEmailHelper
     */
    protected $mpEmailHelper;

    /**
     * @var \Webkul\Marketplace\Model\ProductFactory
     */
    protected $productModel;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerModel;

    /**
     * @var SMSSender
     */
    protected $smsSender;


    /**
     * @param Context                                     $context
     * @param Filter                                      $filter
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\Stdlib\DateTime          $dateTime
     * @param CollectionFactory                           $collectionFactory
     * @param Processor                                   $productPriceIndexerProcessor
     * @param ProductAction                               $productAction
     * @param MpHelper                                    $mpHelper
     * @param MpEmailHelper                               $mpEmailHelper
     * @param \Webkul\Marketplace\Model\ProductFactory    $productModel
     * @param \Magento\Customer\Model\CustomerFactory     $customerModel
     * @param SMSSender                                   $smsSender
     */
    public function __construct(
        Context $context,
        Filter $filter,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        CollectionFactory $collectionFactory,
        Processor $productPriceIndexerProcessor,
        ProductAction $productAction,
        MpHelper $mpHelper,
        MpEmailHelper $mpEmailHelper,
        \Webkul\Marketplace\Model\ProductFactory $productModel,
        \Magento\Customer\Model\CustomerFactory $customerModel,
        SMSSender $smsSender
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
        $this->_date = $date;
        $this->_storeManager = $storeManager;
        $this->_productRepository = $productRepository;
        $this->dateTime = $dateTime;
        $this->_productPriceIndexerProcessor = $productPriceIndexerProcessor;
        $this->productAction = $productAction;
        $this->mpHelper = $mpHelper;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->productModel = $productModel;
        $this->customerModel = $customerModel;
        $this->smsSender = $smsSender;
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $allStores = $this->_storeManager->getStores();
        $status = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
        $enableSellerStatus = \Webkul\Marketplace\Model\Seller::STATUS_ENABLED;
        $customerModel = $this->customerModel->create();
        $helper = $this->mpHelper;
        $sellerIds = [];
        $collection = $this->filter->getCollection(
            $this->collectionFactory->create()
        );
        foreach ($collection as $item) {
            $sellerIds[] = $item->getSellerId();
            $item->setIsSeller(1);
            $item->setUpdatedAt($this->_date->gmtDate());
            $item->save();
            $sellerProduct = $this->productModel->create()
            ->getCollection()
            ->addFieldToFilter('seller_id', $item->getSellerId());

            if ($sellerProduct->getSize()) {
                $productIds = $sellerProduct->getAllIds();
                $coditionArr = [];
                foreach ($productIds as $key => $id) {
                    $condition = "`mageproduct_id`=".$id;
                    array_push($coditionArr, $condition);
                }
                $coditionData = implode(' OR ', $coditionArr);

                $sellerProduct->setProductData(
                    $coditionData,
                    ['status' => $status]
                );
                foreach ($allStores as $eachStoreId => $storeId) {
                    $this->productAction->updateAttributes($productIds, ['status' => $status], $storeId);
                }

                $this->productAction->updateAttributes($productIds, ['status' => $status], 0);

                $this->_productPriceIndexerProcessor->reindexList($productIds);
            }

            $adminStoremail = $helper->getAdminEmailId();
            $adminEmail=$adminStoremail? $adminStoremail:$helper->getDefaultTransEmailId();
            $adminUsername = $helper->getAdminName();

            $seller = $customerModel->load($item->getSellerId());

            $emailTempVariables['myvar1'] = $seller->getName();
            $emailTempVariables['myvar2'] = $this->_storeManager->getStore()->getBaseUrl().'marketplace/account/login';
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
                $message = ("A customer account(s) has been approved as seller. ");
                $this->smsSender->sendSMS(
                    $sellerEmail,
                    $message
                );
                $this->messageManager->addSuccess(__('A customer account(s) has been approved as seller. This information is notified via phone number.'));  
            }
            else
            {
                $this->mpEmailHelper->sendSellerApproveMail(
                    $emailTempVariables,
                    $senderInfo,
                    $receiverInfo
                );
            }           

            $this->_eventManager->dispatch(
                'mp_approve_seller',
                ['seller'=>$seller]
            );
        }
        $model = $this->collectionFactory->create()
        ->addFieldToFilter('seller_id', ['in' => $sellerIds])
        ->addFieldToFilter('is_seller', ['neq'=>$enableSellerStatus]);
        foreach ($model as $value) {
            $value->setIsSeller($enableSellerStatus);
            $value->setUpdatedAt($this->_date->gmtDate());
            $value->save();
        }
        $this->messageManager->addSuccess(
            __(
                'A total of %1 record(s) have been approved as seller.',
                $collection->getSize()
            )
        );
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(
            ResultFactory::TYPE_REDIRECT
        );
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check for is allowed
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_Marketplace::seller');
    }
}
