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
use Magento\Customer\Model\CustomerFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use MIT\Customer\Helper\SMSSender;

/**
 * Class MassProcess used to multiple seller process.
 */
class MassProcess extends \Magento\Backend\App\Action
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
    protected $date;
    /**
     * Store manager.
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

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
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param CollectionFactory                           $collectionFactory
     * @param CustomerFactory                             $customerFactory
     * @param MpHelper                                    $mpHelper
     * @param MpEmailHelper                               $mpEmailHelper
     * @param SMSSender                                   $smsSender
     */
    public function __construct(
        Context $context,
        Filter $filter,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        CustomerFactory $customerFactory,
        MpHelper $mpHelper,
        MpEmailHelper $mpEmailHelper,
        SMSSender $smsSender
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->date = $date;
        $this->customerFactory = $customerFactory;
        $this->mpHelper = $mpHelper;
        $this->mpEmailHelper = $mpEmailHelper;
        parent::__construct($context);
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
        $sellerStatus = \Webkul\Marketplace\Model\Seller::STATUS_PROCESSING;
        $customerModel = $this->customerFactory->create();
        $helper = $this->mpHelper;
        $collection = $this->filter->getCollection(
            $this->collectionFactory->create()
        );
        $updated = 0;
        $notUpdated = 0;
        foreach ($collection as $item) {
            if ($item->getIsSeller() != 1) {
                $sellerId = $item->getSellerId();
                $item->setIsSeller($sellerStatus);
                $item->setUpdatedAt($this->date->gmtDate());
                $item->save();
                $adminStoremail = $helper->getAdminEmailId();
                $adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
                $adminUsername = $helper->getAdminName();

                $seller = $customerModel->load($item->getSellerId());
                $baseUrl = $this->storeManager->getStore()->getBaseUrl();
                $emailTempVariables['myvar1'] = $seller->getName();
                $emailTempVariables['myvar2'] = $baseUrl.'marketplace/account/login';
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

                    $message = ("The status of seller(s) has been changed to processing.");
                        
                    $this->smsSender->sendSMS(
                        $sellerEmail,
                        $message
                    );
                    $this->messageManager->addSuccess(__('The status of seller(s) has been changed to processing. This information is notified via phone number.'));
                }
                else 
                {
                    $this->mpEmailHelper->sendSellerProcessingMail(
                        $emailTempVariables,
                        $senderInfo,
                        $receiverInfo
                    );
                }

                $this->_eventManager->dispatch(
                    'mp_processing_seller',
                    ['seller' => $seller]
                );
                $updated++;
            } else {
                $notUpdated++;
            }
        }
        if ($updated) {
            $this->messageManager->addSuccess(
                __(
                    'A total of %1 record(s) status have been changed to processing.',
                    $updated
                )
            );
        }
        if ($notUpdated) {
            $this->messageManager->addNotice(
                __(
                    'A total of %1 record(s) status cannot be changed from approved to processing.',
                    $notUpdated
                )
            );
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(
            ResultFactory::TYPE_REDIRECT
        );

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check for is allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_Marketplace::seller');
    }
}
