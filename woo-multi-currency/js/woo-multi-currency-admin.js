'use strict';
jQuery(document).ready(function () {
    jQuery('.vi-ui.tabular.menu .item').vi_tab({
        history: true,
        historyType: 'hash'
    });

    /*Setup tab*/
    var tabs,
        tabEvent = false,
        initialTab = 'general',
        navSelector = '.vi-ui.menu',
        navFilter = function (el) {
            // return jQuery(el).attr('href').replace(/^#/, '');
        },
        panelSelector = '.vi-ui.tab',
        panelFilter = function () {
            jQuery(panelSelector + ' a').filter(function () {
                return jQuery(navSelector + ' a[title=' + jQuery(this).attr('title') + ']').size() != 0;
            });
        };

    // Initializes plugin features
    jQuery.address.strict(false).wrap(true);

    if (jQuery.address.value() == '') {
        jQuery.address.history(false).value(initialTab).history(true);
    }

    // Address handler
    jQuery.address.init(function (event) {

        // Adds the ID in a lazy manner to prevent scrolling
        jQuery(panelSelector).attr('id', initialTab);

        panelFilter();

        // Tabs setup
        tabs = jQuery('.vi-ui.menu')
            .vi_tab({
                history: true,
                historyType: 'hash'
            })

        // Enables the plugin for all the tabs
        jQuery(navSelector + ' a').click(function (event) {
            tabEvent = true;
            // jQuery.address.value(navFilter(event.target));
            tabEvent = false;
            return true;
        });

    });


    /*Init JS input*/
    jQuery('.vi-ui.checkbox').checkbox();
    jQuery('select.vi-ui.dropdown').dropdown();
    if ( typeof jQuery.fn.select2 === 'function' ) {
        jQuery('.select2').select2();
        jQuery('.select2-multiple').select2({
            width: '100%'
        });
    }
    /*Select all and Remove all countries in Currency by country*/
    jQuery('.wmc-select-all-countries').on('click', function () {
        var selectedItems = [];
        var allOptions = jQuery(this).closest('tr').find('select');
        allOptions.find('option').each(function () {
            jQuery(this).attr('selected', true);
        });
        allOptions.trigger("change");
    });

    jQuery('.wmc-remove-all-countries').on('click', function () {
        if (confirm("Would you want to remove all countries?")) {
            var selectedItems = [];
            var allOptions = jQuery(this).closest('tr').find('select');
            allOptions.find('option').each(function () {
                jQuery(this).removeAttr('selected', true);
            });
            allOptions.trigger("change");
        }
    });

    // jQuery("#IncludeFieldsMulti").select2("val", selectedItems);

    function wmcTrim(value) {
        return String(value == null ? '' : value).trim();
    }

    function wmcFinalRate(row) {
        let rate = parseFloat(row.find('.wmc-currency-rate').val());
        if (isNaN(rate)) {
            rate = 0;
        }
        let feeRaw = wmcTrim(row.find('.wmc-currency-rate-fee').val());
        let fee = parseFloat(feeRaw);
        if (isNaN(fee)) {
            fee = 0;
        }
        // Free uses fixed fee only (mirrors PHP get_list_currencies).
        let feeSet = feeRaw !== '' && feeRaw !== '0';
        return feeSet ? rate + fee : rate;
    }

    function wmcDecimals(row) {
        let raw = wmcTrim(row.find('input[name="woo_multi_currency_params[currency_decimals][]"]').val());
        if (raw === '') {
            return 0;
        }
        let decimals = parseInt(raw, 10);
        if (isNaN(decimals) || decimals < 0) {
            return 0;
        }
        return decimals;
    }

    function wmcRoundPrice(price, decimals) {
        const negative = price < 0;
        let value = Math.abs(parseFloat(price));
        if (isNaN(value)) {
            value = 0;
        }
        const factor = Math.pow(10, decimals);
        const rounded = Math.round(value * factor) / factor;
        return negative ? -rounded : rounded;
    }

    function wmcMinDecimals(rate) {
        return Math.min(30, Math.max(0, Math.ceil(-Math.log10(rate))));
    }

    function wmcSuggestedDecimals(rate) {
        let parsed = parseFloat(rate);
        if (isNaN(parsed) || parsed <= 0) {
            return null;
        }
        return Math.min(12, wmcMinDecimals(parsed));
    }

    function wmcUnitMessage(currency, reason, minDecimals) {
        if (reason === 'rate') {
            return String(wmcParams.msgRate).replace('%s', currency);
        }
        return String(wmcParams.msgDecimals).replace('%1$s', currency).replace('%2$d', String(minDecimals));
    }

    function wmcShowUnitWarning($form, messages) {
        let $box = $form.find('.wmc-currency-unit-warning').first();
        if (!$box.length) {
            $box = jQuery('<div class="vi-ui red message wmc-currency-unit-warning"><ul class="list"></ul></div>');
            $form.find('.wmc-currency-options').first().after($box);
        }
        let $list = $box.find('ul').empty();
        messages.forEach(function (message) {
            jQuery('<li></li>').text(message).appendTo($list);
        });
    }

    /*Save Submit button — validate unit rate/decimals before save (Pro-parity).*/
    jQuery('.woo-multi-currency form').on('submit', function () {
        let $form = jQuery(this);
        let unitMessages = [];
        $form.find('.wmc-currency-options .wmc-currency-data').each(function () {
            let row = jQuery(this);
            let currency = row.find('select[name="woo_multi_currency_params[currency][]"]').val();
            if (!currency) {
                return;
            }
            let finalRate = wmcFinalRate(row);
            let decimals = wmcDecimals(row);
            let result = wmcRoundPrice(finalRate, decimals);
            if (finalRate <= 0 || result <= 0) {
                if (finalRate <= 0) {
                    unitMessages.push(wmcUnitMessage(currency, 'rate', 0));
                } else {
                    unitMessages.push(wmcUnitMessage(currency, 'decimals', wmcMinDecimals(finalRate)));
                }
            }
        });
        if (unitMessages.length) {
            wmcShowUnitWarning($form, unitMessages);
            jQuery('.wmc-submit').removeClass('loading');
            return false;
        }
        $form.find('.wmc-currency-unit-warning').remove();
        jQuery('.wmc-submit').addClass('loading');
    });

    /*Color picker*/
    jQuery('.color-picker').iris({
        change: function (event, ui) {
            jQuery(this).parent().find('.color-picker').css({backgroundColor: ui.color.toString()});
            var ele = jQuery(this).data('ele');
            if (ele == 'highlight') {
                jQuery('#message-purchased').find('a').css({'color': ui.color.toString()});
            } else if (ele == 'textcolor') {
                jQuery('#message-purchased').css({'color': ui.color.toString()});
            } else {
                jQuery('#message-purchased').css({backgroundColor: ui.color.toString()});
            }
        },
        hide: true,
        border: true
    }).click(function () {
        jQuery('.iris-picker').hide();
        jQuery(this).closest('td').find('.iris-picker').show();
    });

    jQuery('body').click(function () {
        jQuery('.iris-picker').hide();
    });
    jQuery('.color-picker').click(function (event) {
        event.stopPropagation();
    });
    /*Update all rates*/
    jQuery('.wmc-update-rates').on('click', function () {
        var original_currency = jQuery('.wmc-currency-data input[name="woo_multi_currency_params[currency_default]"]:checked').val();
        var other_currencies = [];
        jQuery('.wmc-currency-options').find('input[name="woo_multi_currency_params[currency_default]"]').each(function () {
            if (original_currency != jQuery(this).val()) {
                other_currencies.push(jQuery(this).val());
            }
        });
        jQuery(this).addClass('loading');
        exchange_rate(original_currency, other_currencies);
    });

    /*Process Currency Options*/
    remove_currency();

    function insert_currency() {
        jQuery('.vi-ui.checkbox').unbind();
        jQuery('.vi-ui.checkbox').checkbox();

        jQuery('.wmc-add-currency').unbind();
        jQuery('.wmc-add-currency').on('click', function () {
            if (jQuery('.wmc-currency-data').length >= 2) {
                alert('Please upgrade to Premium version');
                return;
            }
            jQuery('.wmc-currency-data').last().find('select.select2').select2('destroy');
            var new_row = jQuery('.wmc-currency-data').last().clone();
            jQuery('.wmc-currency-data').last().find('select.select2').select2();
            new_row.find('input[name="woo_multi_currency_params[currency_default]"]').attr('checked', false);
            jQuery(new_row).appendTo('.wmc-currency-options tbody');
            remove_currency();
            jQuery('.wmc-currency-data').last().find('select.select2').select2().change();

        });

        jQuery('select[name="woo_multi_currency_params[currency][]"]').on('change', function () {
            var val = jQuery(this).val();
            jQuery(this).closest('tr').find('input[name="woo_multi_currency_params[currency_default]"]').val(val);
            jQuery(this).closest('tr').removeAttr('class').addClass('wmc-currency-data ' + val + '-currency');
        });

        jQuery('.wmc-currency-options tbody').sortable();

        /*Change currency default*/
        jQuery('input[name="woo_multi_currency_params[currency_default]"]').unbind('change');
        jQuery('input[name="woo_multi_currency_params[currency_default]"]').on('change', function () {
            jQuery('.wmc-currency-options').find('input[name="woo_multi_currency_params[currency_rate][]"]').removeAttr('readonly');
            jQuery('.wmc-currency-options').find('input[name="woo_multi_currency_params[currency_rate_fee][]"]').removeAttr('readonly');
            jQuery(this).closest('tr').find('input[name="woo_multi_currency_params[currency_rate][]"]').val(1).attr('readonly', true);
            jQuery(this).closest('tr').find('input[name="woo_multi_currency_params[currency_rate_fee][]"]').val(0).attr('readonly', true);
            var original_currency = jQuery(this).val();
            var other_currencies = [];
            jQuery('.wmc-currency-options').find('input[name="woo_multi_currency_params[currency_default]"]').each(function () {
                if (original_currency != jQuery(this).val()) {
                    other_currencies.push(jQuery(this).val());
                }
            });
            exchange_rate(original_currency, other_currencies);
        });

        /*Update single rate*/
        jQuery('.wmc-update-rate').on('click', function () {

            var original_currency = jQuery('.wmc-currency-data input[name="woo_multi_currency_params[currency_default]"]:checked').val();
            var other_currencies = jQuery(this).closest('tr').find('input[name="woo_multi_currency_params[currency_default]"]').val();

            if (original_currency != other_currencies) {
                jQuery(this).addClass('loading');
                exchange_rate(original_currency, other_currencies);
            }
        });

    }

    function remove_currency() {
        jQuery('.wmc-remove-currency').unbind();
        insert_currency();
        jQuery('.wmc-remove-currency').on('click', function () {
            if (confirm("Would you want to remove this currency?")) {
                if (jQuery('.wmc-currency-options tbody tr').length > 1) {
                    var tr = jQuery(this).closest('tr').remove();
                }
            } else {

            }
        });
    }

    function exchange_rate(original_currency, other_currencies) {
        if (original_currency && other_currencies) {
            var str_data = 'original_price=' + original_currency + '&other_currencies=' + other_currencies;

            jQuery.ajax({
                type: 'POST',
                // data: 'action=woomulticurrency_exchange&' + str_data,
                data: {
                    action: 'woomulticurrency_exchange',
                    nonce: wmcParams.nonce,
                    original_price: original_currency,
                    other_currencies: other_currencies,
                },
                url: ajaxurl,
                success: function (obj) {
                    jQuery.each(obj, function (currency, rate) {
                        if (jQuery('tr.' + currency + '-currency').length > 0) {
                            let $row = jQuery('tr.' + currency + '-currency');
                            $row.find('input[name="woo_multi_currency_params[currency_rate][]"]').val(rate);
                            let suggested = wmcSuggestedDecimals(rate);
                            if (suggested !== null) {
                                $row.find('input[name="woo_multi_currency_params[currency_decimals][]"]').val(suggested);
                            }
                        }
                        jQuery('.woo-multi-currency').find('.loading').removeClass('loading');
                    });
                },
                error: function (html) {
                }
            })
        } else {
            return false;
        }

    }


    /*Checkout currency*/
    jQuery('input[name="woo_multi_currency_params[checkout_currency]"]').on('change', function () {
        jQuery('select[name="woo_multi_currency_params[checkout_currency_args][]"]').removeAttr('disabled');
        jQuery(this).closest('tr').find('select[name="woo_multi_currency_params[checkout_currency_args][]"]').attr('disabled', 'disabled').find('option').removeAttr('selected').last().attr('selected', true);
    })
});