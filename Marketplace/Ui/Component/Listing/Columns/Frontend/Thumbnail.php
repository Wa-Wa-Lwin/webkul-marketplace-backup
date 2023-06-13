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

namespace Webkul\Marketplace\Ui\Component\Listing\Columns\Frontend;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Catalog\Model\ProductFactory;

class Thumbnail extends \Magento\Ui\Component\Listing\Columns\Column
{
    const NAME = 'thumbnail';

    const ALT_FIELD = 'name';

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @param ContextInterface                $context
     * @param UiComponentFactory              $uiComponentFactory
     * @param \Magento\Catalog\Helper\Image   $imageHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array                           $components
     * @param array                           $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        ProductFactory $productModel,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->imageHelper = $imageHelper;
        $this->urlBuilder = $urlBuilder;
        $this->productModel = $productModel;
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $productData = new \Magento\Framework\DataObject($item);
                $product = $this->productModel->create()->load($productData->getEntityId());
                $imageHelper = $this->imageHelper->init($product, 'product_thumbnail_image');
                $item[$fieldName . '_src'] = $imageHelper->getUrl();
                $item[$fieldName . '_alt'] = $this->getAlt($item) ?: $imageHelper->getLabel();
                $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                    'marketplace/product/edit',
                    ['id' => $product->getEntityId()]
                );
                $origImageHelper = $this->imageHelper->init($product, 'product_base_image');
                $item[$fieldName . '_orig_src'] = $origImageHelper->getUrl();
            }
        }

        return $dataSource;
    }

    /**
     * @param array $row
     *
     * @return null|string
     */
    protected function getAlt($row)
    {
        $altField = $this->getData('config/altField') ?: self::ALT_FIELD;

        return isset($row[$altField]) ? $row[$altField] : null;
    }
}
