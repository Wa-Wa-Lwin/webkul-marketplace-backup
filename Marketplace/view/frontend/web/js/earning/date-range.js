/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
 /*jshint jquery:true*/
 define([
    "jquery",
    "jquery/ui",
    'mage/calendar'
], function ($) {
    'use strict';
    $.widget('mage.earningDateRange', {
        _create: function () {
            var self = this;
            $(".wk-mp-design").dateRange({
                'dateFormat':'mm/dd/yy',
                'from': {
                    'id': 'earning-from-date'
                },
                'to': {
                    'id': 'earning-to-date'
                }
            });
        }
    });
    return $.mage.earningDateRange;
});
