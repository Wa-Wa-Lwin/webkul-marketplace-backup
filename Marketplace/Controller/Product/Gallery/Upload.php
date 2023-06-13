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

namespace Webkul\Marketplace\Controller\Product\Gallery;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Marketplace Product Image Upload controller.
 */
class Upload extends Action
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;

    /**
     * File Uploader factory.
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var MediaConfig
     */
    protected $mediaConfig;

    /**
     * @var JsonHelper
     */
    protected $jsonHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param HelperData  $helper
     * @param MediaConfig  $mediaConfig
     * @param JsonHelper  $jsonHelper
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        HelperData $helper,
        MediaConfig $mediaConfig,
        JsonHelper $jsonHelper
    ) {
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(
            DirectoryList::MEDIA
        );
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->helper = $helper;
        $this->mediaConfig = $mediaConfig;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            try {
                $target = $this->_mediaDirectory->getAbsolutePath(
                    $this->mediaConfig->getBaseTmpMediaPath()
                );
                $fileUploader = $this->_fileUploaderFactory->create(
                    ['fileId' => 'image']
                );
                $fileUploader->setAllowedExtensions(
                    ['gif', 'jpg', 'png', 'jpeg']
                );
                $fileUploader->setFilesDispersion(true);
                $fileUploader->setAllowRenameFiles(true);
                $resultData = $fileUploader->save($target);
                unset($resultData['tmp_name']);
                unset($resultData['path']);
                $resultData['url'] = $this->mediaConfig->getTmpMediaUrl($resultData['file']);
                $resultData['file'] = $resultData['file'].'.tmp';
                $this->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode($resultData)
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Gallery_Upload execute : ".$e->getMessage()
                );
                $this->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode(
                        [
                            'error' => $e->getMessage(),
                            'errorcode' => $e->getCode(),
                        ]
                    )
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
