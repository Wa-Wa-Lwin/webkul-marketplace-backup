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

namespace Webkul\Marketplace\Controller\Seller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Catalog\Model\Product;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Helper\Email as MpEmailData;
use Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Webkul Marketplace Sendmail controller.
 */
class Sendmail extends Action
{
    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var Product
     */
    protected $_product;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var MpEmailData
     */
    protected $mpEmailHelper;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @param Context  $context
     * @param Session  $customerSession
     * @param Customer $customer
     * @param Product  $product
     * @param HelperData  $helper
     * @param MpEmailData  $mpEmailHelper
     * @param JsonHelper  $jsonHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Customer $customer,
        Product $product,
        HelperData $helper,
        MpEmailData $mpEmailHelper,
        JsonHelper $jsonHelper
    ) {
        $this->_customer = $customer;
        $this->_product = $product;
        $this->_customerSession = $customerSession;
        $this->helper = $helper;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    /**
     * Sendmail to Seller action.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $helper = $this->helper;
        if (!$helper->getSellerProfileDisplayFlag()) {
            $this->getRequest()->initForward();
            $this->getRequest()->setActionName('noroute');
            $this->getRequest()->setDispatched(false);

            return false;
        }
        $data = $this->getRequest()->getParams();
        if ($data['seller-id']) {
            $this->_eventManager->dispatch(
                'mp_send_querymail',
                [$data]
            );
            if ($this->_customerSession->isLoggedIn()) {
                $buyerName = $this->_customerSession->getCustomer()->getName();
                $buyerEmail = $this->_customerSession->getCustomer()->getEmail();
            } else {
                $buyerEmail = $data['email'];
                $buyerName = $data['name'];
                if (strlen($buyerName) < 2) {
                    $buyerName = 'Guest';
                }
            }
            $emailTemplateVariables = [];
            $senderInfo = [];
            $receiverInfo = [];
            $seller = $this->_customer->load($data['seller-id']);
            $emailTemplateVariables['myvar1'] = $seller->getName();
            $sellerEmail = $seller->getEmail();
            if (!isset($data['product-id'])) {
                $data['product-id'] = 0;
            } else {
                $emailTemplateVariables['myvar3'] = $this->_product->load(
                    $data['product-id']
                )->getName();
            }
            $emailTemplateVariables['myvar4'] = $data['ask'];
            $emailTemplateVariables['myvar6'] = $data['subject'];
            $emailTemplateVariables['myvar5'] = $buyerEmail;
            $senderInfo = [
                'name' => $buyerName,
                'email' => $buyerEmail,
            ];
            $receiverInfo = [
                'name' => $seller->getName(),
                'email' => $sellerEmail,
            ];
            $this->mpEmailHelper->sendQuerypartnerEmail(
                $data,
                $emailTemplateVariables,
                $senderInfo,
                $receiverInfo
            );
        }
        $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode('true')
        );
    }
}
