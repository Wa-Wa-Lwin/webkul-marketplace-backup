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
namespace Webkul\Marketplace\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Select;
use Magento\Ui\Component\Form\Element\DataType\Text;

class AssignSeller extends AbstractModifier
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helper;
    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Webkul\Marketplace\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\Registry  $coreRegistry,
        \Webkul\Marketplace\Helper\Data $helper
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->helper = $helper;
    }
    public function modifyData(array $data)
    {
        return $data;
    }

    public function modifyMeta(array $meta)
    {
        if ($this->checkFieldStatus()) {
            $meta = array_replace_recursive(
                $meta,
                [
                  'assign_seller' => [
                      'arguments' => [
                          'data' => [
                              'config' => [
                                  'label' => __('Assign Product to Seller'),
                                  'componentType' => Fieldset::NAME,
                                  'dataScope' => 'data.product.assign_seller',
                                  'collapsible' => false,
                                  'sortOrder' => 5,
                              ],
                          ],
                      ],
                      'children' => [
                      'assignseller_field' => $this->getSellerField()
                      ],
                  ]
                ]
            );
        }
            return $meta;
    }
    /**
     * getSellerField is used to show the field for assign seller.
     * @return mixed
     */
    public function getSellerField()
    {
        $sellerId = $this->getProductSeller();
        return [
              'arguments' => [
                  'data' => [
                      'config' => [
                          'label' => __('Select Seller'),
                          'componentType' => Field::NAME,
                          'formElement' => Select::NAME,
                          'dataScope' => 'seller_id',
                          'dataType' => Text::NAME,
                          'sortOrder' => 10,
                          'options' => $this->helper->getSellerList(),
                          'value' => $sellerId,
                          'disabled' => $sellerId ? true : false
                      ],
                  ],
              ],
          ];
    }

    /**
     * checkFieldStatus is used to check the allowed product to seller
     * @return bool
     */
    public function checkFieldStatus()
    {
        $helper = $this->helper;
        $product = $this->coreRegistry->registry('product');
        $productType = $product->getTypeId();
        $allowedsProducts = explode(',', $helper->getAllowedProductType());
        if (in_array($productType, $allowedsProducts)) {
            return true;
        }
        return false;
    }

    /**
     * getProductSeller is used to get the seller id by the product id
     * @return int||null
     */
    public function getProductSeller()
    {
        $product = $this->coreRegistry->registry('product');
        $productId = $product->getId();
        $sellerId = $this->helper->getSellerIdByProductId($productId);
        return $sellerId;
    }
}
