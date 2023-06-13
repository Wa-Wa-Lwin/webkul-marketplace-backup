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

namespace Webkul\Marketplace\Controller\Product\Attribute;

use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Webkul Marketplace Product Attribute CreateOptions Controller.
 */
class CreateOptions extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    protected $_eavAttribute;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @param \Magento\Framework\App\Action\Context                     $context
     * @param \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $eavAttribute
     * @param HelperData  $helper
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $eavAttribute,
        HelperData $helper,
        JsonHelper $jsonHelper
    ) {
        $this->_eavAttribute = $eavAttribute;
        $this->helper = $helper;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    /**
     * Create attribute new options.
     *
     * @return json data
     */
    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            try {
                $savedOptionsArray = [];
                $optionsData = [];
                $optionsData = $this->getRequest()->getParam('options');
                foreach ($optionsData as $option) {
                    if (isset($option['attribute_id']) && isset($option['label'])) {
                        $attributeId = $option['attribute_id'];
                        $eavAttributeColl = $this->_eavAttribute->create()->load($attributeId);
                        $optionsCount = count($eavAttributeColl->getSource()->getAllOptions(false));
                        $eavAttributeColl->setOption(
                            [
                                'value' => ['option_0' => [$option['label']]],
                                'order' => ['option_0' => $optionsCount++],
                            ]
                        );
                        $eavAttributeColl->save();
                        $allOptionsArr = $eavAttributeColl->getSource()
                        ->getAllOptions(false);
                        $createdOptionArr = array_pop($allOptionsArr);
                        $savedOptionsArray[$option['id']] = $createdOptionArr['value'];
                    }
                }
                $this->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode($savedOptionsArray)
                );
            } catch (\Exception $e) {
                $helper->logDataInLogger(
                    "Controller_Product_Attribute_CreateOptions execute : ".$e->getMessage()
                );
                $this->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode(
                        [
                            'error' => $e->getMessage(),
                            'errorcode' => $e->getCode(),
                        ]
                    )
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
