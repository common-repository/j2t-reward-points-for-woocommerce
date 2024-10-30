<?php
/**
 * Rewardpoints birthday emails sent to Customer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

	<p><?php printf( __( 'We are pleased to inform you that we offered %s points for your birthday.', 'j2t-reward-points-for-woocommerce' ), $points ); ?></p>

<?php

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

