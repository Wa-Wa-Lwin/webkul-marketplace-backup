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
namespace Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab;

class Commission extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    const COMM_TEMPLATE = 'customer/commission.phtml';

    /**
     * @param \Magento\Framework\Registry               $registry
     * @param \Magento\Backend\Block\Widget\Context     $context
     * @param \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit
     * @param array                                     $data
     * @param \Magento\Framework\Pricing\Helper\Data    $pricingHelper
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Widget\Context $context,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        array $data = [],
        \Magento\Framework\Pricing\Helper\Data $pricingHelper = null
    ) {
        $this->_coreRegistry = $registry;
        $this->customerEdit = $customerEdit;
        $this->pricingHelper = $pricingHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Magento\Framework\Pricing\Helper\Data::class);
        parent::__construct($context, $data);
    }

    /**
     * Set template to itself.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::COMM_TEMPLATE);
        }

        return $this;
    }

    public function getCommission()
    {
        $rowcom = $this->customerEdit->getSalesPartnerCollection()
                    ->addFieldToFilter(
                        'commission_status',
                        1
                    )
                    ->addFieldToSelect(
                        'commission_rate'
                    )
                    ->getFirstItem()
                    ->getCommissionRate();
        if (is_null($rowcom)) {
            $rowcom = $this->customerEdit->getConfigCommissionRate();
        }
        $tsale = 0;
        $tcomm = 0;
        $tact = 0;
        $collection1 = $this->customerEdit->getSalesListCollection();
        foreach ($collection1 as $key) {
            $tsale += $key->getTotalAmount();
            $tcomm += $key->getTotalCommission();
            $tact += $key->getActualSellerAmount();
        }

        return [
            'total_sale' => $tsale,
            'total_comm' => $tcomm,
            'actual_seller_amt' => $tact,
            'current_val' => $rowcom,
        ];
    }

    public function getCurrencySymbol()
    {
        $currencySymbol = $this->customerEdit->getCurrencySymbol();

        return $currencySymbol;
    }

    public function getCurrencyPrice($price = 0)
    {
        return $this->pricingHelper->currency($price, true, false);
    }
}
