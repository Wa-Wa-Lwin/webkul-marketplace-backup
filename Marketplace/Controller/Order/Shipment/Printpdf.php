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

namespace Webkul\Marketplace\Controller\Order\Shipment;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Webkul Marketplace Order Shipment PDF Print Controller.
 */
class Printpdf extends \Webkul\Marketplace\Controller\Order
{
    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            if ($shipment = $this->_initShipment()) {
                try {
                    $pdf = $this->_objectManager->create(
                        \Webkul\Marketplace\Model\Order\Pdf\Shipment::class
                    )->getPdf(
                        [$shipment]
                    );
                    $date = $this->date->date('Y-m-d_H-i-s');

                    return $this->fileFactory->create(
                        'packingslip'.$date.'.pdf',
                        $pdf->render(),
                        DirectoryList::VAR_DIR,
                        'application/pdf'
                    );
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());

                    return $this->resultRedirectFactory->create()->setPath(
                        'marketplace/order/history',
                        [
                            '_secure' => $this->getRequest()->isSecure(),
                        ]
                    );
                } catch (\Exception $e) {
                    $this->helper->logDataInLogger(
                        "Controller_Order_Shipment_Printpdf execute : ".$e->getMessage()
                    );
                    $this->messageManager->addError($e->getMessage());
                    $this->messageManager->addError(
                        __('We can\'t print the shipment right now.')
                    );

                    return $this->resultRedirectFactory->create()->setPath(
                        'marketplace/order/history',
                        [
                            '_secure' => $this->getRequest()->isSecure(),
                        ]
                    );
                }
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    'marketplace/order/history',
                    [
                        '_secure' => $this->getRequest()->isSecure(),
                    ]
                );
            }
        }
        else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}