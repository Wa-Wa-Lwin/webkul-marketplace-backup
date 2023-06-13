/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
define([
    'Magento_Ui/js/form/components/html'
], function (Html) {
    'use strict';

    return Html.extend({
        defaults: {
            isConfigurable: false
        },

        /**
         * Updates component visibility state.
         *
         * @param {Boolean} variationsEmpty
         * @returns {Boolean}
         */
        updateVisibility: function (variationsEmpty) {
            var isVisible = this.isConfigurable || !variationsEmpty;

            this.visible(isVisible);

            return isVisible;
        }
    });
});
