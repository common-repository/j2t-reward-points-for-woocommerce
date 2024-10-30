
<div class="available-point-scheme">
    <?php if($referral_registration_points):?>
        <div class="points-account-info points-child-registration-info">
            <?php echo sprintf(__('For any referred friend\'s registration, you earn: <strong>%d points</strong>.', 'j2t-reward-points-for-woocommerce'), $referral_registration_points);?>
        </div>
    <?php endif;?>
    <?php if($referrer_registration_points):?>
        <div class="points-account-info points-parent-registration-info">
            <?php echo sprintf(__('For any referred friend\'s registration, you friend earn: <strong>%d points</strong>.', 'j2t-reward-points-for-woocommerce'), $referrer_registration_points);?>
        </div>                
    <?php endif;?>
    <?php if($referral_point_value && $referral_child_points_method == $static_method):?>
        <div class="points-account-info points-referrer-points-info">
            <?php echo sprintf(__('For any first valid order placed by referred friend, you earn: <strong>%1d points</strong>.', 'j2t-reward-points-for-woocommerce'), $referral_point_value);?>
        </div>
        <?php elseif ($referral_point_value && $referral_child_points_method == $ratio_method):?>
        <div class="points-account-info points-referrer-points-info">
            <?php echo sprintf(__('For any first valid order placed by referred friend, you earn: <strong>%d points x product price values</strong>.', 'j2t-reward-points-for-woocommerce'), $referral_point_value);?>
        </div>
        <?php elseif ($referral_point_value && $referral_child_points_method == $cart_summary_method):?>
            <div class="points-account-info points-referrer-points-info">
                <?php echo sprintf(__('For any first valid order placed by referred friend, you earn: <strong>%d points x cart summary</strong>.', 'j2t-reward-points-for-woocommerce'), $referral_point_value);?>
            </div>
    <?php endif;?>
    <?php if($referral_child_point_value && $referral_child_points_method == $static_method):?>
        <div class="points-account-info points-referred-points-info">
            <?php echo sprintf(__('For any first valid order placed by referred friend, your friend gets: <strong>%d extra points</strong>.', 'j2t-reward-points-for-woocommerce'), $referral_child_point_value);?>
        </div>
        <?php elseif ($referral_child_point_value && $referral_child_points_method == $ratio_method):?>
            <div class="points-account-info points-referred-points-info">
                <?php echo sprintf(__('For any first valid order placed by referred friend, you friend gets: <strong>%d points x product price values</strong>.', 'j2t-reward-points-for-woocommerce'), $referral_child_point_value);?>
            </div>
        <?php elseif ($referral_child_point_value && $referral_child_points_method == $cart_summary_method):?>
            <div class="points-account-info points-referred-points-info">
                <?php echo sprintf(__('For any first valid order placed by referred friend, you friend gets: <strong>%d points x cart summary</strong>.', 'j2t-reward-points-for-woocommerce'), $referral_child_point_value);?>
            </div>
    <?php endif;?>
    <?php if ($referral_min_order):?>
        <div class="points-account-info min-order-referral">
            <?php echo sprintf(__("Minimum order for referral program to be processed is: <strong>%s</strong>.", 'j2t-reward-points-for-woocommerce'), wc_price($referral_min_order));?>
        </div>
    <?php endif;?>           
</div>

<?php if($referral_share_with_addthis || $referral_permanent || $referral_custom_code):?>
        <div class="account-box ad-account-info box">
            <?php if($referral_permanent):?>
            <div class="group-select fieldset">
                <h4 class="legend"><span><?php echo __('Permanent Link', 'j2t-reward-points-for-woocommerce') ?></span></h4>
                <div><?php echo sprintf(__('Share the following link with your friends: %s', 'j2t-reward-points-for-woocommerce'), '<br /><strong>'.$referral_url.'</strong>') ?></div>
            </div>
            <?php endif;?>

            <?php if ($custom_code = $referral_custom_code):?>
                <div class="group-select fieldset" style="min-height: 40px;">
                    <div class="legend"><span><?php echo __('Share with the following', 'j2t-reward-points-for-woocommerce') ?></span></div>
                    <?php echo str_replace("{{referral_url}}", $referral_url, $custom_code);?>
                </div>
            <?php endif;?>
            
            <?php if($referral_share_with_addthis):?>

                <div class="group-select fieldset">
                    <h4 class="legend"><span><?php echo __('Share referring link') ?></span></h4>                            
                            <div class="input-box">
                                <div for="j2t-share-title"><?php echo __('Sharing title (may not be used)', 'j2t-reward-points-for-woocommerce') ?></div>
                                <input type="text" name="j2t-share-title" id="j2t-share-title" value="<?php echo __('Great deals here!', 'j2t-reward-points-for-woocommerce');?>" class="input-text" />
                            </div>
                            <div class="input-box">
                                <div for="j2t-share-text"><?php echo __('Sharing text (may not be used)', 'j2t-reward-points-for-woocommerce') ?></div>
                                <textarea id="j2t-share-text" name="j2t-share-text" cols="100" rows="5" class="input-text"><?php echo __('Visit this for great deals!', 'j2t-reward-points-for-woocommerce');?></textarea>
                            </div>
                        
                    <br />
                    <!-- AddThis Button BEGIN -->
                    <?php echo wp_kses($referral_addthis_code, j2t_shapeSpace_allowed_html());?>
                    <script type="text/javascript">
                        var addthis_share =
                        {
                            url: "<?php echo esc_url($referral_url);?>",
                            title: jQuery('#j2t-share-title').val(),
                            description: jQuery('#j2t-share-text').val()
                        }
                    </script>
                </div>


                <?php $http = (is_ssl()) ? 'https' : 'http';?>
                <script type="text/javascript" src="<?php echo $http;?>://s7.addthis.com/js/250/addthis_widget.js#pubid=<?php echo esc_html($referral_addthis_account_name);?>"></script>
                <!-- AddThis Button END -->


        <?php endif;?>

    </div>
<?php endif;?>
<?php if ($hasReferredFriends) : ?>
    <h3 class="refered-friends-summary"><?php echo __('My Referred Friends', 'j2t-reward-points-for-woocommerce') ?></h3>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
        <thead>
                <tr>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-full_name"><span class="nobr"><?php echo __('Full Name', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-email"><span class="nobr"><?php echo __('Email', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-first_order"><span class="nobr"><?php echo __('First order?', 'j2t-reward-points-for-woocommerce') ?></span></th>
                </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result):?>
                <tr class="woocommerce-orders-table__row order">                                
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-full_name">
                        <?php echo (trim($result->rewardpoints_referral_name)) ? esc_html($result->rewardpoints_referral_name) : __('NA', 'j2t-reward-points-for-woocommerce'); ?>
                    </td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-email"><?php echo esc_html($result->rewardpoints_referral_email); ?></td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-first_order"><?php echo esc_html($result->rewardpoints_referral_status) ? __('yes', 'j2t-reward-points-for-woocommerce') : __('no', 'j2t-reward-points-for-woocommerce'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php endif; ?>
    <?php echo wp_kses($footerHtml, j2t_shapeSpace_allowed_html()); ?>
