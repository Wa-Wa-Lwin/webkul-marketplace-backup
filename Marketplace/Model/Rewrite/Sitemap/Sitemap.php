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
namespace Webkul\Marketplace\Model\Rewrite\Sitemap;

use Magento\Framework\App\ObjectManager;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapConfigReaderInterface;

class Sitemap extends \Magento\Sitemap\Model\Sitemap
{
    /**
     * Initialize dependencies.
     * @param MpHelper $mpHelper
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Escaper $escaper,
        \Magento\Sitemap\Helper\Data $sitemapData,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sitemap\Model\ResourceModel\Catalog\CategoryFactory $categoryFactory,
        \Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory $productFactory,
        \Magento\Sitemap\Model\ResourceModel\Cms\PageFactory $cmsFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $modelDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        MpHelper $mpHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\Config\Model\Config\Reader\Source\Deployed\DocumentRoot $documentRoot = null,
        ItemProviderInterface $itemProvider = null,
        SitemapConfigReaderInterface $configReader = null,
        \Magento\Sitemap\Model\SitemapItemInterfaceFactory $sitemapItemFactory = null
    ) {
        $this->mpHelper = $mpHelper;
        $this->_escaper = $escaper;
        $this->_sitemapData = $sitemapData;
        $this->filesystem = $filesystem;
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::PUB);
        $this->_categoryFactory = $categoryFactory;
        $this->_productFactory = $productFactory;
        $this->_cmsFactory = $cmsFactory;
        $this->_dateModel = $modelDate;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->dateTime = $dateTime;
        $this->itemProvider = $itemProvider ?: ObjectManager::getInstance()->get(ItemProviderInterface::class);
        $this->configReader = $configReader ?: ObjectManager::getInstance()->get(SitemapConfigReaderInterface::class);
        $this->sitemapItemFactory = $sitemapItemFactory ?: ObjectManager::getInstance()->get(
            \Magento\Sitemap\Model\SitemapItemInterfaceFactory::class
        );
        parent::__construct(
            $context,
            $registry,
            $escaper,
            $sitemapData,
            $filesystem,
            $categoryFactory,
            $productFactory,
            $cmsFactory,
            $modelDate,
            $storeManager,
            $request,
            $dateTime,
            $resource,
            $resourceCollection,
            $data,
            $documentRoot,
            $itemProvider,
            $configReader,
            $sitemapItemFactory
        );
    }
    /**
     * Initialize sitemap
     *
     * @return void
     */
    protected function _initSitemapItems()
    {
        $helper = $this->mpHelper;
        if (!$helper->includeSellerUrlInSitemap()) {
            return parent::_initSitemapItems();
        }

        parent::_initSitemapItems();
        $error = "";
        try {
            $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
            $includeProfileUrl = $helper->includeProfileUrlInSitemap();
            $profileFrequency = $helper->getFrequencyOfProfileUrlInSitemap();
            $profilePriority = $helper->getPriorityOfProfileUrlInSitemap();
            $includeCollectionUrl = $helper->includeCollectionUrlInSitemap();
            $collectionFrequency = $helper->getFrequencyOfCollectionUrlInSitemap();
            $collectionPriority = $helper->getPriorityOfCollectionUrlInSitemap();
            $fields = ["shop_url", "shop_title", "updated_at"];
            $sellerCollection = $helper->getSellerCollection();
            $sellerCollection->resetColumns();
            $sellerCollection->addFieldsToCollection($fields);
            $sellerCollection->addStoreWiseSellerColumns();
            $sellerCollection->addAllSellerUrls();

            foreach ($sellerCollection as $seller) {
                $updatedAt = $seller->getUpdatedAt();
                if ($includeProfileUrl) {
                    $this->_sitemapItems[] = $this->sitemapItemFactory->create([
                        'url' => $seller->getProfileUrl(),
                        'updatedAt' => $updatedAt,
                        'images' => [],
                        'priority' => $profilePriority,
                        'changeFrequency' => $profileFrequency,
                    ]);
                }

                if ($includeCollectionUrl) {
                    $this->_sitemapItems[] = $this->sitemapItemFactory->create([
                        'url' => $seller->getCollectionUrl(),
                        'updatedAt' => $updatedAt,
                        'images' => [],
                        'priority' => $collectionPriority,
                        'changeFrequency' => $collectionFrequency,
                    ]);
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
    }
}
