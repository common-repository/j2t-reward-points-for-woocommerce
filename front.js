
console.log('J2T Points & Rewards FRONT');

(function( $ ) {
    
    'use strict';

    $( document ).ready(
        function() {



            function _j2tPointValueFormat(pointValue) {
                var totalPoint = j2tProcessJsMath(pointValue);
                if (!Number.isInteger(pointValue)) {
                    totalPoint = parseFloat(pointValue);
                    totalPoint = totalPoint.toFixed(j2tNumberOfDecimals);
                }
            
                var htmlPoint = totalPoint.toString().replace('.', j2tDecimalSeparator);
                if(j2tThousandSeparator.length > 0) {
                    htmlPoint = _j2tAddPointThousandSep(htmlPoint);
                }
                return htmlPoint;
            }

            function _j2tAddPointThousandSep(n){

                var rx=  /(\d+)(\d{3})/;
                return String(n).replace(/^\d+/, function(w){
                    while(rx.test(w)){
                        w= w.replace(rx, '$1'+j2tThousandSeparator+'$2');
                    }
                    return w;
                });
            
            }

            var currentVariation = null;

            function calcTotalPrice(variation = null) {
                if (current_product_type  == 'simple') {
                    var qty = Math.max($('.qty').val(), 1);
                    var total = (default_current_product_price * qty) * default_variable_conv_rate ;
                    $('span.point-value-inline').html(_j2tPointValueFormat(total));
                } else {
                    if (variation) {
                        currentVariation = variation;
                    }
                    if (!currentVariation) {
                        var $price = $('#product-price');
                        if ($price && $price.length) {
                            currentVariation = {
                                display_price: $price.val()
                            };
                        }
                    }
                    if (currentVariation) {
                        var qty = Math.max($('.qty').val(), 1);
                        var total = (currentVariation.display_price * qty) * default_variable_conv_rate ;
                        $('span.point-value-inline.configured').html(_j2tPointValueFormat(total));
    
                    }
                }
            }

            $('form.variations_form').on('found_variation', function(e, variation) {
                calcTotalPrice(variation);
            });
            $('.qty').on('input change', function() {
                calcTotalPrice();
            });
            
            /*This is code for the loader*/
            var block = function ($node) {
                if (!is_blocked($node)) {
                    $node.addClass('processing').block(
                            {
                                message: null,
                                overlayCSS: {
                                    background: '#fff',
                                    opacity: 0.6
                                }
                            }
                    );
                }
            };
            var is_blocked = function ($node) {
                return $node.is('.processing') || $node.parents('.processing').length;
            };
            var unblock = function ($node) {
                $node.removeClass('processing').unblock();
            };
            
            $( document ).on(
                'click',
                '#j2t_points_apply',
                function(){
                        var userId = $( this ).data( 'id' );

                        var message = ''; var html = '';

                        var pointsValue = $( '#reward_points' ).val();
                        var removePoints = $( '#remove_points' ).val();
                        
                        if ( (pointsValue !== 'undefined' && pointsValue !== '' && pointsValue !== null && pointsValue > 0 ) || removePoints == 1) {

                            block($('.woocommerce-cart-form'));
                            block($('.woocommerce-checkout'));

                            var data = {
                                action: 'apply_points_on_cart',
                                userId: userId,
                                //userPoint:userPoint,
                                pointsValue: pointsValue,
                                removePoints: removePoints,
                                j2trewardpoints_nonce: j2t_rewardpoints.j2trewardpoints_nonce,
                            };
                            
                            console.log(data);

                            

                            $.ajax(
                                    {
                                        url: j2t_rewardpoints.ajaxurl,
                                        type: "POST",
                                        data: data,
                                        dataType: 'json',
                                        success: function (response)
                                        {
                                            if (response.result == true) {
                                                message = response.message;
                                                html = message;
                                            } else {
                                                message = response.message;
                                                html = message;
                                            }
                                            console.log(html);
                                        },
                                        complete: function () {
                                            unblock($('.woocommerce-cart-form'));
                                            unblock($('.woocommerce-checkout'));
                                            location.reload();
                                        }
                                    }
                            );

                        }
                }
            );
            $( document ).on(
                'click',
                '#j2t_points_remove',
                function(){
                        var userId = $( this ).data( 'id' );
                        var message = ''; var html = '';
                        var removePoints = $( '#remove_points' ).val();
                        if ( removePoints == 1) {

                            block($('.woocommerce-cart-form'));
                            block($('.woocommerce-checkout'));

                            var data = {
                                action: 'apply_points_on_cart',
                                userId: userId,
                                removePoints: removePoints,
                                j2trewardpoints_nonce: j2t_rewardpoints.j2trewardpoints_nonce,
                            };
                            
                            console.log(data);

                            $.ajax(
                                    {
                                        url: j2t_rewardpoints.ajaxurl,
                                        type: "POST",
                                        data: data,
                                        dataType: 'json',
                                        success: function (response)
                                        {
                                            if (response.result == true) {
                                                message = response.message;
                                                html = message;
                                            } else {
                                                message = response.message;
                                                html = message;
                                            }
                                            console.log(html);
                                        },
                                        complete: function () {
                                            unblock($('.woocommerce-cart-form'));
                                            unblock($('.woocommerce-checkout'));
                                            location.reload();
                                        }
                                    }
                            );

                        }
                }
            );
        }
    );


})( jQuery );