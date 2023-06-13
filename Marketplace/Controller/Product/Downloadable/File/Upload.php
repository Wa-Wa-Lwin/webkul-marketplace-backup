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

namespace Webkul\Marketplace\Controller\Product\Downloadable\File;

use Magento\Customer\Controller\AccountInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Helper\Data as HelperData;

/**
 * Marketplace Product Downloadable File Upload controller.
 */
class Upload extends Action implements AccountInterface
{
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    private $_fileUploaderFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var \Magento\Downloadable\Model\Link
     */
    protected $link;

    /**
     * @var \Magento\Downloadable\Model\Sample
     */
    protected $sample;

    /**
     * @var \Magento\Downloadable\Helper\File
     */
    protected $fileHelper;

    /**
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $mediaStorage;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @param \Magento\Backend\App\Action\Context               $context
     * @param \Magento\MediaStorage\Model\File\UploaderFactory  $fileUploaderFactory
     * @param HelperData                                        $helper
     * @param \Magento\Downloadable\Model\Link                  $link
     * @param \Magento\Downloadable\Model\Sample                $sample
     * @param \Magento\Downloadable\Helper\File                 $fileHelper
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $mediaStorage
     * @param \Magento\Framework\Json\Helper\Data               $jsonHelper
     */
    public function __construct(
        Context $context,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        HelperData $helper,
        \Magento\Downloadable\Model\Link $link,
        \Magento\Downloadable\Model\Sample $sample,
        \Magento\Downloadable\Helper\File $fileHelper,
        \Magento\MediaStorage\Helper\File\Storage\Database $mediaStorage,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        parent::__construct($context);
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->helper = $helper;
        $this->link = $link;
        $this->sample = $sample;
        $this->fileHelper = $fileHelper;
        $this->mediaStorage = $mediaStorage;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Marketplace Downloadable Upload file controller action
     *
     * @return json data
     */
    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            try {
                $fileType = $this->getRequest()->getParam('type');
                $destPath = '';
                if ($fileType == 'links') {
                    $destPath = $this->link->getBaseTmpPath();
                } elseif ($fileType == 'samples') {
                    $destPath = $this->sample->getBaseTmpPath();
                } elseif ($fileType == 'link_samples') {
                    $destPath = $this->link->getBaseSampleTmpPath();
                }
                $fileUploader = $this->_fileUploaderFactory->create(
                    ['fileId' => $fileType]
                );
                $resultData = $this->fileHelper->uploadFromTmp($destPath, $fileUploader);
                if (!$resultData) {
                    throw new LocalizedException('File can not be uploaded.');
                }
                if (isset($resultData['file'])) {
                    $relativePath = rtrim($destPath, '/') . '/' . ltrim($resultData['file'], '/');
                    $this->mediaStorage->saveFile($relativePath);
                }
                return $this->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode($resultData)
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Downloadable_File_Upload execute : ".$e->getMessage()
                );
                $this->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode(
                        [
                            'error' => $e->getMessage(),
                            'errorcode' => $e->getCode()
                        ]
                    )
                );
            }
        } else {
            return $this->resultRedirectFactory->create()
            ->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
