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
use Webkul\Marketplace\Model\SellerFactory as MpSellerFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;

/**
 * Webkul Marketplace CustomerRegisterSuccessObserver Observer.
 */
class CustomerRegisterSuccessObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var MpSellerFactory
     */
    protected $mpSellerFactory;

    /**
     * @var UrlRewriteFactory
     */
    protected $urlRewriteFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var MpEmailHelper
     */
    protected $mpEmailHelper;

    /**
     * @var \Magento\Backend\Model\Url
     */
    protected $urlBackendModel;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface  $storeManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param MpHelper                                    $mpHelper
     * @param MpSellerFactory                             $mpSellerFactory
     * @param UrlRewriteFactory                           $urlRewriteFactory
     * @param \Magento\Customer\Model\Session             $customerSession
     * @param \Magento\Framework\UrlInterface             $urlInterface
     * @param MpEmailHelper                               $mpEmailHelper
     * @param \Magento\Backend\Model\Url                  $urlBackendModel
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        MpHelper $mpHelper,
        MpSellerFactory $mpSellerFactory,
        UrlRewriteFactory $urlRewriteFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\UrlInterface $urlInterface,
        MpEmailHelper $mpEmailHelper,
        \Magento\Backend\Model\Url $urlBackendModel
    ) {
        $this->_storeManager = $storeManager;
        $this->_messageManager = $messageManager;
        $this->_date = $date;
        $this->mpHelper = $mpHelper;
        $this->mpSellerFactory = $mpSellerFactory;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->customerSession = $customerSession;
        $this->urlInterface = $urlInterface;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->urlBackendModel = $urlBackendModel;
    }

    /**
     * customer register event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data = $observer['account_controller'];
        try {
            $paramData = $data->getRequest()->getParams();
            if (!empty($paramData['is_seller']) && !empty($paramData['profileurl']) && $paramData['is_seller'] == 1) {
                $customer = $observer->getCustomer();

                $profileurlcount = $this->mpSellerFactory->create()->getCollection();
                $profileurlcount->addFieldToFilter(
                    'shop_url',
                    $paramData['profileurl']
                );
                if (!$profileurlcount->getSize()) {
                    $partnerApprovalStatus = $this->mpHelper->getIsPartnerApproval();
                    $status = $partnerApprovalStatus ? 0 : 1;
                    $customerid = $customer->getId();
                    $model = $this->mpSellerFactory->create();
                    $model->setData('is_seller', $status);
                    $model->setData('shop_url', $paramData['profileurl']);
                    $model->setData('seller_id', $customerid);
                    $model->setData('store_id', 0);
                    $model->setCreatedAt($this->_date->gmtDate());
                    $model->setUpdatedAt($this->_date->gmtDate());
                    $model->setAdminNotification(1);
                    $model->save();
                    $loginUrl = $this->urlInterface->getUrl("marketplace/account/dashboard");
                    $this->customerSession->setBeforeAuthUrl($loginUrl);
                    $this->customerSession->setAfterAuthUrl($loginUrl);

                    $helper = $this->mpHelper;
                    if ($helper->getAutomaticUrlRewrite()) {
                        $this->createSellerPublicUrls($paramData['profileurl']);
                    }
                    if ($partnerApprovalStatus) {
                        $adminStoremail = $helper->getAdminEmailId();
                        $adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
                        $adminUsername = $helper->getAdminName();
                        $senderInfo = [
                            'name' => $customer->getFirstName().' '.$customer->getLastName(),
                            'email' => $customer->getEmail(),
                        ];
                        $receiverInfo = [
                            'name' => $adminUsername,
                            'email' => $adminEmail,
                        ];
                        $emailTemplateVariables['myvar1'] = $customer->getFirstName().' '.
                        $customer->getLastName();
                        $emailTemplateVariables['myvar2'] = $this->urlBackendModel->getUrl(
                            'customer/index/edit',
                            ['id' => $customer->getId()]
                        );
                        $emailTemplateVariables['myvar3'] = $helper->getAdminName();

                        $this->mpEmailHelper->sendNewSellerRequest(
                            $emailTemplateVariables,
                            $senderInfo,
                            $receiverInfo
                        );
                    }
                } else {
                    $this->_messageManager->addError(
                        __('This Shop URL already Exists.')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->mpHelper->logDataInLogger(
                "Observer_CustomerRegisterSuccessObserver execute : ".$e->getMessage()
            );
            $this->_messageManager->addError($e->getMessage());
        }
    }

    private function createSellerPublicUrls($profileurl = '')
    {
        if ($profileurl) {
            $getCurrentStoreId = $this->mpHelper->getCurrentStoreId();

            /*
            * Set Seller Profile Url
            */
            $sourceProfileUrl = 'marketplace/seller/profile/shop/'.$profileurl;
            $requestProfileUrl = $profileurl;
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $profileRequestUrl = '';
            $urlCollectionData = $this->urlRewriteFactory->create()
                              ->getCollection()
                              ->addFieldToFilter('target_path', $sourceProfileUrl)
                              ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlCollectionData as $value) {
                $urlId = $value->getId();
                $profileRequestUrl = $value->getRequestPath();
            }
            if ($profileRequestUrl != $requestProfileUrl) {
                $idPath = rand(1, 100000);
                $this->urlRewriteFactory->create()->load($urlId)
                ->setStoreId($getCurrentStoreId)
                ->setIsSystem(0)
                ->setIdPath($idPath)
                ->setTargetPath($sourceProfileUrl)
                ->setRequestPath($requestProfileUrl)
                ->save();
            }

            /*
            * Set Seller Collection Url
            */
            $sourceCollectionUrl = 'marketplace/seller/collection/shop/'.$profileurl;
            $requestCollectionUrl = $profileurl.'/collection';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $collectionRequestUrl = '';
            $urlCollectionData = $this->urlRewriteFactory->create()
                                ->getCollection()
                                ->addFieldToFilter('target_path', $sourceCollectionUrl)
                                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlCollectionData as $value) {
                $urlId = $value->getId();
                $collectionRequestUrl = $value->getRequestPath();
            }
            if ($collectionRequestUrl != $requestCollectionUrl) {
                $idPath = rand(1, 100000);
                $this->urlRewriteFactory->create()->load($urlId)
                ->setStoreId($getCurrentStoreId)
                ->setIsSystem(0)
                ->setIdPath($idPath)
                ->setTargetPath($sourceCollectionUrl)
                ->setRequestPath($requestCollectionUrl)
                ->save();
            }

            /*
            * Set Seller Feedback Url
            */
            $sourceFeedbackUrl = 'marketplace/seller/feedback/shop/'.$profileurl;
            $requestFeedbackUrl = $profileurl.'/feedback';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $feedbackRequestUrl = '';
            $urlFeedbackData = $this->urlRewriteFactory->create()
                              ->getCollection()
                              ->addFieldToFilter('target_path', $sourceFeedbackUrl)
                              ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlFeedbackData as $value) {
                $urlId = $value->getId();
                $feedbackRequestUrl = $value->getRequestPath();
            }
            if ($feedbackRequestUrl != $requestFeedbackUrl) {
                $idPath = rand(1, 100000);
                $this->urlRewriteFactory->create()->load($urlId)
                ->setStoreId($getCurrentStoreId)
                ->setIsSystem(0)
                ->setIdPath($idPath)
                ->setTargetPath($sourceFeedbackUrl)
                ->setRequestPath($requestFeedbackUrl)
                ->save();
            }

            /*
            * Set Seller Location Url
            */
            $sourceLocationUrl = 'marketplace/seller/location/shop/'.$profileurl;
            $requestLocationUrl = $profileurl.'/location';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $locationRequestUrl = '';
            $urlLocationData = $this->urlRewriteFactory->create()
                              ->getCollection()
                              ->addFieldToFilter('target_path', $sourceLocationUrl)
                              ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlLocationData as $value) {
                $urlId = $value->getId();
                $locationRequestUrl = $value->getRequestPath();
            }
            if ($locationRequestUrl != $requestLocationUrl) {
                $idPath = rand(1, 100000);
                $this->urlRewriteFactory->create()->load($urlId)
                ->setStoreId($getCurrentStoreId)
                ->setIsSystem(0)
                ->setIdPath($idPath)
                ->setTargetPath($sourceLocationUrl)
                ->setRequestPath($requestLocationUrl)
                ->save();
            }

            /**
             * Set Seller Policy Url
             */
            $sourcePolicyUrl = 'marketplace/seller/policy/shop/'.$profileurl;
            $requestPolicyUrl = $profileurl.'/policy';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $policyRequestUrl = '';
            $urlPolicyData = $this->urlRewriteFactory->create()
                            ->getCollection()
                            ->addFieldToFilter('target_path', $sourcePolicyUrl)
                            ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlPolicyData as $value) {
                $urlId = $value->getId();
                $policyRequestUrl = $value->getRequestPath();
            }
            if ($policyRequestUrl != $requestPolicyUrl) {
                $idPath = rand(1, 100000);
                $this->urlRewriteFactory->create()->load($urlId)
                ->setStoreId($getCurrentStoreId)
                ->setIsSystem(0)
                ->setIdPath($idPath)
                ->setTargetPath($sourcePolicyUrl)
                ->setRequestPath($requestPolicyUrl)
                ->save();
            }
        }
    }
}
