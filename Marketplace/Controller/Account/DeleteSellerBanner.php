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
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session;
use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Customer\Model\Url as CustomerUrl;
use Webkul\Marketplace\Model\SellerFactory;
use Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Webkul Marketplace Account DeleteSellerBanner Controller.
 */
class DeleteSellerBanner extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @var SellerFactory
     */
    protected $sellerModel;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @param Context     $context
     * @param Session     $customerSession
     * @param PageFactory $resultPageFactory
     * @param HelperData  $helper
     * @param CustomerUrl $customerUrl
     * @param SellerFactory $sellerModel
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        HelperData $helper,
        CustomerUrl $customerUrl,
        SellerFactory $sellerModel,
        JsonHelper $jsonHelper
    ) {
        $this->_customerSession = $customerSession;
        $this->_resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->customerUrl = $customerUrl;
        $this->sellerModel = $sellerModel;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
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
     * DeleteSellerBanner action.
     *
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        try {
            $autoId = '';
            $sellerId = $this->helper->getCustomerId();
            $storeId = $this->helper->getCurrentStoreId();
            $collection = $this->sellerModel->create()
            ->getCollection()
            ->addFieldToFilter(
                'seller_id',
                $sellerId
            )
            ->addFieldToFilter('store_id', $storeId);
            foreach ($collection as $value) {
                $autoId = $value->getId();
            }
            // If seller data doesn't exist for current store
            $fields = [];
            if (!$autoId) {
                $sellerDefaultData = [];
                $collection = $this->sellerModel->create()
                ->getCollection()
                ->addFieldToFilter('seller_id', $sellerId)
                ->addFieldToFilter('store_id', 0);
                foreach ($collection as $value) {
                    $sellerDefaultData = $value->getData();
                }
                foreach ($sellerDefaultData as $key => $value) {
                    if ($key != 'entity_id') {
                        $fields[$key] = $value;
                    }
                }
            }
            if ($autoId != '') {
                $value = $this->sellerModel->create()->load($autoId);
                $value->setBannerPic('');
                $value->setStoreId($storeId);
                $value->save();
            } else {
                $value = $this->sellerModel->create();
                $value->setData($fields);
                $value->setBannerPic('');
                $value->setStoreId($storeId);
                $value->save();
            }
            // clear cache
            $this->helper->clearCache();
            $this->getResponse()->representJson(
                $this->jsonHelper->jsonEncode(true)
            );
        } catch (\Exception $e) {
            $this->helper->logDataInLogger(
                "controller_account_deleteSellerBanner execute : ".$e->getMessage()
            );
            $this->getResponse()->representJson(
                $this->jsonHelper->jsonEncode($e->getMessage())
            );
        }
    }
}
