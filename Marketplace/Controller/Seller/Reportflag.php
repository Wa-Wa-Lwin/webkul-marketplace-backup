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
 * Webkul Marketplace Reportflag controller.
 */
class Reportflag extends Action
{
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
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var \Webkul\Marketplace\Model\SellerFlagsFactory
     */
    protected $sellerFlags;

    /**
     * @param Context  $context
     * @param HelperData  $helper
     * @param MpEmailData  $mpEmailHelper
     * @param JsonHelper  $jsonHelper
     */
    public function __construct(
        Context $context,
        HelperData $helper,
        MpEmailData $mpEmailHelper,
        JsonHelper $jsonHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Webkul\Marketplace\Model\SellerFlagsFactory $sellerFlags
    ) {
        $this->helper = $helper;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->jsonHelper = $jsonHelper;
        $this->date = $date;
        $this->sellerFlags = $sellerFlags;
        parent::__construct($context);
    }

    /**
     * Report Flag for Seller
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $helper = $this->helper;
        $data = $this->getRequest()->getParams();
        if ($data['seller_id']) {
            if (!isset($data['reason'])) {
                $data['reason'] = "";
            } elseif ($data['reason'] == "other_value") {
                $data['reason'] = $data['flag_other_reason'];
            }
            $data['created_at'] = $this->date->gmtDate();
            try {
                $sellerFlagModel = $this->sellerFlags->create()
                              ->addData($data)
                              ->save();
                $senderInfo = [
                'name' => $data['name'],
                'email' => $data['email']
                ];
                $receiverInfo = [
                'name' => $helper->getAdminName(),
                'email' => $helper->getAdminEmailId()
                ];
                $emailTemplateVariables['admin_name'] = $helper->getAdminName();
                $emailTemplateVariables['seller_name'] = $data['seller_name'];
                $emailTemplateVariables['reporter_name'] = $data['name'];
                $emailTemplateVariables['reporter_email'] = $data['email'];
                $emailTemplateVariables['reason'] = $data['reason'];
                $this->mpEmailHelper->sendSellerFlagMail(
                    $emailTemplateVariables,
                    $senderInfo,
                    $receiverInfo
                );
            } catch (\Exception $e) {
                $helper->logDataInLogger("Seller_ReportflagController_Execute : ".$e->getMessage());
            }
        }
        $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode('true')
        );
    }
}
