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

namespace Webkul\Marketplace\Plugin\Customer\Controller\Adminhtml\Index;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Controller\Adminhtml\Index\Save as CustomerSaveController;

class Save
{
    public function afterExecute(CustomerSaveController $subject, $result)
    {
        $customerId = $this->getCurrentCustomerId($subject);
        $sellerPanel = trim($subject->getRequest()->getParam("seller_panel"));
        if ($sellerPanel) {
            $path = $result->getPath();
            if (strpos($path, "customer/index") !== false) {
                return $result->setPath("marketplace/seller");
            } else {
                if ($customerId) {
                    $result->setPath(
                        'customer/*/edit',
                        ['id' => $customerId, 'seller_panel' => 1, '_current' => true]
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve current customer ID
     *
     * @return int
     */
    protected function getCurrentCustomerId($subject)
    {
        $originalRequestData = $subject->getRequest()->getPostValue(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER);

        $customerId = isset($originalRequestData['entity_id'])
            ? $originalRequestData['entity_id']
            : null;

        return $customerId;
    }
}
