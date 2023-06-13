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

namespace Webkul\Marketplace\Model\Rewrite\Backend\View\Result;

class Redirect extends \Magento\Backend\Model\View\Result\Redirect
{
    /**
     * @var string
     */
    public $_path;

    /**
     * Set Path
     *
     * @param string $path
     * @param array $params
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function setPath($path, array $params = [])
    {
        $this->_path = $path;
        return parent::setPath($path, $params);
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }
}
