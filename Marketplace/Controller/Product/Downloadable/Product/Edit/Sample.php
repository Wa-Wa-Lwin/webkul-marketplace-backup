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

namespace Webkul\Marketplace\Controller\Product\Downloadable\Product\Edit;

use Magento\Downloadable\Helper\Download as DownloadableHelper;

class Sample extends \Webkul\Marketplace\Controller\Product\Edit
{
    /**
     * Seller Downloadable Product Sample action.
     */
    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            try {
                $sampleId = $this->getRequest()->getParam('id', 0);
                $productSample = $this->sample->create()->load($sampleId);
                $mageProductId = $productSample->getProductId();
                $rightseller = $helper->isRightSeller($mageProductId);
                if (!$rightseller) {
                    return $this->resultRedirectFactory->create()->setPath(
                        'marketplace/product/productlist',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                }
                $sampleTypeUrl = DownloadableHelper::LINK_TYPE_URL;
                $sampleTypeFile = DownloadableHelper::LINK_TYPE_FILE;
                $sampleUrl = '';
                $sampleType = '';
                if ($productSample->getSampleType() == $sampleTypeUrl) {
                    $sampleUrl = $productSample->getSampleUrl();
                    $sampleType = $sampleTypeUrl;
                } elseif ($productSample->getSampleType() == $sampleTypeFile) {
                    $sampleUrl = $this->fileHelper->getFilePath(
                        $this->sample->create()->getBasePath(),
                        $productSample->getSampleFile()
                    );
                    $sampleType = $sampleTypeFile;
                }
                $downloadableHelper = $this->downloadHelper;
                $downloadableHelper->setResource($sampleUrl, $sampleType);
                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader(
                        'Cache-Control',
                        'must-revalidate, post-check=0, pre-check=0',
                        true
                    )
                    ->setHeader('Pragma', 'public', true)
                    ->setHeader(
                        'Content-type',
                        $downloadableHelper->getContentType(),
                        true
                    );
                if ($downloadableHelper->getFileSize()) {
                    $this->getResponse()->setHeader(
                        'Content-Length',
                        $downloadableHelper->getFileSize()
                    );
                }
                if ($contentDisposition = $downloadableHelper->getContentDisposition()) {
                    $this->getResponse()->setHeader(
                        'Content-Disposition',
                        $contentDisposition.'; filename='.$downloadableHelper->getFilename()
                    );
                }
                $this->getResponse()->clearBody();
                $this->getResponse()->sendHeaders();
                $downloadableHelper->output();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Downloadable_Product_Edit_Sample execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
