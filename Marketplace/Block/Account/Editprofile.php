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

namespace Webkul\Marketplace\Block\Account;

use Magento\Framework\App\Request\DataPersistorInterface;
use Webkul\Marketplace\Model\SaleperpartnerFactory as MpSalesPartner;

/**
 * Webkul Marketplace Account Editprofile Block
 */
class Editprofile extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $_countryCollectionFactory;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helper;

    /**
     * @var MpSalesPartner
     */
    protected $mpSalesPartner;

    /**
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param \Webkul\Marketplace\Helper\Data $helper
     * @param array $data
     * @param MpSalesPartner $mpSalesPartner
     */
    public function __construct(
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        DataPersistorInterface $dataPersistor,
        \Webkul\Marketplace\Helper\Data $helper,
        array $data = [],
        MpSalesPartner $mpSalesPartner = null,
        \Magento\Cms\Helper\Wysiwyg\Images $wysiwygImages = null
    ) {
        $this->_countryCollectionFactory = $countryCollectionFactory;
        $this->dataPersistor = $dataPersistor;
        $this->helper = $helper;
        $this->mpSalesPartner = $mpSalesPartner ?: \Magento\Framework\App\ObjectManager::getInstance()
                                  ->create(MpSalesPartner::class);
        $this->wysiwygImages = $wysiwygImages ?: \Magento\Framework\App\ObjectManager::getInstance()
                                ->create(\Magento\Cms\Helper\Wysiwyg\Images::class);
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    public function getCountryCollection()
    {
        $collection = $this->_countryCollectionFactory->create()->loadByStore();
        return $collection;
    }

    /**
     * Retrieve list of top destinations countries
     *
     * @return array
     */
    protected function getTopDestinations()
    {
        $destinations = (string)$this->_scopeConfig->getValue(
            'general/country/destinations',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return !empty($destinations) ? explode(',', $destinations) : [];
    }

    /**
     * Retrieve list of countries option array
     *
     * @return array
     */
    public function getCountryOptionArray()
    {
        return $options = $this->getCountryCollection()
                ->setForegroundCountries($this->getTopDestinations())
                ->toOptionArray();
    }

    public function getPersistentData()
    {
        $partner = $this->helper->getSeller();
        $persistentData = (array)$this->dataPersistor->get('seller_profile_data');
        foreach ($partner as $key => $value) {
            if (empty($persistentData[$key])) {
                $persistentData[$key] = $value;
            }
        }
        $this->dataPersistor->clear('seller_profile_data');
        return $persistentData;
    }
    /**
     * getMinimumOrderValue function
     *
     * @return string|float
     */
    public function getMinimumOrderValue()
    {
        $minOrderAmount = '';
        $sellerId = $this->helper->getCustomerId();
        $salePerPartnerModel = $this->mpSalesPartner->create()
                                    ->getCollection()
                                    ->addFieldToFilter('seller_id', $sellerId)
                                    ->addFieldToFilter('min_order_status', 1);
        if ($salePerPartnerModel->getSize()) {
            $minOrderAmount = $salePerPartnerModel->getFirstItem()
                                                ->getMinOrderAmount();
        }
        return $minOrderAmount;
    }
    /**
     * getAnalyticId function
     *
     * @return string
     */
    public function getAnalyticId()
    {
        $analyticId = '';
        $sellerId = $this->helper->getCustomerId();
        $salePerPartnerModel = $this->mpSalesPartner->create()
                                    ->getCollection()
                                    ->addFieldToFilter('seller_id', $sellerId)
                                    ->addFieldToFilter('analytic_id', ['neq' => null]);
        if ($salePerPartnerModel->getSize()) {
            $analyticId = $salePerPartnerModel->getFirstItem()
                                                ->getAnalyticId();
        }
        return $analyticId;
    }
    /**
     * getWysiwygUrl function
     *
     * @return string
     */
    public function getWysiwygUrl()
    {
        $currentTreePath = $this->wysiwygImages->idEncode(
            \Magento\Cms\Model\Wysiwyg\Config::IMAGE_DIRECTORY
        );
        $url =  $this->getUrl(
            'marketplace/wysiwyg_images/index',
            [
                'current_tree_path' => $currentTreePath
            ]
        );
        return $url;
    }
}
