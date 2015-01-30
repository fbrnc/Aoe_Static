/**
 * Send ajax request to the Magento store in order to insert dynamic content into the
 * static page delivered from Varnish
 *
 * @author Fabrizio Branca
 */
var Aoe_Static = {

    storeId: null,
    websiteId: null,
    fullActionName: null,
    ajaxHomeUrl: null,
    currentProductId: null,

    init: function(ajaxhome_url, fullactionname, storeId, websiteId, currentproductid) {
        this.storeId = storeId;
        this.websiteId = websiteId;
        this.fullActionName = fullactionname;
        this.ajaxHomeUrl = ajaxhome_url;
        this.currentProductId = currentproductid;

        this.populatePage();
    },

    /**
     * populate page
     */
    populatePage: function() {
        this.replaceCookieContent();
        this.replaceAjaxBlocks();
        if (this.isLoggedIn()) {
            jQuery('.aoestatic_notloggedin').hide();
            jQuery('.aoestatic_loggedin').show();
        } else {
            jQuery('.aoestatic_loggedin').hide();
            jQuery('.aoestatic_notloggedin').show();
        }
    },

    /**
     * Replace cookie content
     */
    replaceCookieContent: function() {
        jQuery.each(this.getCookieContent(), function(name, value) {
            jQuery('.aoestatic_' + name).text(value);
            // console.log('Replacing ".aoestatic_' + name + '" with "' + value + '"');
        })
    },

    isLoggedIn: function() {
        var cookieValues = this.getCookieContent();
        return typeof cookieValues['customername'] != 'undefined' && cookieValues['customername'].length;
    },

    /**
     * Get info from cookies
     */
    getCookieContent: function() {
        // expected format as_[g|w<websiteId>|s<storeId>]
        var values = {};
        jQuery.each(jQuery.cookie(), function(name, value) {
            if (name.substr(0, 10) == 'aoestatic_') {
                name = name.substr(10);
                var parts = name.split('_');
                var scope = parts.splice(0, 1)[0];
                name = parts.join('_');
                if (name && scope) {
                    if (typeof values[name] == 'undefined') {
                        values[name] = {};
                    }
                    values[name][scope] = value;
                }
            }
        });

        var cookieValues = {};
        jQuery.each(values, function(name, data) {
            if (typeof data['s' + Aoe_Static.storeId] != 'undefined') {
                cookieValues[name] = data['s' + Aoe_Static.storeId];
            } else if (typeof data['w' + Aoe_Static.websiteId] != 'undefined') {
                cookieValues[name] = data['w' + Aoe_Static.websiteId];
            } else if (typeof data['g'] != 'undefined') {
                cookieValues[name] = data['g'];
            }
        });
        return cookieValues;
    },

    /**
     * Load block content from server
     */
    replaceAjaxBlocks: function() {
        jQuery(document).ready(function($) {
            var data = {
                getBlocks: {}
            };

            // add placeholders
            var counter = 0;
            $('.as-placeholder').each(function() {
                var id = $(this).attr('id');
                if (!id) {
                    // create dynamic id
                    id = 'ph_' + counter;
                    $(this).attr('id', id);
                }
                var rel = $(this).attr('rel');
                if (rel) {
                    data.getBlocks[id] = rel;
                    counter++;
                } else {
                    // console.log(this);
                    throw 'Found placeholder without rel attribute';
                }
            });

            // add current product
            /* This needs some serious refactoring anyways...
            if (typeof currentproductid !== 'undefined' && currentproductid) {
                data.currentProductId = currentproductid;
            }
            */

            // E.T. phone home
            if (typeof data.currentProductId !== 'undefined' || counter > 0) {
                $.get(
                    Aoe_Static.ajaxHomeUrl,
                    data,
                    function (response) {
                        for(var id in response.blocks) {
                            $('#' + id).html(response.blocks[id]);
                        }
                        jQuery('body').trigger('aoestatic_afterblockreplace');
                    },
                    'json'
                );
            }

        });
    }
};


