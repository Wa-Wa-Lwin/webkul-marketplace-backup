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
namespace Webkul\Marketplace\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Webkul\Marketplace\Model\ControllersRepository;
use Magento\Framework\App\Filesystem\DirectoryList;

class MoveMediaFiles implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param \Magento\Framework\Filesystem\Io\File $file
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Io\File $file,
        \Magento\Framework\Module\Dir\Reader $reader
    ) {
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->reader = $reader;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->processDefaultImages();
    }

    /**
     * Copy Banner and Icon Images to Media
     */
    private function processDefaultImages()
    {
        $error = false;
        try {
            $this->createDirectories();
            $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $ds = "/";
            $baseModulePath = $this->reader->getModuleDir('', 'Webkul_Marketplace');
            $mediaDetails = [
                "avatar" => [
                    "view/base/web/images/avatar" => [
                        "banner-image.png",
                        "noimage.png"
                    ]
                ],
                "marketplace/banner" => [
                    "view/base/web/images/marketplace/banner" => [
                        "sell-page-banner.png"
                    ],
                    "view/base/web/images/landingpage1/banner" => [
                        "sell-page-1-hero-banner.jpg"
                    ],
                    "view/base/web/images/landingpage2/banner" => [
                        "sell-page-2-hero-banner.jpg"
                    ]
                ],
                "marketplace/icon" => [
                    "view/base/web/images/marketplace/icon" => [
                        "icon-add-products.png",
                        "icon-collect-revenues.png",
                        "icon-register-yourself.png",
                        "icon-start-selling.png"
                    ],
                    "view/base/web/images/landingpage2/icon" => [
                        "sell-page-2-setup-1.png",
                        "sell-page-2-setup-2.png",
                        "sell-page-2-setup-3.png",
                        "sell-page-2-setup-4.png",
                        "sell-page-2-setup-5.png"
                    ]
                ],
                "placeholder" => [
                    "view/base/web/images/placeholder" => [
                        "image.jpg"
                    ]
                ],
            ];

            foreach ($mediaDetails as $mediaDirectory => $imageDetails) {
                foreach ($imageDetails as $modulePath => $images) {
                    foreach ($images as $image) {
                        $path = $directory->getAbsolutePath($mediaDirectory);
                        $mediaFilePath = $path.$ds.$image;
                        $moduleFilePath = $baseModulePath.$ds.$modulePath.$ds.$image;

                        if ($this->file->fileExists($mediaFilePath)) {
                            continue;
                        }

                        if (!$this->file->fileExists($moduleFilePath)) {
                            continue;
                        }

                        $this->file->cp($moduleFilePath, $mediaFilePath);
                    }
                }
            }
        } catch (\Exception $e) {
            $error = true;
        }
    }

    /**
     * Create default directories
     */
    private function createDirectories()
    {
        $mediaDirectories = ['avatar', 'marketplace', 'marketplace/banner', 'marketplace/icon', 'placeholder'];
        foreach ($mediaDirectories as $mediaDirectory) {
            $directory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $path = $directory->getAbsolutePath($mediaDirectory);
            if (!$this->file->fileExists($path)) {
                $this->file->mkdir($path, 0777, true);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
}
