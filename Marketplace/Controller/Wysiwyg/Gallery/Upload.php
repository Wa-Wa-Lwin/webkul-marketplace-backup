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

namespace Webkul\Marketplace\Controller\Wysiwyg\Gallery;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Api\Data\WysiwygImageInterfaceFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;

/**
 * Marketplace Wysiwyg Image Upload controller.
 */
class Upload extends Action
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $mediaDirectory;

    /**
     * File Uploader factory.
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $fileUploaderFactory;
    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var WysiwygImageInterfaceFactory
     */
    protected $wysiwygImage;
    /**
     * @var MpHelper
     */
    protected $mpHelper;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        StoreManagerInterface $storeManager,
        WysiwygImageInterfaceFactory $wysiwygImage,
        MpHelper $mpHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(
            DirectoryList::MEDIA
        );
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->storeManager = $storeManager;
        $this->wysiwygImage = $wysiwygImage;
        $this->mpHelper = $mpHelper;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $helper = $this->mpHelper;
        $isPartner = $helper->isSeller();
        $sellerId = $helper->getCustomerId();
        try {
            $target = $this->mediaDirectory->getAbsolutePath(
                'tmp/desc'
            );
            $fileUploader = $this->fileUploaderFactory->create(
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
            $resultData['url'] = $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ) . 'tmp/desc' . '/' . ltrim(str_replace('\\', '/', $resultData['file']), '/');
            $resultData['file'] = $resultData['file'] . '.tmp';
            if ($isPartner == 1) {
                $checkVal = $this->saveImageDesc($sellerId, $resultData['url'], $resultData['file']);
            }
            $this->getResponse()->representJson(
                $this->jsonHelper->jsonEncode($resultData)
            );
        } catch (\Exception $e) {
            $helper->logDataInLogger(
                "Controller_Wysiwyg_Gallery_Upload execute : ".$e->getMessage()
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
    }
    /**
     * saveImageDesc function
     *
     * @param int $sellerId
     * @param string $imageUrl
     * @param string $imageName
     * @return bool
     */
    public function saveImageDesc($sellerId, $imageUrl, $imageName)
    {
        try {
            $imageinfo = getimagesize($imageUrl);
            $file = explode("desc", $imageUrl);
            $nameArray = explode("/", $imageName);
            $name = explode(".tmp", $nameArray[count($nameArray)-1])[0];
            $descImage = $this->wysiwygImage->create();
            $descImage->setSellerId($sellerId);
            $descImage->setUrl($imageUrl);
            $descImage->setName($name);
            $descImage->setFile($file[1]);
            $descImage->setType($imageinfo["mime"]);
            $descImage->save();
            return 1;
        } catch (\Exception $e) {
            $this->mpHelper->logDataInLogger(
                "Controller_Wysiwyg_Gallery_Upload saveImageDesc : ".$e->getMessage()
            );
            return 0;
        }
    }
}
