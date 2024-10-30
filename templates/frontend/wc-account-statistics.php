<div class="available-point-scheme">
    <div class="points-account-info points-gather-info">
        <?php printf(__("%s spent on this site will allow you to gather %s point(s).", 'j2t-reward-points-for-woocommerce'), $price_unit, $point_price_unit); ?>
    </div>
    <div class="points-account-info points-required-info">
        <?php printf(__("When using %s point(s), you will get %s discount.", 'j2t-reward-points-for-woocommerce'), $price_point_unit, $price_unit); ?>
    </div>                
    <?php if ($birthday_points > 0): ?>
        <div class="points-account-info points-dob-info">
            <?php printf(__("For your birthday, we'll give you %s point(s).", 'j2t-reward-points-for-woocommerce'), $birthday_points); ?>
        </div>
    <?php endif;?>                
    <?php /*if ($registration_points > 0): ?>
        <div class="points-account-info points-registration-info">
            <?php printf(__("%s point(s) is given for your registration.", 'j2t-reward-points-for-woocommerce'), $registration_points); ?>
        </div>
    <?php endif;*/?>
</div>

<?php 
if ($hasPoints):
?>
    <h4 class="point-summary"><?php echo __('Summary', 'j2t-reward-points-for-woocommerce') ?></h4>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
        <thead>
                <tr>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-your_points"><span class="nobr"><?php echo __('Your Points', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-accumulated_points"><span class="nobr"><?php echo __('Accumulated Points', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-spent"><span class="nobr"><?php echo __('Points Spent', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <?php /*?><th class="woocommerce-orders-table__header woocommerce-orders-table__header-pending"><span class="nobr"><?php echo __('Pending Points', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-expired_delayed"><span class="nobr"><?php echo __('Expired/Delayed Points', 'j2t-reward-points-for-woocommerce') ?></span></th><?php */?>
                </tr>
        </thead>
        <tbody>
            <tr class="woocommerce-orders-table__row order">
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-your_points"><?php echo esc_html($available_points); ?></td>
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-accumulated_points"><?php echo esc_html($gathered_points); ?></td>
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-spent"><?php echo esc_html($total_points); ?></td>
                <?php /*?><td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-pending"></td>
                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-expired_delayed"></td><?php */?>
            </tr>
        </tbody>
    </table>
    <h4 class="point-history"><?php echo __('History', 'j2t-reward-points-for-woocommerce') ?></h3>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
        <thead>
                <tr>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order"><span class="nobr"><?php echo __('Type Of Points', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-gathered"><span class="nobr"><?php echo __('Gathered Points', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-spent"><span class="nobr"><?php echo __('Used Points', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-insertion_date"><span class="nobr"><?php echo __('Insertion Date', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <?php /*?><th class="woocommerce-orders-table__header woocommerce-orders-table__header-valid_from"><span class="nobr"><?php echo __('Available From', 'j2t-reward-points-for-woocommerce') ?></span></th>
                    <th class="woocommerce-orders-table__header woocommerce-orders-table__header-valid_until"><span class="nobr"><?php echo __('Available Until', 'j2t-reward-points-for-woocommerce') ?></span></th><?php */?>
                </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result):?>
                <?php 
                    $order = null;
                    if ($result->order_id > 0) {
                        $order = wc_get_order($result->order_id); 
                    }
                ?>
                <tr class="woocommerce-orders-table__row order">
                    
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order">
                        <?php if ($order): ?> 
                            <?php if ($result->rewardpoints_referral_id && !J2t_Rewardpoints::is_child_order($result->rewardpoints_referral_id, get_current_user_id())):?>
                                <?php echo esc_html( _x( 'Order #', 'hash before order number', 'woocommerce' ) . $order->get_order_number() ); ?>
                            <?php else:?>
                                <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                                    <?php echo esc_html( _x( 'Order #', 'hash before order number', 'woocommerce' ) . $order->get_order_number() ); ?>
                                </a>
                            <?php endif;?>                            
                            <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
                            <?php if ($result->rewardpoints_referral_id):?>
                                <div class="referral-program-points">
                                    <?php
                                        $customer = J2t_Rewardpoints::get_referred_friend_customer($result->rewardpoints_referral_id, get_current_user_id());
                                        if (is_object($customer) && ($email = $customer->get_email())):
                                            echo esc_html(sprintf(__('Referral Extra Points (user: %s)', 'j2t-reward-points-for-woocommerce'), $email));
                                    ?>
                                        <?php else: ?>
                                            <?php echo __('Referral Extra Points', 'j2t-reward-points-for-woocommerce'); ?>
                                        <?php endif; ?>                                    
                                </div>
                            <?php endif;?>
                            

                        <?php else:?>

                            <?php
                                if (isset($points_type[$result->order_id])):
                                    $reviewed_product = null;
                                    if ($result->order_id == $review_product_type):
                                        $reviewed_product = wc_get_product($result->rewardpoints_linker);
                                    endif;
                                    if (is_object($reviewed_product)) :
                                        echo sprintf(__('Review Points for product: %s.'), '<a href="'.esc_url($reviewed_product->get_permalink()).'">'.esc_html($reviewed_product->get_title()).'</a>');
                                    else:
                                        echo esc_html($points_type[$result->order_id]);
                                    endif;
                            else:
                                echo __( 'NA', 'j2t-reward-points-for-woocommerce' );
                            ?>

                            <?php endif;?>

                            <?php if ($result->rewardpoints_referral_id):?>
                                <div class="referral-program-points">
                                    <?php
                                        $customer = J2t_Rewardpoints::get_referred_friend_customer($result->rewardpoints_referral_id, get_current_user_id());
                                        if (is_object($customer) && ($email = $customer->get_email())):
                                            echo sprintf(__('Linked to referred friend (user: %s)', 'j2t-reward-points-for-woocommerce'), $email);
                                    ?>
                                        <?php else: ?>
                                            <?php echo __('Linked to referred friend', 'j2t-reward-points-for-woocommerce'); ?>
                                        <?php endif; ?>                                    
                                </div>

                            <?php endif;?>
                            
                            <?php if ($result->rewardpoints_description):?>
                                <div class="point-description">
                                    <?php echo esc_html($result->rewardpoints_description); ?>
                                </div>
                            <?php endif;?>

                            

                        <?php endif;?>
                    </td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-gathered"><?php echo esc_html($result->points_current); ?></td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-spent"><?php echo esc_html($result->points_spent); ?></td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-insertion_date">
                            <?php if ($order) {
                                ?>
                                <time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></time>
                                <?php
                            } elseif ($result->date_insertion) {
                                ?>
                                <time datetime="<?php echo esc_attr( wc_string_to_datetime($result->date_insertion)->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( wc_string_to_datetime($result->date_insertion) ) ); ?></time>
                                <?php
                            } else { ?>
                            <?php }?>
                        
                    </td>
                    <?php /*?><td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-valid_from"></td>
                    <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-valid_until"></td><?php */?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    echo wp_kses($footerHtml, j2t_shapeSpace_allowed_html());
endif;
?>