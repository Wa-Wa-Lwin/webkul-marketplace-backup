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
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Webkul\Marketplace\Model\SellerFactory;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use MIT\Customer\Helper\SMSSender;

/**
 * Class massDisapprove
 */
class Deny extends \Magento\Backend\App\Action
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
     * @var SellerFactory
     */
    protected $sellerModel;

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
     * @param SellerFactory                               $sellerModel
     * @param ProductAction                               $productAction
     * @param MpHelper                                    $mpHelper
     * @param MpEmailHelper                               $mpEmailHelper
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
        SellerFactory $sellerModel,
        ProductAction $productAction,
        MpHelper $mpHelper,
        MpEmailHelper $mpEmailHelper,
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
        $this->sellerModel = $sellerModel;
        $this->productAction = $productAction;
        $this->mpHelper = $mpHelper;
        $this->mpEmailHelper = $mpEmailHelper;
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
        $data = $this->getRequest()->getParams();
        $allStores = $this->_storeManager->getStores();
        $status = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
        $sellerStatus = \Webkul\Marketplace\Model\Seller::STATUS_DISABLED;

        $collection = $this->sellerModel->create()
        ->getCollection()
        ->addFieldToFilter('seller_id', $data['seller_id']);
        foreach ($collection as $item) {
            $item->setIsSeller($sellerStatus);
            $item->save();
        }

        $sellerProduct = $this->collectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $data['seller_id']
        );

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
                $this->productAction->updateAttributes(
                    $productIds,
                    ['status' => $status],
                    $storeId
                );
            }

            $this->productAction->updateAttributes($productIds, ['status' => $status], 0);

            $this->_productPriceIndexerProcessor->reindexList($productIds);
        }
        $seller = $this->customerModel->create()->load($data['seller_id']);
        if (isset($data['notify_seller']) && $data['notify_seller'] == 1) {
            $helper = $this->mpHelper;

            $adminStoremail = $helper->getAdminEmailId();
            $adminEmail=$adminStoremail? $adminStoremail:$helper->getDefaultTransEmailId();
            $adminUsername = $helper->getAdminName();
            $emailTempVariables['myvar1'] = $seller->getName();
            $emailTempVariables['myvar2'] = $data['seller_deny_reason'];
            $senderInfo = [
                  'name' => $adminUsername,
                  'email' => $adminEmail,
              ];
            $receiverInfo = [
              'name' => $seller->getName(),
              'email' => $seller->getEmail(),
            ];

            $sellerEmail = $receiverInfo['email']; 
            $sellerName = $receiverInfo['name']; 
            
            if (preg_match('/^\+?\d+$/', $sellerEmail))
            {
                $message = ("Seller ".$sellerName." has been Denied.");                    
                $this->smsSender->sendSMS(
                    $sellerEmail,
                    $message
                );
                $this->messageManager->addSuccess(__('Seller has been Denied. This information is notified via phone number.'));
            }
            else
            {
                $this->mpEmailHelper->sendSellerDenyMail(
                    $emailTempVariables,
                    $senderInfo,
                    $receiverInfo
                );
                $this->messageManager->addSuccess(__('Seller has been Denied. This information is notified via mail.'));
            }
        }
        $this->_eventManager->dispatch(
            'mp_deny_seller',
            ['seller' => $seller]
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
