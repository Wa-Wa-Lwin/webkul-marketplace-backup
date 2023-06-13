<?php
/**
 * Webkul Software
 *
 * @category Webkul
 * @package Webkul_Marketplace
 * @author Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Model;

use Webkul\Marketplace\Api\Data\SellerFlagsInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;

class SellerFlags extends \Magento\Framework\Model\AbstractModel implements SellerFlagsInterface
{
    /**
     * Marketplace SellerFlags cache tag.
     */
    const CACHE_TAG = 'marketplace_sellerflags';

    /**
     * @var string
     */
    protected $_cacheTag = 'marketplace_sellerflags';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'marketplace_sellerflags';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Webkul\Marketplace\Model\ResourceModel\SellerFlags::class);
    }

    /**
     * Get Entity Id
     * @return string
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Set Entity Id
     * @param string $entityId
     * @return \Webkul\Marketplace\Api\Data\SellerFlagsInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get Seller Id
     * @return string
     */
    public function getSellerId()
    {
        return $this->getData(self::SELLER_ID);
    }

    /**
     * Set Seller Id
     * @param string $sellerId
     * @return \Webkul\Marketplace\Api\Data\SellerFlagsInterface
     */
    public function setSellerId($sellerId)
    {
        return $this->setData(self::SELLER_ID, $sellerId);
    }

    /**
     * Get reason
     * @return string
     */
    public function getReason()
    {
        return $this->getData(self::REASON);
    }

    /**
     * Set reason
     * @param string $reason
     * @return \Webkul\Marketplace\Api\Data\SellerFlagsInterface
     */
    public function setReason($reason)
    {
        return $this->setData(self::REASON, $reason);
    }

    /**
     * Get Name
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set Name
     * @param string $name
     * @return \Webkul\Marketplace\Api\Data\SellerFlagsInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get Email
     * @return string
     */
    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * Set Email
     * @param string $email
     * @return \Webkul\Marketplace\Api\Data\SellerFlagsInterface
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * Get CreatedAt
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set CreatedAt
     * @param string $timestamp
     * @return \Webkul\Marketplace\Api\Data\SellerFlagsInterface
     */
    public function setCreatedAt($timestamp)
    {
        return $this->setData(self::CREATED_AT, $timestamp);
    }
}
