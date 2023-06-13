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
namespace Webkul\Marketplace\Controller\Product\Attribute;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\DB\Select as DBSelect;
use Magento\Framework\Encryption\EncryptorInterface;
use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Magento\Catalog\Model\Product\Url as ProductUrl;
use Magento\Eav\Model\Entity as EntityModel;

/**
 * Webkul Marketplace Product Attribute Save controller.
 */
class Save extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var AttributeCollection
     */
    protected $attributeCollection;

    /**
     * @var EavAttribute
     */
    protected $eavAttribute;

    /**
     * @var ProductUrl
     */
    protected $productUrl;

    /**
     * @var EntityModel
     */
    protected $entityModel;

    /**
     * @param Context          $context
     * @param Session          $customerSession
     * @param FormKeyValidator $formKeyValidator
     * @param PageFactory      $resultPageFactory
     * @param HelperData       $helper
     * @param AttributeCollection       $attributeCollection
     * @param EavAttribute       $eavAttribute
     * @param ProductUrl       $productUrl
     * @param EntityModel       $entityModel
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        FormKeyValidator $formKeyValidator,
        PageFactory $resultPageFactory,
        EncryptorInterface $encryptor,
        HelperData $helper,
        AttributeCollection $attributeCollection,
        EavAttribute $eavAttribute,
        ProductUrl $productUrl,
        EntityModel $entityModel
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->resultPageFactory = $resultPageFactory;
        $this->encryptor = $encryptor;
        $this->helper = $helper;
        $this->attributeCollection = $attributeCollection;
        $this->eavAttribute = $eavAttribute;
        $this->productUrl = $productUrl;
        $this->entityModel = $entityModel;
        parent::__construct(
            $context
        );
    }

    /**
     * Retrieve customer session object.
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }

    /**
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            try {
                if ($this->getRequest()->isPost()) {
                    if (!$this->_formKeyValidator->validate($this->getRequest())) {
                        return $this->resultRedirectFactory->create()->setPath(
                            '*/*/new',
                            ['_secure' => $this->getRequest()->isSecure()]
                        );
                    }

                    $wholedata = $this->getRequest()->getParams();

                    $collection = $this->attributeCollection;
                    $collection->getSelect()->reset(DBSelect::COLUMNS);
                    $collection->addFieldToSelect("attribute_code");
                    $allattrCodes = [];
                    foreach ($collection as $attribute) {
                        $allattrCodes[] = $attribute->getAttributeCode();
                    }

                    if (in_array($wholedata['attribute_code'], $allattrCodes)) {
                        $this->messageManager->addError(
                            __('Attribute Code already exists')
                        );

                        return $this->resultRedirectFactory->create()->setPath(
                            '*/*/new',
                            ['_secure' => $this->getRequest()->isSecure()]
                        );
                    } else {
                        if (array_key_exists('attroptions', $wholedata)) {
                            foreach ($wholedata['attroptions'] as $c) {
                                $data1['.'.$c['admin'].'.'] = [$c['admin'],$c['store']];
                            }
                        } else {
                            $data1 = [];
                        }
                        if (empty($wholedata['attribute_code'])) {
                            $wholedata['attribute_code'] = $this->generateAttrCode(
                                $wholedata['attribute_label']
                            );
                        }
                        if (!empty($wholedata['attribute_code'])) {
                            $validatorRegx = new \Zend_Validate_Regex(
                                ['pattern' => '/^[a-z][a-z_0-9]{0,30}$/']
                            );
                            if (!$validatorRegx->isValid($wholedata['attribute_code'])) {
                                $this->messageManager->addError(
                                    __(
                                        'Attribute code "%1" is invalid. Please use only letters (a-z), ' .
                                        'numbers (0-9) or underscore(_) in this field, first character should be a letter.',
                                        $wholedata['attribute_code']
                                    )
                                );
                                return $this->resultRedirectFactory->create()->setPath(
                                    '*/*/new',
                                    ['_secure' => $this->getRequest()->isSecure()]
                                );
                            }
                        }

                        $attributeData = [
                            'attribute_code' => $wholedata['attribute_code'],
                            'is_global' => '1',
                            'frontend_input' => $wholedata['frontend_input'],
                            'default_value_text' => '',
                            'default_value_yesno' => '0',
                            'default_value_date' => '',
                            'default_value_textarea' => '',
                            'is_unique' => '0',
                            'is_required' => $wholedata['val_required'],
                            'apply_to' => '0',
                            'is_configurable' => '1',
                            'is_searchable' => '0',
                            'is_visible_in_advanced_search' => '1',
                            'is_comparable' => '0',
                            'is_used_for_price_rules' => '0',
                            'is_wysiwyg_enabled' => '0',
                            'is_html_allowed_on_front' => '1',
                            'is_visible_on_front' => '0',
                            'used_in_product_listing' => '0',
                            'used_for_sort_by' => '0',
                            'frontend_label' => [$wholedata['attribute_label']],
                        ];
                        $model = $this->eavAttribute;
                        if (!isset($attributeData['is_configurable'])) {
                            $attributeData['is_configurable'] = 0;
                        }
                        if (!isset($attributeData['is_filterable'])) {
                            $attributeData['is_filterable'] = 0;
                        }
                        if (!isset($attributeData['is_filterable_in_search'])) {
                            $attributeData['is_filterable_in_search'] = 0;
                        }
                        if (($model->getIsUserDefined()===null) || $model->getIsUserDefined() != 0) {
                            $attributeData['backend_type'] = $model->getBackendTypeByInput(
                                $attributeData['frontend_input']
                            );
                        }
                        $defaultValueField = $model->getDefaultValueByInput(
                            $attributeData['frontend_input']
                        );
                        if ($defaultValueField) {
                            $attributeData['default_value'] = $this->getRequest()->getParam(
                                $defaultValueField
                            );
                        }
                        $model->addData($attributeData);
                        $data['option']['value'] = $data1;
                        if ($wholedata['frontend_input'] == 'select') {
                            $model->addData($data);
                        }
                        $entityTypeID = $this->entityModel
                        ->setType('catalog_product')
                        ->getTypeId();
                        $model->setEntityTypeId($entityTypeID);
                        $model->setIsUserDefined(1);
                        $model->save();
                        $this->messageManager->addSuccess(
                            __('Attribute Created Successfully')
                        );

                        return $this->resultRedirectFactory->create()->setPath(
                            '*/*/new',
                            ['_secure' => $this->getRequest()->isSecure()]
                        );
                    }
                }

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/new',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Attribute_Save execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/new',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Attribute_Save execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/new',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }

    /**
     * Generate attribute code from attribute label
     *
     * @param string $attributeLabel
     * @return string
     */
    protected function generateAttrCode($attributeLabel)
    {
        $attributeLabelFormatUrlKey = $this->productUrl->formatUrlKey($attributeLabel);
        $attributeCode = substr(
            preg_replace('/[^a-z_0-9]/', '_', $attributeLabelFormatUrlKey),
            0,
            30
        );
        $validatorAttrCode = new \Zend_Validate_Regex(
            ['pattern' => '/^[a-z][a-z_0-9]{0,29}[a-z0-9]$/']
        );
        if (!$validatorAttrCode->isValid($attributeCode)) {
            $attributeCode = 'attr_' . ($attributeCode ?: substr($this->encryptor->hash(time()), 0, 8));
        }
        return $attributeCode;
    }
}
