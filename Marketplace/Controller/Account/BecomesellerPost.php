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

namespace Webkul\Marketplace\Controller\Account;

use Magento\Framework\App\Action\Action;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\App\RequestInterface;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Backend\Model\Url as BackendUrl;

/**
 * Webkul Marketplace Account BecomesellerPost Controller.
 */
class BecomesellerPost extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var  \Webkul\Marketplace\Model\SellerFactory
     */
    protected $_sellerFactory;

    /**
     * @var  \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory
     */
    protected $_sellerCollectionFactory;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $_helper;

    /**
     * @var MpEmailHelper
     */
    protected $mpEmailHelper;

    /**
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @var BackendUrl
     */
    protected $backendUrl;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Webkul\Marketplace\Model\SellerFactory $sellerFactory
     * @param \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerCollectionFactory
     * @param \Webkul\Marketplace\Helper\Data $helper
     * @param MpEmailHelper $mpEmailHelper
     * @param CustomerUrl $customerUrl
     * @param BackendUrl $backendUrl
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Webkul\Marketplace\Model\SellerFactory $sellerFactory,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerCollectionFactory,
        \Webkul\Marketplace\Helper\Data $helper,
        MpEmailHelper $mpEmailHelper,
        CustomerUrl $customerUrl,
        BackendUrl $backendUrl
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_date = $date;
        $this->_helper = $helper;
        $this->_sellerFactory = $sellerFactory;
        $this->_sellerCollectionFactory = $sellerCollectionFactory;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->customerUrl = $customerUrl;
        $this->backendUrl = $backendUrl;
        parent::__construct($context);
    }

    /**
     * Retrieve customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->customerUrl->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * Check for Existing Shop Url
     *
     * @return boolean
     */
    private function isExistingShopUrl()
    {
        $shopUrl = $this->getRequest()->getParam("profileurl");
        $collection = $this->_sellerCollectionFactory->create();
        $collection->addFieldToFilter('shop_url', $shopUrl);
        if ($collection->getSize()) {
            return true;
        }

        return false;
    }

    /**
     * Get Approval Status
     *
     * @return int
     */
    private function getStatus()
    {
        if ($this->_helper->getIsPartnerApproval()) {
            return 0;
        }

        return 1;
    }

    /**
     * Save Seller Data
     */
    private function saveSellerData()
    {
        try {
            $shopUrl = $this->getRequest()->getParam("profileurl");
            $sellerId = $this->_getSession()->getCustomerId();
            $status = $this->getStatus();
            $autoId = 0;
            $collection = $this->_sellerCollectionFactory->create();
            $collection->addFieldToFilter('seller_id', $sellerId);
            foreach ($collection as $value) {
                $autoId = $value->getId();
                break;
            }

            $seller = $this->_sellerFactory->create()->load($autoId);
            $seller->setData('is_seller', $status);
            $seller->setData('shop_url', $shopUrl);
            $seller->setData('seller_id', $sellerId);
            $seller->setCreatedAt($this->_date->gmtDate());
            $seller->setUpdatedAt($this->_date->gmtDate());
            $seller->setAdminNotification(1);
            $seller->save();
        } catch (\Exception $e) {
            $this->_helper->logDataInLogger(
                "controller_account_becomesellerPost saveSellerData : ".$e->getMessage()
            );
            $this->messageManager->addError($e->getMessage());
        }

        try {
            if ($status) {
                /* clear cache */
                $this->_helper->clearCache();
                $this->messageManager->addSuccess(
                    __('Congratulations! Your seller account is created.')
                );
            } else {
                $customer = $this->_helper->getCustomer();
                $adminStoremail = $this->_helper->getAdminEmailId();
                $adminEmail = $adminStoremail ? $adminStoremail : $this->_helper->getDefaultTransEmailId();
                $adminUsername = $this->_helper->getAdminName();
                $senderInfo = [
                    'name' => $customer->getFirstName().' '.$customer->getLastName(),
                    'email' => $customer->getEmail(),
                ];
                $receiverInfo = [
                    'name' => $adminUsername,
                    'email' => $adminEmail,
                ];

                $emailTemplateVariables['myvar1'] = $customer->getFirstname().' '.
                $customer->getMiddlename().' '.$customer->getLastname();
                $emailTemplateVariables['myvar2'] = $this->backendUrl->getUrl(
                    'customer/index/edit',
                    ['id' => $customer->getId()]
                );
                $emailTemplateVariables['myvar3'] = $this->_helper->getAdminName();
                $this->mpEmailHelper->sendNewSellerRequest(
                    $emailTemplateVariables,
                    $senderInfo,
                    $receiverInfo
                );
                $this->messageManager->addSuccess(
                    __('Your request to become seller is successfully raised.')
                );
            }
        } catch (\Exception $e) {
            $this->_helper->logDataInLogger(
                "controller_account_becomesellerPost saveSellerData : ".$e->getMessage()
            );
            $this->messageManager->addError($e->getMessage());
        }
    }

    /**
     * BecomesellerPost action.
     *
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        $hasError = false;
        /**
         * @var \Magento\Framework\Controller\Result\Redirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }

        if (!$this->getRequest()->isPost()) {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }

        try {
            if ($this->isExistingShopUrl()) {
                $this->messageManager->addError(
                    __('Shop URL already exist please set another.')
                );
                return $this->resultRedirectFactory->create()->setPath(
                    'marketplace/account/becomeseller',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }

            $this->saveSellerData();
        } catch (\Exception $e) {
            $this->_helper->logDataInLogger(
                "controller_account_becomesellerPost execute : ".$e->getMessage()
            );
            $this->messageManager->addError($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath(
            'marketplace/account/becomeseller',
            ['_secure' => $this->getRequest()->isSecure()]
        );
    }
}
