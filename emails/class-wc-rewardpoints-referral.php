<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * Class WC_Rewardpoints_Referral
 */
class WC_Rewardpoints_Referral extends WC_Email {

	protected $_friend_name;
    protected $_friend_email;
	
	function __construct() {
		$this->id          = 'wc_rewardpoints_referral';
		$this->title       = __( 'Referral program confirmation', 'j2treward' );
		$this->description = __( 'Email sent to referrer when referred friend places his/her first order.', 'j2treward' );
		$this->customer_email = true;
		$this->heading     = __( 'Good News! Your friend placed an order!', 'j2treward' );
		$this->subject     = sprintf( _x( '[%s] Referral program confirmation', 'default email subject for referred friend emails sent to the customer', 'j2treward' ), '{blogname}' );
    

		$this->template_html  = 'emails/wc-rewardpoints-referral.php';
		$this->template_plain = 'emails/plain/wc-rewardpoints-referral.php';
		$this->template_base  = REWARDPOINTS_WC_EMAIL_PATH . 'templates/';
    
    // Action to which we hook onto to send the email.
		//add_action( 'woocommerce_order_status_pending_to_cancelled_notification', array( $this, 'trigger' ) );
		//add_action( 'woocommerce_order_status_on-hold_to_cancelled_notification', array( $this, 'trigger' ) );

		parent::__construct();
	}


    function trigger( $parent, $child ) {
		$this->object = $parent;
		$email = $this->object->get_email();
		$this->recipient = $email;

		$this->_friend_name = $child->get_first_name() . ' ' . $child->get_last_name();
		$this->_friend_name = (trim($this->_friend_name)) ? $this->_friend_name : __( 'NA', 'j2treward' );
		$this->_friend_email = $child->get_email();

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
			'friend_name'   => $this->_friend_name,
			'friend_email'	=> $this->_friend_email,
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
			'friend_name'   => $this->_friend_name,
			'friend_email'	=> $this->_friend_email,
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		), '', $this->template_base );
	}
}