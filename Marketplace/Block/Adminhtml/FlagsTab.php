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
namespace Webkul\Marketplace\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;

/**
 * Class FlagsTab is used to show the tab in customer edit page
 */
class FlagsTab extends TabWrapper
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;
    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;
    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->customerEdit = $customerEdit;
        parent::__construct($context, $data);
    }

    /**
     * @return string|null
     */
    public function getCustomerId()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * @inheritdoc
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
     * Return Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Flags');
    }
    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('marketplace/seller/flags', ['_current' => true]);
    }
}
