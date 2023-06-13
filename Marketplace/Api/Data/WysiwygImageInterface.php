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

namespace Webkul\Marketplace\Api\Data;

interface WysiwygImageInterface
{
    const ENTITY_ID   = 'entity_id';
    const SELLER_ID   = 'seller_id';
    const URL         = 'url';
    const NAME        = 'name';
    const TYPE        = 'type';
    const FILE        = 'file';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getEntityId();

    /**
     * Get Seller Id
     *
     * @return int|null
     */
    public function getSellerId();

    /**
     * Get Url
     *
     * @return string|null
     */
    public function getUrl();

    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Get Type
     *
     * @return string|null
     */
    public function getType();

    /**
     * Get File
     *
     * @return string|null
     */
    public function getFile();

    /**
     * Set ID
     *
     * @return int|null
     */
    public function setEntityId($id);

    /**
     * Set Seller Id
     *
     * @return int|null
     */
    public function setSellerId($sellerId);

    /**
     * Set Url
     *
     * @return string|null
     */
    public function setUrl($url);

    /**
     * Set Name
     *
     * @return string|null
     */
    public function setName($name);

    /**
     * Set Type
     *
     * @return string|null
     */
    public function setType($type);

    /**
     * Set File
     *
     * @return string|null
     */
    public function setFile($file);
}
