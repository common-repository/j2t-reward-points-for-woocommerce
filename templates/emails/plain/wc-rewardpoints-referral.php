<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "= " . html_esc($email_heading) . " =\n\n";

echo  __( 'Your friend just placed an order.', 'j2t-reward-points-for-woocommerce') . "\n\n";
echo sprintf( __( 'Friend\'s Name: %s.', 'j2t-reward-points-for-woocommerce' ), $friend_name ) . "\n\n";
echo sprintf( __( 'Friend\'s email address: %s.', 'j2t-reward-points-for-woocommerce' ), $friend_email ) . "\n\n";


echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );