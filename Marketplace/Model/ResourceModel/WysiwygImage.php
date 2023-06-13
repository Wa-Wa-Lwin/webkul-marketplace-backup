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

namespace Webkul\Marketplace\Model\ResourceModel;

class WysiwygImage extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
     /**
      * Initialize resource model.
      */
    protected function _construct()
    {
        $this->_init('wk_mp_wysiwyg_image', 'entity_id');
    }
}
