/**
 * Send ajax request to the Magento store in order to insert dynamic content into the
 * static page delivered from Varnish
 *
 * @author Fabrizio Branca
 * @author Bastian Ike
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

        this.populatePage(false);
    },

    /**
     * populate page
     * @param {Boolean} avoidReload
     */
    populatePage: function(avoidReload) {
        this.replaceCookieContent();
        this.replaceAjaxBlocks(avoidReload);
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
        jQuery('body').trigger('aoestatic_beforecookiereplace');
        jQuery.each(this.getCookieContent(), function(name, value) {
            jQuery('.aoestatic_' + name).text(value);
            // console.log('Replacing ".aoestatic_' + name + '" with "' + value + '"');
        });
        jQuery('body').trigger('aoestatic_aftercookiereplace');
    },

    isLoggedIn: function() {
        var cookieValues = this.getCookieContent();
        //return typeof cookieValues['customername'] != 'undefined' && cookieValues['customername'].length;
        return typeof cookieValues['isloggedin'] != 'undefined' && cookieValues['isloggedin'] == 1;
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
     * @param {Boolean} avoidReload
     */
    replaceAjaxBlocks: function(avoidReload) {
        jQuery(function($) {
            var data = {
                getBlocks: {},
                currentProductId: Aoe_Static.currentProductId
            };

            // add placeholders
            var counter = 0;

            var $placeholder = $('.as-placeholder').each(function() {
                var id = $(this).attr('id');
                if (!id) {
                    // create dynamic id
                    id = 'ph_' + counter;
                    $(this).attr('id', id);
                }
                var rel = $(this).attr('rel');
                if (rel) {
                    var dataAttribute = $(this).attr('data-aoestatic');
                    if (!dataAttribute || dataAttribute !== 'skip-localstorage') {
                        var localStorageKey = Aoe_Static._getLocalStorageKey(rel);
                        if (localStorage.getItem(localStorageKey)) {
                            Aoe_Static._replaceBlock(id, localStorage.getItem(localStorageKey));
                        }
                    }
                    data.getBlocks[id] = rel;
                    counter++;
                } else {
                    // console.log(this);
                    throw 'Found placeholder without rel attribute';
                }
            });
            jQuery('body').trigger('aoestatic_afterlocalblockreplace');

            // avoid E.T. phone home if avoidReload option is set and no block to initialize were found
            if (avoidReload && !$placeholder.filter(':not(.initialized)').length) return;

            // E.T. phone home, get blocks and pending flash-messages
            $.get(
              Aoe_Static.ajaxHomeUrl,
              data,
              function (response) {
                  for(var id in response.blocks) {
                      Aoe_Static._replaceBlock(id, response.blocks[id]);
                      // try to save in localStorage if allowed (f.e. not allowed in private mode on iOS)
                      try {
                          localStorage.setItem(
                              Aoe_Static._getLocalStorageKey(data.getBlocks[id]),
                              response.blocks[id]
                          );
                      } catch(e) {}
                  }
                  jQuery('body').trigger('aoestatic_afterblockreplace', response);
              },
              'json'
            );
        });
    },

    _replaceBlock: function (id, content) {
        var $block = jQuery('#' + id);
        $block.html(content).addClass('initialized');
    },

    _getLocalStorageKey: function(code) {
        return 'aoe_static_blocks_' + Aoe_Static.websiteId + '_' + Aoe_Static.storeId + '_' + code;
    }
};
