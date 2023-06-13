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

/**
 * Class Flags
 */
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Ui\Component\Form;
use Magento\Framework\UrlInterface;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Flags modifier for catalog product form
 */
class Flags extends AbstractModifier
{
    const GROUP_FLAGS = 'flags';
    const GROUP_CONTENT = 'content';
    const SORT_ORDER = 30;
    const LINK_TYPE = 'associated';
    /**
     * @var LocatorInterface
     * @since 100.1.0
     */
    protected $locator;
    /**
     * @var UrlInterface
     * @since 100.1.0
     */
    protected $urlBuilder;
    /**
     * @var ModuleManager
     */
    private $moduleManager;
    /**
     * @param LocatorInterface $locator
     * @param UrlInterface $urlBuilder
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        LocatorInterface $locator,
        UrlInterface $urlBuilder,
        ModuleManager $moduleManager
    ) {
        $this->locator = $locator;
        $this->urlBuilder = $urlBuilder;
        $this->moduleManager = $moduleManager;
    }
    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function modifyMeta(array $meta)
    {
        if (!$this->locator->getProduct()->getId() || !$this->moduleManager->isOutputEnabled('Webkul_Marketplace')) {
            return $meta;
        }
        $meta[static::GROUP_FLAGS] = [
            'children' => [
                'flags_listing' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => true,
                                'componentType' => 'insertListing',
                                'dataScope' => 'flags_listing',
                                'externalProvider' => 'flags_listing.flags_listing_data_source',
                                'selectionsProvider' => 'flags_listing.flags_listing.product_columns.ids',
                                'ns' => 'flags_listing',
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => false,
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'productId' => '${ $.provider }:data.product.current_product_id',
                                    '__disableTmpl' => ['productId' => false],
                                ],
                                'exports' => [
                                    'productId' => '${ $.externalProvider }:params.current_product_id',
                                    '__disableTmpl' => ['productId' => false],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Product Flags'),
                        'collapsible' => true,
                        'opened' => false,
                        'componentType' => Form\Fieldset::NAME,
                        'sortOrder' =>
                            $this->getNextGroupSortOrder(
                                $meta,
                                static::GROUP_CONTENT,
                                static::SORT_ORDER
                            ),
                    ],
                ],
            ],
        ];
        return $meta;
    }
    /**
     * @inheritdoc
     * @since 100.1.0
     */
    public function modifyData(array $data)
    {
        $flagProductId = $this->locator->getProduct()->getId();
        $data[$flagProductId][self::DATA_SOURCE_DEFAULT]['current_product_id'] = $flagProductId;
        return $data;
    }
}
