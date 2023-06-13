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

namespace Webkul\Marketplace\Controller\Order;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Model\SellerFactory as MpSellerModel;

/**
 * Webkul Marketplace Order Print PDF Header Infomation Save Controller.
 */
class Printpdfinfo extends \Magento\Customer\Controller\AbstractAccount
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
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var MpSellerModel
     */
    protected $mpSellerModel;

    /**
     * @param Context          $context
     * @param Session          $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param CustomerUrl      $customerUrl
     * @param HelperData       $helper
     * @param MpSellerModel    $mpSellerModel
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        CustomerUrl $customerUrl,
        HelperData $helper,
        MpSellerModel $mpSellerModel
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->customerUrl = $customerUrl;
        $this->helper = $helper;
        $this->mpSellerModel = $mpSellerModel;
        parent::__construct(
            $context
        );
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
     * Order Print PDF Header Infomation Save action.
     *
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();

            if ($this->getRequest()->isPost()) {
                try {
                    if (!$this->_formKeyValidator->validate($this->getRequest())) {
                        return $this->resultRedirectFactory->create()->setPath(
                            '*/*/shipping',
                            ['_secure' => $this->getRequest()->isSecure()]
                        );
                    }
                    $fields = $this->getRequest()->getParams();
                    $sellerId = $this->_getSession()->getCustomerId();
                    $storeId = $helper->getCurrentStoreId();
                    $autoId = 0;
                    $collection = $this->mpSellerModel->create()
                    ->getCollection()
                    ->addFieldToFilter(
                        'seller_id',
                        $sellerId
                    )
                    ->addFieldToFilter(
                        'store_id',
                        $storeId
                    );
                    foreach ($collection as $value) {
                        $autoId = $value->getId();
                    }
                    $sellerData = [];
                    if (!$autoId) {
                        $sellerDefaultData = [];
                        $collection = $this->mpSellerModel->create()
                        ->getCollection()
                        ->addFieldToFilter('seller_id', $sellerId)
                        ->addFieldToFilter('store_id', 0);
                        foreach ($collection as $value) {
                            $sellerDefaultData = $value->getData();
                            $value->setOthersInfo($fields['others_info']);
                            $value->save();
                        }
                        foreach ($sellerDefaultData as $key => $value) {
                            if ($key != 'entity_id') {
                                $sellerData[$key] = $value;
                            }
                        }
                    }

                    $value = $this->mpSellerModel->create()->load($autoId);
                    if (!empty($sellerData)) {
                        $value->addData($sellerData);
                    }
                    $value->setOthersInfo($fields['others_info']);
                    $value->setStoreId($storeId);
                    $value->save();
                    $this->messageManager->addSuccess(
                        __('Information was successfully saved')
                    );

                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/shipping',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                } catch (\Exception $e) {
                    $this->messageManager->addError($e->getMessage());

                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/shipping',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                }
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/shipping',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
