<?php
/**
 * Referral order confirmation emails sent to Customer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

    <p><?php __( 'Your friend just placed an order.', 'j2t-reward-points-for-woocommerce' ); ?></p>
	<p><?php printf( __( 'Friend\'s Name: %s.', 'j2t-reward-points-for-woocommerce' ), $friend_name ); ?></p>
	<p><?php printf( __( 'Friend\'s email address: %s.', 'j2t-reward-points-for-woocommerce' ), $friend_email ); ?></p>

<?php

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

