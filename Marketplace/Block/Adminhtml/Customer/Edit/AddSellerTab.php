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

/**
 * Customer account form block.
 */
class AddSellerTab extends Generic implements TabInterface
{
    /**
     * @var string
     */
    /*protected $_template = 'customfields/customer/button.phtml';*/

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
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Directory\Model\ResourceModel\Country\Collection $country,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_systemStore = $systemStore;
        $this->_country = $country;
        $this->customerEdit = $customerEdit;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Do You Want To Make This Customer As Seller ?');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Do You Want To Make This Customer As Seller ?');
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
            return false;
        }

        return true;
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
            return true;
        }

        return false;
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
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Do You Want To Make This Customer As Seller ?')]
        );

        $coll = $this->customerEdit->getMarketplaceSellerCollection();
        $profileurl = '';
        foreach ($coll as $row) {
            $profileurl = $row->getShopUrl();
        }

        $fieldset->addField(
            'profileurl',
            'text',
            [
                'name' => 'profileurl',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Shop Url'),
                'title' => __('Shop Url'),
                'value' => $profileurl,
            ]
        );
        $fieldset->addField(
            'is_seller_add',
            'checkbox',
            [
                'name' => 'is_seller_add',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Approve Seller'),
                'title' => __('Approve Seller'),
                'onchange' => 'this.value = this.checked;',
            ]
        );
        $this->setForm($form);

        return $this;
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
            \Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab\AddSellerJs::class
        )->toHtml();

        return $html;
    }
}
