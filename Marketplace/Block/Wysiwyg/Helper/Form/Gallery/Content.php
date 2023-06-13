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

namespace Webkul\Marketplace\Block\Wysiwyg\Helper\Form\Gallery;

use Webkul\Marketplace\Api\Data\WysiwygImageInterfaceFactory;

class Content extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\File\Size
     */
    protected $fileSize;

    /**
     * @var WysiwygImageInterfaceFactory
     */
    protected $wysiwygImage;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Backend\Block\Template\Context     $context
     * @param \Magento\Framework\File\Size                $fileSize
     * @param WysiwygImageInterfaceFactory                $wysiwygImage
     * @param \Webkul\Marketplace\Helper\Data             $helper
     * @param array                                       $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\File\Size $fileSize,
        WysiwygImageInterfaceFactory $wysiwygImage,
        \Webkul\Marketplace\Helper\Data $helper,
        array $data = []
    ) {
        $this->_fileSizeService = $fileSize;
        $this->wysiwygImage = $wysiwygImage;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }
    /**
     * @return \Magento\Framework\File\Size
     */
    public function getFileSizeService()
    {
        return $this->_fileSizeService;
    }
    /**
     * saveImageDesc function
     * @return \Webkul\Marketplace\Model\WysiwygImage
     */
    public function saveImageDesc()
    {
        $sellerId = $this->helper->getCustomerId();
        $wysiwygImage = $this->wysiwygImage->create()
                    ->getCollection()
                    ->addFieldToFilter("seller_id", $sellerId);
        return $wysiwygImage;
    }
}
