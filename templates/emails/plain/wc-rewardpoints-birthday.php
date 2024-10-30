<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "= " . esc_html($email_heading) . " =\n\n";

echo sprintf( __( 'We are pleased to inform you that we offered %d points for your birthday.', 'j2t-reward-points-for-woocommerce' ), $points ) . "\n\n";


echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );