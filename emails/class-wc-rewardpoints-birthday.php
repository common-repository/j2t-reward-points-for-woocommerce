<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * Class WC_Rewardpoints_Birthday
 */
class WC_Rewardpoints_Birthday extends WC_Email {

	
	function __construct() {
		$this->id          = 'wc_rewardpoints_birthday';
		$this->title       = __( 'Rewardpoints Birthday Email', 'j2treward' );
		$this->description = __( 'An email sent to the customer giving them points for their birthday.', 'j2treward' );
		$this->customer_email = true;
		$this->heading     = __( 'You received some points for your birthday.', 'j2treward' );
		$this->subject     = sprintf( _x( '[%s] A Special Gift For Your Birthday!', 'default email subject for cancelled emails sent to the customer', 'j2treward' ), '{blogname}' );
    

		$this->template_html  = 'emails/wc-rewardpoints-birthday.php';
		$this->template_plain = 'emails/plain/wc-rewardpoints-birthday.php';
		$this->template_base  = REWARDPOINTS_WC_EMAIL_PATH . 'templates/';
    
    // Action to which we hook onto to send the email.
		//add_action( 'woocommerce_order_status_pending_to_cancelled_notification', array( $this, 'trigger' ) );
		//add_action( 'woocommerce_order_status_on-hold_to_cancelled_notification', array( $this, 'trigger' ) );

		parent::__construct();
	}


    function trigger( $customer ) {
		$this->object = $customer;
		$email = $this->object->get_email();
		$this->recipient = $email;
		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}
		if ( false === defined( 'STYLESHEETPATH' ) ) {
			wp_templating_constants();
		}
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

    /**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'points'         => J2t_Rewardpoints::get_birthday_points(),
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'			=> $this
		), '', $this->template_base );
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'points'         => J2t_Rewardpoints::get_birthday_points(),
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		), '', $this->template_base );
	}
}