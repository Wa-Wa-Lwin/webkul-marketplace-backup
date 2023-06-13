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

namespace Webkul\Marketplace\Model;

use Webkul\Marketplace\Api\Data\WysiwygImageInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class WysiwygImage extends AbstractModel implements WysiwygImageInterface, IdentityInterface
{
    const CACHE_TAG = 'marketplace_wysiwygimage';
    /**
     * @var string
     */
    protected $_cacheTag = 'marketplace_wysiwygimage';
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'marketplace_wysiwygimage';
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Webkul\Marketplace\Model\ResourceModel\WysiwygImage::class
        );
    }
    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getEntityId()];
    }
    /**
     * Get ID
     *
     * @return int|null
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }
    /**
     * set Id
     *
     * @param int $id
     * @return void
     */
    public function setEntityId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }
    /**
     * Get Seller Id
     *
     * @return int|null
     */
    public function getSellerId()
    {
        return $this->getData(self::SELLER_ID);
    }
    /**
     * set Seller Id
     *
     * @param int $sellerId
     * @return void
     */
    public function setSellerId($sellerId)
    {
        return $this->setData(self::SELLER_ID, $sellerId);
    }
    /**
     * Get Url
     *
     * @return string|null
     */
    public function getUrl()
    {
        return $this->getData(self::URL);
    }
    /**
     * set Url
     *
     * @param string $url
     * @return void
     */
    public function setUrl($url)
    {
        return $this->setData(self::URL, $url);
    }
    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }
    /**
     * set Name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }
    /**
     * Get Type
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }
    /**
     * set Type
     *
     * @param string $type
     * @return void
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }
    /**
     * Get File
     *
     * @return string|null
     */
    public function getFile()
    {
        return $this->getData(self::FILE);
    }
    /**
     * set File
     *
     * @param string $type
     * @return void
     */
    public function setFile($file)
    {
        return $this->setData(self::FILE, $file);
    }
}
