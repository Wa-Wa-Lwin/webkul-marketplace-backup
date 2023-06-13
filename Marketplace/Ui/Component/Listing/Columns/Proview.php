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

namespace Webkul\Marketplace\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Security\Model\AdminSessionsManager;
use Magento\Catalog\Model\ProductFactory;
use Magento\Store\Model\StoreManagerInterface;

class Proview extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var AdminSessionsManager
     */
    protected $adminSessionsManager;

    /**
     * @var ProductFactory
     */
    protected $productModel;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor.
     *
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface       $urlBuilder
     * @param AdminSessionsManager $adminSessionsManager
     * @param ProductFactory $productModel
     * @param StoreManagerInterface $storeManager
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\Url $urlBuilder,
        AdminSessionsManager $adminSessionsManager,
        ProductFactory $productModel,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->adminSessionsManager = $adminSessionsManager;
        $this->productModel = $productModel;
        $this->storeManager = $storeManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
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
        $adminSession = $this->adminSessionsManager;
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['mageproduct_id'])) {
                    if ($item['visibility'] != 1) {
                        $url = $this->getWebsiteUrl($item['mageproduct_id']);
                        $item[$fieldName] = "<a href='".$url.'marketplace/catalog/view/id/'.$item['mageproduct_id'].'/?SID='.$adminSession->getCurrentSession()->getSessionId()."' target='blank' title='".__('View Product')."'>".__('View').'</a>';
                    } else {
                        $item[$fieldName] = __("--");
                    }
                }
            }
        }

        return $dataSource;
    }

    /**
     * get website url by product id
     * @param  int $productId
     * @return string
     */
    public function getWebsiteUrl($productId)
    {
        $product = $this->productModel->create()->load($productId);
        $storeManager =  $this->storeManager;
        $productWebsites = $product->getWebsiteIds();
        $websites = $storeManager->getWebsites();
        $url = '';
        foreach ($websites as $website) {
            if (isset($productWebsites[0]) && $productWebsites[0] == $website->getId()) {
                foreach ($website->getStores() as $store) {
                    $storeObj = $storeManager->getStore($store);
                    $url = $storeObj->getBaseUrl();
                    break;
                }
            }
            if ($url !== '') {
                break;
            }
        }
        return $url;
    }
}
