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

namespace Webkul\Marketplace\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Customer\Model\CustomerFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Model\SellerFactory;

/**
 * Customer Seller form block.
 */
class Tabs extends Generic implements TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    protected $_dob = null;

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var Magento\Directory\Model\ResourceModel\Country\CollectionFactory
     */
    protected $_country;

    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * @var CustomerFactory
     */
    protected $customerModel;

    /**
     * @var \Magento\Store\Ui\Component\Listing\Column\Store\Options
     */
    protected $options;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var SellerFactory
     */
    protected $sellerModel;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
     * @param CustomerFactory                         $customerModel
     * @param \Magento\Store\Ui\Component\Listing\Column\Store\Options $options
     * @param MpHelper                                $mpHelper
     * @param SellerFactory                           $sellerModel
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Directory\Model\ResourceModel\Country\Collection $country,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        CustomerFactory $customerModel,
        \Magento\Store\Ui\Component\Listing\Column\Store\Options $options,
        MpHelper $mpHelper,
        SellerFactory $sellerModel,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_systemStore = $systemStore;
        $this->_country = $country;
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->customerEdit = $customerEdit;
        $this->customerModel = $customerModel;
        $this->options = $options;
        $this->mpHelper = $mpHelper;
        $this->sellerModel = $sellerModel;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function getWysiwygConfig()
    {
        $config = $this->_wysiwygConfig->getConfig();
        $config = json_encode($config->getData());
    }

    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(
            RegistryConstants::CURRENT_CUSTOMER_ID
        );
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Seller Account Information');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Seller Account Information');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        $coll = $this->customerEdit->getMarketplaceUserCollection();
        $isSeller = false;
        foreach ($coll as $row) {
            $isSeller = $row->getIsSeller();
        }
        if ($this->getCustomerId() && $isSeller) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        $coll = $this->customerEdit->getMarketplaceUserCollection();
        $isSeller = false;
        foreach ($coll as $row) {
            $isSeller = $row->getIsSeller();
        }
        if ($this->getCustomerId() && $isSeller) {
            return false;
        }

        return true;
    }

    /**
     * Tab class getter.
     *
     * @return string
     */
    public function getTabClass()
    {
        return '';
    }

    /**
     * Return URL link to Tab content.
     *
     * @return string
     */
    public function getTabUrl()
    {
        return '';
    }

    /**
     * Tab should be loaded trough Ajax call.
     *
     * @return bool
     */
    public function isAjaxLoaded()
    {
        return false;
    }

    public function initForm()
    {
        if (!$this->canShowTab()) {
            return $this;
        }
        /**@var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('marketplace_');
        $customerId = $this->_coreRegistry->registry(
            RegistryConstants::CURRENT_CUSTOMER_ID
        );
        $storeid = $this->_storeManager->getStore()->getId();
        $mediaUrl = $this->_storeManager->getStore()
                                        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Seller Profile Information')]
        );
        $customer = $this->customerModel->create()->load($customerId);
        $partner = $this->customerEdit->getSellerInfoCollection();
        $twAactive = '';
        $fbAactive = '';
        $instagramActive = '';
        $youtubeActive = '';
        $vimeoActive = '';
        $pinterestActive = '';
        $moleskineActive = '';

        if ($partner['tw_active'] == 1) {
            $twAactive = "value='1' checked='checked'";
        }
        if ($partner['fb_active'] == 1) {
            $fbAactive = "value='1' checked='checked'";
        }
        if ($partner['instagram_active'] == 1) {
            $instagramActive = "value='1' checked='checked'";
        }
        if ($partner['youtube_active'] == 1) {
            $youtubeActive = "value='1' checked='checked'";
        }
        if ($partner['vimeo_active'] == 1) {
            $vimeoActive = "value='1' checked='checked'";
        }
        if ($partner['pinterest_active'] == 1) {
            $pinterestActive = "value='1' checked='checked'";
        }
        if ($partner['moleskine_active'] == 1) {
            $moleskineActive = "value='1' checked='checked'";
        }
        $allStoreViews = $this->options->toOptionArray();
        $len = count($allStoreViews);
        $allStoreViews[$len]['label'] = __('Admin Store');
        $allStoreViews[$len]['value'][0]['label'] = __('Admin Store View');
        $allStoreViews[$len]['value'][0]['value'] = 0;
        $allStores = $this->mpHelper->getAllStores();
        $currentUrl = $this->getCurrentUrl();
        $currentUrlArr = explode("store", $currentUrl);
        $currentUrlBase = $currentUrlArr[0];
        $storeUrl = $currentUrlBase."store/0";
        $data = '<input type="hidden" id="wk_mp_store0" value="'.$storeUrl.'">';
        foreach ($allStores as $store) {
            $storeUrl = $currentUrlBase."store/".$store->getId()."/";
            $data = $data.'<input type="hidden" id="wk_mp_store'.$store->getId().'" value="'.$storeUrl.'">';
        }
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $requestParams = $this->getRequest()->getParams();
        if (!isset($requestParams['store'])) {
            $collection = $this->sellerModel->create()->getCollection()
                          ->addFieldToFilter('seller_id', $customerId)
                          ->addFieldToFilter('store_id', $customer->getStoreId());
            if (count($collection)) {
                $storeId = $customer->getStoreId();
            }
        }
        $fieldset->addField(
            'store_id',
            'select',
            [
                'name' => 'store_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Select Store'),
                'title' => __('Select Store'),
                'values' => $allStoreViews,
                'value' => $storeId,
                'after_element_html' => $data.'<script>
                require([
                    "jquery"
                ], function($){
                    $("#marketplace_store_id").on("change", function() {
                        var storeId = $(this).val();
                        window.location.href = $("#wk_mp_store"+storeId).val();
                    });
                });
                </script>'
            ]
        );
        $fieldset->addField(
            'twitter_id',
            'text',
            [
                'name' => 'twitter_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Twitter ID'),
                'title' => __('Twitter ID'),
                'value' => $partner['twitter_id'],
                'after_element_html' => '<input
                    type="checkbox"
                    name="tw_active"
                    data-form-part="customer_form"
                    onchange="this.value = this.checked ? 1 : 0;"
                    title="'.__('Allow to Display Twitter Icon in Profile Page').'"
                    '.$twAactive.'
                >',
            ]
        );
        $fieldset->addField(
            'facebook_id',
            'text',
            [
                'name' => 'facebook_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Facebook ID'),
                'title' => __('Facebook ID'),
                'value' => $partner['facebook_id'],
                'after_element_html' => '<input
                    type="checkbox"
                    name="fb_active"
                    data-form-part="customer_form"
                    onchange="this.value = this.checked ? 1 : 0;"
                    title="'.__('Allow to Display Facebook Icon in Profile Page').'"
                    '.$fbAactive.'
                >',
            ]
        );
        $fieldset->addField(
            'instagram_id',
            'text',
            [
                'name' => 'instagram_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Instagram ID'),
                'title' => __('Instagram ID'),
                'value' => $partner['instagram_id'],
                'after_element_html' => '<input
                    type="checkbox"
                    name="instagram_active"
                    data-form-part="customer_form"
                    onchange="this.value = this.checked ? 1 : 0;"
                    title="'.__('Allow to Display Instagram Icon in Profile Page').'"
                    '.$instagramActive.'
                >',
            ]
        );
        $fieldset->addField(
            'youtube_id',
            'text',
            [
                'name' => 'youtube_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Youtube ID'),
                'title' => __('Youtube ID'),
                'value' => $partner['youtube_id'],
                'after_element_html' => '<input
                    type="checkbox"
                    name="youtube_active"
                    data-form-part="customer_form"
                    onchange="this.value = this.checked ? 1 : 0;"
                    title="'.__('Allow to Display Youtube Icon in Profile Page').'"
                    '.$youtubeActive.'
                >',
            ]
        );
        $fieldset->addField(
            'vimeo_id',
            'text',
            [
                'name' => 'vimeo_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Vimeo ID'),
                'title' => __('Vimeo ID'),
                'value' => $partner['vimeo_id'],
                'after_element_html' => '<input
                type="checkbox"
                name="vimeo_active"
                data-form-part="customer_form"
                onchange="this.value = this.checked ? 1 : 0;"
                title="'.__('Allow to Display Vimeo Icon in Profile Page').'"
                '.$vimeoActive.'
            >',
            ]
        );
        $fieldset->addField(
            'pinterest_id',
            'text',
            [
                'name' => 'pinterest_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Pinterest ID'),
                'title' => __('Pinterest ID'),
                'value' => $partner['pinterest_id'],
                'after_element_html' => '<input
                    type="checkbox"
                    name="pinterest_active"
                    data-form-part="customer_form"
                    onchange="this.value = this.checked ? 1 : 0;"
                    title="'.__('Allow to Display Pinterest Icon in Profile Page').'"
                    '.$pinterestActive.'
                >',
            ]
        );
        $fieldset->addField(
            'moleskine_id',
            'text',
            [
                'name' => 'moleskine_id',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Moleskine ID'),
                'title' => __('Moleskine ID'),
                'value' => $partner['moleskine_id'],
                'after_element_html' => '<input
                    type="checkbox"
                    name="moleskine_active"
                    data-form-part="customer_form"
                    onchange="this.value = this.checked ? 1 : 0;"
                    title="'.__('Allow to Display Moleskine Icon in Profile Page').'"
                    '.$moleskineActive.'
                >',
            ]
        );
        $fieldset->addField(
            'contact_number',
            'text',
            [
                'name' => 'contact_number',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Contact Number'),
                'title' => __('Contact Number'),
                'value' => $partner['contact_number'],
            ]
        );
        $fieldset->addField(
            'taxvat',
            'text',
            [
                'name' => 'taxvat',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Tax/VAT Number'),
                'title' => __('Tax/VAT Number'),
                'value' => $customer->getTaxvat(),
            ]
        );
        $fieldset->addField(
            'shop_title',
            'text',
            [
                'name' => 'shop_title',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Shop Title'),
                'title' => __('Shop Title'),
                'value' => $partner['shop_title'],
            ]
        );
        $fieldset->addField(
            'company_locality',
            'text',
            [
                'name' => 'company_locality',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Locality'),
                'title' => __('Company Locality'),
                'value' => $partner['company_locality'],
            ]
        );
        $fieldset->addField(
            'country_pic',
            'select',
            [
                'name' => 'country_pic',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Select Country'),
                'title' => __('Select Country'),
                'values' => $this->_country->loadByStore()->toOptionArray(),
                'value' => $partner['country_pic'],
            ]
        );
        $fieldset->addField(
            'company_description',
            'editor',
            [
                'name' => 'company_description',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Description'),
                'title' => __('Company Description'),
                'value' => $partner['company_description'],
                'config'    => $this->_wysiwygConfig->getConfig([
                    'add_widgets' => false,
                    'add_variables' => false
                    ]),
                'wysiwyg'   => true,
                'required'  => false,
                'after_element_html' => ""
            ]
        );
        $fieldset->addField(
            'return_policy',
            'editor',
            [
                'name' => 'return_policy',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Return Policy'),
                'title' => __('Return Policy'),
                'value' => $partner['return_policy'],
                'config'    => $this->_wysiwygConfig->getConfig([
                    'add_widgets' => false,
                    'add_variables' => false
                    ]),
                'wysiwyg'   => true,
                'required'  => false,
                'after_element_html' => ""
            ]
        );
        $fieldset->addField(
            'shipping_policy',
            'editor',
            [
                'name' => 'shipping_policy',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Shipping Policy'),
                'title' => __('Shipping Policy'),
                'value' => $partner['shipping_policy'],
                'config'    => $this->_wysiwygConfig->getConfig([
                    'add_widgets' => false,
                    'add_variables' => false
                    ]),
                'wysiwyg'   => true,
                'required'  => false,
                'after_element_html' => ""
            ]
        );
        $fieldset->addField(
            'privacy_policy',
            'editor',
            [
                'name' => 'privacy_policy',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Privacy Policy'),
                'title' => __('Privacy Policy'),
                'value' => $partner['privacy_policy'],
                'config'    => $this->_wysiwygConfig->getConfig([
                    'add_widgets' => false,
                    'add_variables' => false
                    ]),
                'wysiwyg'   => true,
                'required'  => false,
                'after_element_html' => ""
            ]
        );
        $fieldset->addField(
            'meta_keyword',
            'textarea',
            [
                'name' => 'meta_keyword',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Meta Keywords'),
                'title' => __('Meta Keywords'),
                'value' => $partner['meta_keyword'],
            ]
        );
        $fieldset->addField(
            'meta_description',
            'textarea',
            [
                'name' => 'meta_description',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Meta Description'),
                'title' => __('Meta Description'),
                'value' => $partner['meta_description'],
            ]
        );
        $fieldset->addField(
            'banner_pic',
            'file',
            [
                'name' => 'banner_pic',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Banner'),
                'title' => __('Company Banner'),
                'value' => $partner['banner_pic'],
                'after_element_html' => '<label style="width:100%;">
                    Allowed File Type : [jpg, jpeg, gif, png]
                </label>
                <img style="margin:5px 0;width:700px;"
                src="'.$mediaUrl.'avatar/'.$partner['banner_pic'].'"
                />',
            ]
        );
        $fieldset->addField(
            'logo_pic',
            'file',
            [
                'name' => 'logo_pic',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Company Logo'),
                'title' => __('Company Logo'),
                'value' => $partner['logo_pic'],
                'after_element_html' => '<label style="width:100%;">
                    Allowed File Type : [jpg, jpeg, gif, png]
                </label>
                <img style="margin:5px 0;width:250px;"
                src="'.$mediaUrl.'avatar/'.$partner['logo_pic'].'"
                />',
            ]
        );

        $form->setUseContainer(true);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->canShowTab()) {
            $this->initForm();

            return parent::_toHtml();
        } else {
            return '';
        }
    }

    /**
     * Prepare the layout.
     *
     * @return $this
     */
    public function getFormHtml()
    {
        $html = parent::getFormHtml();
        $html .= $this->getLayout()->createBlock(
            \Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Js::class
        )->toHtml();

        return $html;
    }
}
