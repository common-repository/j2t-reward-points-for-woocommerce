<?php


/**
 * Class RewardEmails
 */
class RewardEmails {

	/**
	 * RewardEmails constructor.
	 */
	public function __construct() {
    // Filtering the emails and adding our own email.
		add_filter( 'woocommerce_email_classes', array( $this, 'register_email' ), 90, 1 );
    // Absolute path to the plugin folder.
		define( 'REWARDPOINTS_WC_EMAIL_PATH', plugin_dir_path( __FILE__ ) );
	}

	/**
	 * @param array $emails
	 *
	 * @return array
	 */
	public function register_email( $emails ) {
		require_once 'emails/class-wc-rewardpoints-birthday.php';
		$emails['WC_Rewardpoints_Birthday'] = new WC_Rewardpoints_Birthday();
		require_once 'emails/class-wc-rewardpoints-referral.php';
		$emails['WC_Rewardpoints_Referral'] = new WC_Rewardpoints_Referral();

		return $emails;
	}
}

new RewardEmails();
