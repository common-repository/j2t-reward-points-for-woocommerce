<?php
defined( 'ABSPATH' ) || exit;
/**
 * Plugin Name: J2T Reward Points for Woocommerce
 * Description: Rewardpoint engine for woocommerce. This module will allow your customers to gather points while placing an order. You can also decide to give them points for their birthday.
 * Plugin URI: https://wordpress.org/plugins/j2t-reward-points-for-woocommerce/
 * Text Domain: j2t-reward-points-for-woocommerce
 * Version: 1.0.0 
 * Author: J2T Design
 * Author URI: https://www.j2t-design.net
 *
 * @package WooCommerce\Admin
 */
//compatible with WC 3+

global $j2treward_db_version;
$j2treward_db_version = '1.0.1';

if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}


function j2t_shapeSpace_allowed_html() {

	$allowed_tags = array(
		'a' => array(
			'class' => array(),
			'href'  => array(),
			'rel'   => array(),
			'title' => array(),
		),
		'abbr' => array(
			'title' => array(),
		),
		'b' => array(),
		'blockquote' => array(
			'cite'  => array(),
		),
		'cite' => array(
			'title' => array(),
		),
		'code' => array(),
		'del' => array(
			'datetime' => array(),
			'title' => array(),
		),
		'dd' => array(),
		'div' => array(
			'class' => array(),
			'title' => array(),
			'style' => array(),
		),
		'dl' => array(),
		'dt' => array(),
		'em' => array(),
		'h1' => array(),
		'h2' => array(),
		'h3' => array(),
		'h4' => array(),
		'h5' => array(),
		'h6' => array(),
		'i' => array(),
		'img' => array(
			'alt'    => array(),
			'class'  => array(),
			'height' => array(),
			'src'    => array(),
			'width'  => array(),
		),
		'li' => array(
			'class' => array(),
		),
		'ol' => array(
			'class' => array(),
		),
		'p' => array(
			'class' => array(),
		),
		'q' => array(
			'cite' => array(),
			'title' => array(),
		),
		'span' => array(
			'class' => array(),
			'title' => array(),
			'style' => array(),
		),
		'strike' => array(),
		'strong' => array(),
		'ul' => array(
			'class' => array(),
		),
	);
	
	return $allowed_tags;
}


/**
 * Register the JS.
 */
function j2t_add_extension_register_script() {
	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! \Automattic\WooCommerce\Admin\Loader::is_admin_or_embed_page() ) {
		return;
	}
	
	$script_path       = '/build/index.js';
	$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require( $script_asset_path )
		: array( 'dependencies' => array(), 'version' => filemtime( $script_path ) );
	$script_url = plugins_url( $script_path, __FILE__ );

	wp_register_script(
		'j2t-rewardpoints-woocommerce',
		$script_url,
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	wp_register_style(
		'j2t-rewardpoints-woocommerce',
		plugins_url( '/build/index.css', __FILE__ ),
		array(),
		filemtime( dirname( __FILE__ ) . '/build/index.css' )
	);

	wp_enqueue_script( 'j2t-rewardpoints-woocommerce');
	wp_enqueue_style( 'j2t-rewardpoints-woocommerce' );
}

add_action( 'admin_enqueue_scripts', 'j2t_add_extension_register_script' );


function j2t_rewardpoints_woocommerce_activate() {

    //check if woocommerce is active
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH.'wp-admin/includes/plugin.php');
    }
    if ( !is_plugin_active('woocommerce/woocommerce.php') ) {
        echo '<h3>'.__('WooCommerce is required in order to use this plugin', 'j2t-reward-points-for-woocommerce').'</h3>';

        //Adding @ before will prevent XDebug output
        @trigger_error(__('Please ensure that WooCommerce is installed and activated in order to activate J2T Points & Rewards For WooCommerce.', 'j2t-reward-points-for-woocommerce'), E_USER_ERROR);
        exit;
    }

    // activation logic
    global $wpdb;
    global $j2treward_db_version;
    $table_name = $wpdb->prefix . "rewardpoints_account"; 
    $table_name_referral = $wpdb->prefix . "rewardpoints_referral"; 
    $table_name_flat = $wpdb->prefix . "rewardpoints_flat_account"; 

    $charset_collate = $wpdb->get_charset_collate();

    $installed_ver = get_option( "j2treward_db_version" );

    if (!$installed_ver || version_compare($installed_ver, '1.0.0', '<')) {
        $sql = "CREATE TABLE $table_name (
            `rewardpoints_account_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Entity Id',
            `customer_id` int(11) DEFAULT NULL,
            `store_id` varchar(255) DEFAULT NULL COMMENT 'Store Id',
            `order_id` varchar(60) DEFAULT NULL COMMENT 'Order Id',
            `points_current` float(10,0) DEFAULT 0 COMMENT 'Points Gathered',
            `points_spent` float(10,0) DEFAULT 0 COMMENT 'Points Spent',
            `rewardpoints_description` varchar(255) DEFAULT NULL COMMENT 'Point Description',
            `rewardpoints_linker` int(10) UNSIGNED DEFAULT 0 COMMENT 'Linker',
            `date_start` date DEFAULT NULL COMMENT 'Start Date',
            `date_end` date DEFAULT NULL COMMENT 'End Date',
            `convertion_rate` float(10,0) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Conversion Rate',
            `rewardpoints_referral_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Referral ID',
            `rewardpoints_status` varchar(255) DEFAULT NULL COMMENT 'Status',
            `rewardpoints_state` varchar(255) DEFAULT NULL COMMENT 'State',
            `date_order` datetime DEFAULT NULL COMMENT 'Date Order',
            `date_insertion` datetime DEFAULT NULL COMMENT 'Date Insertion',
            `period` date DEFAULT NULL COMMENT 'Period of time',
            `quote_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Quote ID',
            PRIMARY KEY  (`rewardpoints_account_id`),
            INDEX `REWARDPOINTS_ACCOUNT_STORE_ID` (`store_id`),
            INDEX `REWARDPOINTS_ACCOUNT_REWARDPOINTS_STATUS` (`rewardpoints_status`),
            INDEX `REWARDPOINTS_ACCOUNT_REWARDPOINTS_STATE` (`rewardpoints_state`)
            ) $charset_collate;
            
            CREATE TABLE $table_name_referral (
            `rewardpoints_referral_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Entity Id',
            `rewardpoints_referral_parent_id` int(11) DEFAULT NULL,
            `rewardpoints_referral_child_id` int(11) DEFAULT NULL,
            `rewardpoints_referral_email` varchar(255) DEFAULT NULL COMMENT 'Referral Email Address',
            `rewardpoints_referral_name` varchar(255) DEFAULT NULL COMMENT 'Referral Name',
            `rewardpoints_referral_status` smallint(6) NOT NULL DEFAULT 0 COMMENT 'Status',
            `store_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Store ID',
            PRIMARY KEY  (`rewardpoints_referral_id`),
            INDEX `REWARDPOINTS_REFERRAL_STORE_ID` (`store_id`),
            INDEX `REWARDPOINTS_REFERRAL_REWARDPOINTS_REFERRAL_ID` (`rewardpoints_referral_id`),
            INDEX `REWARDPOINTS_REFERRAL_REWARDPOINTS_REFERRAL_PARENT_ID` (`rewardpoints_referral_parent_id`),
            INDEX `REWARDPOINTS_REFERRAL_REWARDPOINTS_REFERRAL_CHILD_ID` (`rewardpoints_referral_child_id`),
            INDEX `REWARDPOINTS_REFERRAL_REWARDPOINTS_REFERRAL_EMAIL` (`rewardpoints_referral_email`)
            ) $charset_collate;";

    
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    if (!$installed_ver || version_compare($installed_ver, '1.0.1', '<')) {
        $sql = "CREATE TABLE $table_name_flat (

            `flat_account_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Entity Id',
            `user_id` int(11) DEFAULT NULL,
            `store_id` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Store Id',
            `points_collected` float(10,0) DEFAULT 0 COMMENT 'Collected Points',
            `points_used` float(10,0) DEFAULT 0 COMMENT 'Used Points',
            `points_waiting` float(10,0) DEFAULT 0 COMMENT 'Waiting Points',
            `points_not_available` float(10,0) DEFAULT 0 COMMENT 'Not Available Points',
            `points_current` float(10,0) DEFAULT 0 COMMENT 'Current Points',
            `points_lost` float(10,0) DEFAULT 0 COMMENT 'Lost Points',
            `notification_qty` float(10,0) DEFAULT 0 COMMENT 'Notification Qty',
            `notification_date` datetime DEFAULT NULL COMMENT 'Notification Date',
            `last_check` date DEFAULT NULL COMMENT 'Last Verification Date',
            `dob_points` date DEFAULT NULL COMMENT 'Last DOB Gift Date',
            PRIMARY KEY  (`flat_account_id`),
            INDEX `REWARDPOINTS_FLAT_ACCOUNT_FLAT_ACCOUNT_ID` (`flat_account_id`),
            INDEX `REWARDPOINTS_FLAT_ACCOUNT_STORE_ID` (`store_id`),
            INDEX `REWARDPOINTS_FLAT_ACCOUNT_USER_ID` (`user_id`)
        ) $charset_collate;";

    
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    add_option( 'j2treward_db_version', $j2treward_db_version );

    if (! wp_next_scheduled ( 'j2t_rewardpoints_daily_event' )) {
        wp_schedule_event(time(), 'daily', 'j2t_rewardpoints_daily_event');
    }

}
register_activation_hook( __FILE__, 'j2t_rewardpoints_woocommerce_activate' );


function j2t_rewardpoints_woocommerce_is_current_version(){
    global $j2treward_db_version;
    $version = get_option( 'j2treward_db_version' );
    return version_compare($version, $j2treward_db_version, '=') ? true : false;
}

if ( !j2t_rewardpoints_woocommerce_is_current_version() ) j2t_rewardpoints_woocommerce_activate();

 
function j2t_rewardpoints_woocommerce_deactivate() {
    // deactivation logic
}
register_deactivation_hook( __FILE__, 'j2t_rewardpoints_woocommerce_deactivate' );


if ( ! class_exists( 'J2t_Rewardpoints' ) ) :

    
    /**
     * J2t Reward Points core class
     */
    class J2t_Rewardpoints {

        const APPLY_ALL_ORDERS = '-1';
        const TYPE_POINTS_ADMIN = '-1';
        const TYPE_POINTS_REVIEW = '-2';
        const TYPE_POINTS_REGISTRATION = '-3';
        const TYPE_POINTS_REQUIRED = '-10';
        const TYPE_POINTS_BIRTHDAY = '-20';
        const TYPE_POINTS_FB = '-30';
        const TYPE_POINTS_GP = '-40';
        const TYPE_POINTS_PIN = '-50';
        const TYPE_POINTS_TT = '-60';
        const TYPE_POINTS_NEWSLETTER = '-70';
        const TYPE_POINTS_POLL = '-80';
        const TYPE_POINTS_TAG = '-90';
        const TYPE_POINTS_DYN = '-99';
        const TYPE_POINTS_REFERRAL_REGISTRATION = '-33';

        const MATH_CEIL = 'ceil';
        const MATH_ROUND = 'round';
        const MATH_FLOOR = 'floor';

        const STATIC_VALUE = 'static';
        const RATIO_POINTS = 'ratio';
        const CART_SUMMARY = 'cart_summary_ratio';

        /**
         * The single instance of the class.
         */
        protected static $_instance = null;
 
        /**
         * Constructor.
         */
        protected function __construct() {
            // Instantiation logic will go here.
            $this->includes();
            $this->init();
        }
        
        public function includes() {
        }
        
        public static function init() {

            if (!function_exists('is_plugin_active')) {
                include_once(ABSPATH.'wp-admin/includes/plugin.php');
            }
            if (is_plugin_active('woocommerce/woocommerce.php')) {

                
                add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
                add_action( 'woocommerce_sections_settings_tab_j2trewards', __CLASS__ . '::output_sections'  );
                add_action( 'woocommerce_settings_tabs_settings_tab_j2trewards', __CLASS__ . '::settings_tab' );
                add_action( 'woocommerce_update_options_settings_tab_j2trewards', __CLASS__ . '::update_settings' );
                add_action( 'wp_enqueue_scripts', __CLASS__ . '::rewardpoints_front_scripts' );

                add_action( 'woocommerce_order_status_changed', __CLASS__ . '::rewardpoints_order_status_change', 15, 1 );
                

                if (self::is_module_activated_by_user()) {

                    add_action( 'init', __CLASS__ . '::rewardpoints_referral_process', 50) ;
                    add_action( 'woocommerce_cart_actions', __CLASS__ . '::reward_cart_form', 10 );
                    add_action( 'woocommerce_review_order_before_submit', __CLASS__ . '::reward_checkout_form', 10 );  
                    
                    add_action( 'woocommerce_cart_calculate_fees',__CLASS__ . '::reward_add_discount', 20, 1 );


                    add_action( 'wp_ajax_apply_points_on_cart', __CLASS__ . '::apply_points_on_cart' );
                    //add_action( 'woocommerce_removed_coupon', __CLASS__ . '::removed_points_on_cart', 10, 1 ); 
                    //add_filter( 'woocommerce_cart_subtotal', __CLASS__ . '::rewardpoints_checkout_coupons', 10, 3 );

                    //update order info
                    //woocommerce_process_shop_order_meta
                    //woocommerce_update_order
                    //woocommerce_before_order_object_save
                    add_action( 'woocommerce_after_order_object_save', __CLASS__ . '::reward_save_order', 10, 1 );

                    add_action('woocommerce_checkout_create_order', __CLASS__ . '::before_checkout_create_order', 20, 2);
                    add_action( 'woocommerce_new_order', __CLASS__ . '::add_points_order_note',  1, 1  );
                    add_action( 'woocommerce_email_order_meta', __CLASS__ . '::rewardpoints_add_email_order_meta', 10, 3 );

                    add_action( 'init', __CLASS__ . '::add_rewardpoints_endpoint' );
                    add_filter( 'query_vars', __CLASS__ . '::rewardpoints_query_vars', 0 );
                    add_filter( 'woocommerce_account_menu_items', __CLASS__ . '::rewardpoints_link_my_account' );
                    add_action( 'woocommerce_account_rewardpoints_endpoint', __CLASS__ . '::rewardpoints_content' );

                    add_action('wp_set_comment_status', __CLASS__ . '::rewardpoints_review_status_update', 15, 3);


                    if (self::is_referral_link_active()) {
                        add_action( 'init', __CLASS__ . '::add_rewardpoints_referral_endpoint' );
                        add_filter( 'query_vars', __CLASS__ . '::rewardpoints_referral_query_vars', 0 );
                        add_filter( 'woocommerce_account_menu_items', __CLASS__ . '::rewardpoints_referral_link_my_account' );
                        add_action( 'woocommerce_account_rewardpoints_referral_endpoint', __CLASS__ . '::rewardpoints_referral_content' );
                    }
                    

                    add_action('woocommerce_after_shop_loop_item_title', __CLASS__ . '::rewardpoints_point_value_grid');
                    add_action('woocommerce_after_add_to_cart_button', __CLASS__ . '::rewardpoints_point_value_view_page');
                    
                    add_filter( 'manage_users_columns', __CLASS__ . '::rewardpoints_point_new_user_column' );
                    add_filter( 'manage_users_custom_column', __CLASS__ . '::rewardpoints_point_new_user_column_content', 10, 3 );
                    //add_filter( 'manage_edit-customers_columns', __CLASS__ . '::rewardpoints_point_new_customer_column_content' );
                    //add_filter( 'manage_edit-shop_order_columns',  __CLASS__ . '::rewardpoints_point_new_user_column_content', 10, 3 );

                    // add the action 
                    add_action( 'woocommerce_register_form',  __CLASS__ . '::rewardpoints_registration_form_hook', 10, 0 ); 
                    add_action( 'user_register', __CLASS__ . '::rewardpoints_registration_save', 10, 1 );
                    


                    if (self::is_dob_activated_by_user()) {
                        add_action( 'woocommerce_edit_account_form', __CLASS__ . '::action_woocommerce_edit_account_form' );
                        add_action( 'woocommerce_save_account_details_errors', __CLASS__ . '::action_woocommerce_save_account_details_errors', 10, 1 );
                        add_action( 'woocommerce_save_account_details', __CLASS__ . '::action_woocommerce_save_account_details', 10, 1 );
                        add_action( 'show_user_profile', __CLASS__ . '::add_user_birtday_field', 10, 1 );
                        add_action( 'edit_user_profile', __CLASS__ . '::add_user_birtday_field', 10, 1 );
                        add_action( 'personal_options_update', __CLASS__ . '::save_user_birtday_field', 10, 1 );
                        add_action( 'edit_user_profile_update', __CLASS__ . '::save_user_birtday_field', 10, 1 );
                        
                        add_action('j2t_rewardpoints_daily_event', __CLASS__ . '::daily_event_action');
                    }
                }
                if (self::allow_trash_action()) {
                    add_action( 'trashed_post', __CLASS__ . '::rewardpoints_order_status_change_trash', 20, 1 );
                    add_action( 'untrashed_post', __CLASS__ . '::rewardpoints_order_status_change_trash', 20, 1 );
                }
                if (self::allow_trash_delete()) {
                    add_action( 'woocommerce_delete_order', __CLASS__ . '::rewardpoints_delete_order', 20, 1 );
                    add_action( 'before_delete_post', __CLASS__ . '::rewardpoints_delete_order', 20, 1 ); 
                }                
            }            
        }

        public static function rewardpoints_referral_process() {
            

            if (is_user_logged_in())
                return;
            
            if(isset($_GET['referral'])) {
                if ( ! WC()->session->has_session() ) {
                    WC()->session->set_customer_session_cookie( true );
                }
                $referral_code_decoded = explode('-', str_replace('j2t','', base64_decode($_GET['referral'])));
                if (count($referral_code_decoded) == 2 && is_numeric($referral_code_decoded[1])) {
                    $referrer_id = $referral_code_decoded[1];
                    $customer = new WC_Customer( $referrer_id );
                    if ($customer->get_id()) {
                        //referrer is found, store session
                        WC()->session->set( 'referral_user', $customer->get_id() );
                        //redirect
                        $url_redirect = home_url().'/'.self::get_conf_referral_redirection() ;
                        if ( !$url_redirect ) {
                            $url_redirect = home_url();
                        }
                        wp_redirect($url_redirect);
                        exit;
                    }
                }
            }
            //WC()->session->get( 'referral_user' );
        }

        public static function rewardpoints_point_new_user_column( $columns ) {
            $columns['customer_points'] = 'Available points';
            return $columns;
        }

        public static function rewardpoints_point_new_user_column_content( $content, $column, $user_id ) {
    
            if ( 'customer_points' === $column ) {
                $content = self::get_user_available_points($user_id);
            }
            
            return $content;
        }

        public static function write_log($log) {
            if (true === WP_DEBUG) {
                if (is_array($log) || is_object($log)) {
                    error_log(print_r($log, true));
                } else {
                    error_log($log);
                }
            } 
        }

        public static function can_record_logs() {
            return (get_option( 'wc_settings_tab_j2trewards_record_dob_logs', false ));
        }

        public static function daily_event_action() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rewardpoints_flat_account';
            //1. get all customers
            //2. check the birthdays
            //3. if current day give points only if points haven't been given for specific day
            $storeId = null;
            $args = array(
                'role'    => 'Customer'
            );
            $collection = get_users( $args );
            $recordLogs = self::can_record_logs();
            
            if ($recordLogs){                
                $message = 'Running get_users query';
                self::write_log($message);

                $message = sprintf('Log sql count: %d', count($collection));
                self::write_log($message);
            }
            
            if(!class_exists('RewardEmails')){
                require_once dirname( __FILE__ ) . '/' . 'RewardEmails.php';
            }

            foreach ($collection as $customer) {
                //$storeId = $customer->getStoreId();
                $points = self::get_birthday_points();
                $prior_verification_days = (int)self::get_birthday_prior_verification();
                
                $customerId = $customer->ID;
                $customerName = trim($customer->first_name . ' ' . $customer->last_name);

                if (self::is_module_activated_by_user()) {
                    $dob = get_user_meta( $customer->ID, 'dob_field', true );

                    if ($recordLogs){
                        $message = sprintf('Verify DOB (%s) for customer %s. Points to be inserted: %d', $dob, $customer->ID, $points);
                        self::write_log($message);
                    }

                    if ($points > 0 && $dob != null && $dob != '0000-00-00' && $dob != ''){
                        
                        if ($recordLogs){
                            $message = sprintf('Log birthday verification customer #%d (%s) - DOB DEFINED (%s)', $customer->ID, $customer->first_name.' '.$customerName, $dob);
                            self::write_log($message);
                        }
    
                        $strMktime = strtotime($dob);
    
                        $dob_year = date("Y", $strMktime);
                        $dobMonth = date("m", $strMktime);
                        $dobDay = date("d", $strMktime);

                        $substractDays = 0;
                        if ($prior_verification_days){
                            $substractDays = $prior_verification_days * 24 * 60 * 60;
                        }

                        $dateVerifiedDob_original = mktime(0, 0, 0, $dobMonth, $dobDay, date("Y"));
                        $dateVerifiedDob = mktime(0, 0, 0, $dobMonth, $dobDay, date("Y")) - $substractDays;
                        $dateVerifiedCurrent = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
                        $dobRealCurrent = mktime(0, 0, 0, $dobMonth, $dobDay, date("Y")) + $substractDays;

                        if ($recordLogs){
                            $message = sprintf('Log birthday verification (dob: %s) customer #%d (%s): verification (%s = %s)? Original DOB: %s.', $dob, $customer->ID, $customerName, date('Y-m-d H:i:s', $dateVerifiedCurrent), date('Y-m-d H:i:s', $dateVerifiedDob), date('Y-m-d H:i:s', $dateVerifiedDob_original));
                            self::write_log($message);
                        }

                        if ($dateVerifiedCurrent == $dateVerifiedDob){
                            $flat_reward_record = $wpdb->get_row( "SELECT * FROM $table_name WHERE user_id = $customerId" );
                            $dateInsertion = null;
                            if ($flat_reward_record) {
                                $dateInsertion = $flat_reward_record->dob_points;
                            }
                            

                            if ($dateInsertion && $dateInsertion != '0000-00-00' && $dateInsertion != ''){

                                //check if year date_start < todays year
                                $lastDobYearMktime = strtotime($dateInsertion);
                                $lastDobYear = date("Y", $lastDobYearMktime);
                                $realDobYear = date("Y", $dobRealCurrent);

                                //verify if points were already given
                                if ($lastDobYear < $realDobYear){
                                    //HAPPY BIRTHDAY
                                    self::addBirthdayPoints($points, $storeId, $customerId, $prior_verification_days);
                                    if ($recordLogs){
                                        $message = sprintf('Log birthday verification customer #%d (%s): Points allocated', $customer->ID, $customerName);
                                        self::write_log($message);
                                    }
                                } else {
                                    //NOT BIRTHDAY;
                                    if ($recordLogs){
                                        $message = sprintf('Log birthday verification customer #%d (%s): Points already allocated (%s < %s)?', $customer->ID, $customerName, $lastDobYear, date("Y"));
                                        self::write_log($message);
                                    }
                                }
                            } else {
                                //HAPPY BIRTHDAY
                                self::addBirthdayPoints($points, $storeId, $customerId, $prior_verification_days);
                                if ($recordLogs){
                                    $message = sprintf('Log birthday verification customer #%d (%s): Points allocated', $customer->ID, $customerName);
                                    self::write_log($message);
                                }
                            }
                        }
                    } else {
                        if ($recordLogs){
                            $message = sprintf('Log birthday verification customer #%d (%s) - NO DOB DEFINED', $customer->ID, $customerName);
                            self::write_log($message);
                        }
                    }
                } elseif ($recordLogs){
                    self::write_log('Module Not Active');
                }
            }
        }

        

        public static function addBirthdayPoints($points, $storeId, $customerId, $prior_verification_days){
            $delay = self::get_birthday_delay();
            $duration = (int)self::get_birthday_duration();
    
            //update dob_points DOB field verification in flatpoints tobe this year, customer's month, customer's day + $prior_verification_days
            $updateFlatPoints = self::updateDobVerification($customerId, $prior_verification_days,  $storeId);

            $recordLogs = self::can_record_logs();
            
            if ($updateFlatPoints !== false){
                //add points & send mail
                if ($recordLogs){
                    $message = sprintf('Points added to customer: %s.', $customerId);
                    self::write_log($message);
                }
                $recordPointsModel = self::insert_update_points($customerId, self::TYPE_POINTS_BIRTHDAY, $points, 0, date('Y-m-d h:i:s'), null);
                if ($recordLogs){
                    $message = sprintf('Point record added for customer: %d.', $customerId);
                    self::write_log($message);
                }
                //send birthday notification email
                $email_birthday = WC()->mailer()->get_emails()['WC_Rewardpoints_Birthday'];
                $customer = new WC_Customer( $customerId );
                // Sending the DOB points emails to customer
                $email_birthday->trigger( $customer );
                if ($recordLogs){
                    $message = sprintf('Email sent to customer: %d.', $customerId);
                    self::write_log($message);
                }
            } else {
                if ($recordLogs){
                    $message = sprintf('Unable to add points to customer: %d.', $customerId);
                    self::write_log($message);
                }
            }
        }

        public static function updateDobVerification($customerId, $numDaysAdded, $storeId = null) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rewardpoints_flat_account';
            $dateInsertion = ($numDaysAdded && $numDaysAdded >0) ? date('Y-m-d', strtotime(date('Y-m-d'). " + $numDaysAdded days")) : date('Y-m-d');
            $insert_array = array( 
                'user_id' => $customerId, 
                'points_collected' => self::get_user_total_gathered_points($customerId), 
                'points_used' => self::get_user_total_used_points($customerId), 
                'points_current' => self::get_user_total_gathered_points($customerId) - self::get_user_total_used_points($customerId),
                'dob_points' => $dateInsertion
            );
            $recorded = $wpdb->get_row( "SELECT * FROM $table_name WHERE user_id = $customerId" );
            $flat_account_id = (is_object($recorded)) ? $recorded->flat_account_id : 0; 
            
            
            //insert or update if data exists
            if ($flat_account_id) {
                $result = $wpdb->update($table_name, $insert_array, array('flat_account_id' => $flat_account_id));
            } else {
                $result = $wpdb->insert($table_name, $insert_array);
            }

            return $result;
        }

        // Add field - my account
        public static function action_woocommerce_edit_account_form() {   
            woocommerce_form_field( 'dob_field', array(
                'type'        => 'date',
                'label'       => __( 'Date of Birth', 'j2t-reward-points-for-woocommerce' ),
                'placeholder' => __( 'Date of Birth', 'j2t-reward-points-for-woocommerce' ),
                'required'    => true,
            ), get_user_meta( get_current_user_id(), 'dob_field', true ));
        }
        // Validate - my account
        public static function action_woocommerce_save_account_details_errors( $args ){
            if ( isset($_POST['dob_field']) && empty($_POST['dob_field']) ) {
                $args->add( 'error', __( 'Please provide a valid birth date', 'j2t-reward-points-for-woocommerce' ) );
            }
        }
        // Save - my account
        public static function action_woocommerce_save_account_details( $user_id ) {  
            if( isset($_POST['dob_field']) && ! empty($_POST['dob_field']) ) {
                update_user_meta( $user_id, 'dob_field', sanitize_text_field($_POST['dob_field']) );
            }
        }
        // Add field - admin
        public static function add_user_birtday_field( $user ) {
            ?>
                <h3><?php _e('Date of Birth','j2t-reward-points-for-woocommerce' ); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="dob_field"><?php _e( 'Date of Birth', 'j2t-reward-points-for-woocommerce' ); ?></label></th>
                        <td><input type="date" name="dob_field" value="<?php echo esc_attr( get_the_author_meta( 'dob_field', $user->ID )); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <br />
            <?php
        }
        // Save field - admin
        public static function save_user_birtday_field( $user_id ) {
            if( ! empty($_POST['dob_field']) ) {
                update_user_meta( $user_id, 'dob_field', sanitize_text_field( $_POST['dob_field'] ) );
            }
        }

        public function getPointsDefaultTypeToArray()
        {
            $return_value = [self::TYPE_POINTS_FB => __('Facebook Like points', 'j2t-reward-points-for-woocommerce'), 
                self::TYPE_POINTS_PIN => __('Pinterest points', 'j2t-reward-points-for-woocommerce'), 
                self::TYPE_POINTS_TT => __('Twitter points', 'j2t-reward-points-for-woocommerce'), 
                self::TYPE_POINTS_GP => __('Google Plus points', 'j2t-reward-points-for-woocommerce'), 
                self::TYPE_POINTS_BIRTHDAY => __('Birthday points', 'j2t-reward-points-for-woocommerce'), 
                self::TYPE_POINTS_REQUIRED => __('Required points usage', 'j2t-reward-points-for-woocommerce'), 
                self::TYPE_POINTS_REVIEW => __('Review points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_DYN => __('Event points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_NEWSLETTER => __('Newsletter points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_POLL => __('Poll points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_TAG => __('Tag points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_ADMIN => __('Admin gift', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_REGISTRATION => __('Registration points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_REFERRAL_REGISTRATION => __('Referral registration points', 'j2t-reward-points-for-woocommerce')];

            return $return_value;
        }

        public static function getPointsTypeToArray()
        {
            $return_value = [self::TYPE_POINTS_FB => __('Facebook Like points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_GP => __('Google Plus points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_PIN => __('Pinterest points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_TT => __('Twitter points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_BIRTHDAY => __('Birthday points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_REVIEW => __('Review points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_DYN => __('Event points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_NEWSLETTER => __('Newsletter points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_POLL => __('Poll points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_TAG => __('Tag points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_ADMIN => __('Admin gift', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_REQUIRED => __('Points used on products', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_REGISTRATION => __('Registration points', 'j2t-reward-points-for-woocommerce'),
                self::TYPE_POINTS_REFERRAL_REGISTRATION => __('Referral registration points', 'j2t-reward-points-for-woocommerce')];

            return $return_value;
        }

        public static function add_rewardpoints_endpoint() {
            add_rewrite_endpoint( 'rewardpoints', EP_ROOT | EP_PAGES );
        }
        public static function rewardpoints_query_vars( $vars ) {
            $vars[] = 'rewardpoints';
            return $vars;
        }
        public static function rewardpoints_link_my_account( $items ) {
            $items['rewardpoints'] = 'My Reward Points';
            return $items;
        }


        public static function add_rewardpoints_referral_endpoint() {
            add_rewrite_endpoint( 'rewardpoints_referral', EP_ROOT | EP_PAGES );
        }
        public static function rewardpoints_referral_query_vars( $vars ) {
            $vars[] = 'rewardpoints_referral';
            return $vars;
        }
        public static function rewardpoints_referral_link_my_account( $items ) {
            $items['rewardpoints_referral'] = 'My Referral Program';
            return $items;
        }

        public static function is_referral_link_active() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_show', false );
        }

        public static function get_price_point_worthing_value($price) {            
            $pointGatheringRatio = self::get_conf_money_point_value();
            if (!$price) return 0;
            $gatheringPoints = self::mathActionOnCatalogEarn($price * $pointGatheringRatio);
            return $gatheringPoints;
        }

        public static function get_group_product_prices ($product) {
            $children = $product->get_children();
            $prices = array('min' => $product->get_price(), 'max' => $product->get_price());
            foreach ($children as $key => $value) {
                $_product = wc_get_product( $value );
                $prices['min'] = min($_product->get_price(), $prices['min']);
                $prices['max'] = max($_product->get_price(), $prices['max']);
            }
            return $prices;
        }

        public static function rewardpoints_point_value_grid () {
            global $product;
            if (self::show_on_grid()) {
                $product_price = $product->get_price();
                $process = true;
                if( $product->is_type('grouped') ) {
                    $prices = self::get_group_product_prices ($product);
                    $min = $prices['min'];
                    $max = $prices['max'];
                    if ($min < $max) {
                        $process = false;
                        ?>
                        <div class="product-points-value product-grid-page"><?php echo self::get_rewardpoints_inline_image().sprintf(__("With this product, you will collect between %s and %s points.", 'j2t-reward-points-for-woocommerce'), '<span class="point-value-inline min">'.self::get_price_point_worthing_value($min).'</span>', '<span class="point-value-inline max">'.self::get_price_point_worthing_value($max).'</span>'); ?></div>
                        <?php
                    }
                }
                if( $process && $product->is_type('variable') && $product->get_variation_price() < $product->get_variation_price('max')) {
                    ?>
                    <div class="product-points-value product-grid-page"><?php echo self::get_rewardpoints_inline_image().sprintf(__("With this product, you will collect between %s and %s points.", 'j2t-reward-points-for-woocommerce'), '<span class="point-value-inline min">'.self::get_price_point_worthing_value($product->get_variation_price()).'</span>', '<span class="point-value-inline max">'.self::get_price_point_worthing_value($product->get_variation_price('max')).'</span>'); ?></div>
                    <?php
                } elseif ($process) {
                    ?>
                    <div class="product-points-value product-grid-page"><?php echo self::get_rewardpoints_inline_image().sprintf(__("With this product, you will collect %s points.", 'j2t-reward-points-for-woocommerce'), '<span class="point-value-inline">'.self::get_price_point_worthing_value($product_price).'</span>'); ?></div>
                    <?php
                }
                
            }   
        }
        public static function rewardpoints_point_value_view_page () {
            global $product;
            if (self::show_on_product_page()) {
                ?>
                <script>
                    function j2tProcessJsMath(total_points) {
                        return <?php echo self::getJSMathMethod(); ?>;
                    }
                </script>
                <?php
                $product_price = $product->get_price();
                $process = true;
                ?>
                <script>
                    var default_variable_conv_rate = <?php echo esc_html(self::get_conf_money_point_value())?>;
                    var j2tNumberOfDecimals = <?php echo esc_html(wc_get_price_decimals())?>;
                    var j2tDecimalSeparator = '<?php echo esc_html(wc_get_price_decimal_separator())?>';
                    var j2tThousandSeparator = '<?php echo esc_html(wc_get_price_thousand_separator())?>';
                    var default_current_product_price = <?php echo esc_html($product->get_price())?>;
                    var current_product_type = '<?php echo esc_html($product->get_type());?>';
                </script>
                <?php

                if( $product->is_type('grouped') ) {
                    
                    $prices = self::get_group_product_prices ($product);
                    $min = $prices['min'];
                    $max = $prices['max'];
                    if ($min < $max) {
                        $process = false;
                        ?>
                        <div class="product-points-value product-grid-page"><?php echo self::get_rewardpoints_inline_image().sprintf(__("With this product, you will collect between %s and %s points.", 'j2t-reward-points-for-woocommerce'), '<span class="point-value-inline min">'.self::get_price_point_worthing_value($min).'</span>', '<span class="point-value-inline max">'.self::get_price_point_worthing_value($max).'</span>'); ?></div>
                        <?php
                    }
                }
                if( $process && $product->is_type('variable') && $product->get_variation_price() < $product->get_variation_price('max')) {
                    ?>
                    <div class="product-points-value product-grid-page"><?php echo self::get_rewardpoints_inline_image().sprintf(__("With this product, you will collect between %s and %s points.", 'j2t-reward-points-for-woocommerce'), '<span class="point-value-inline min">'.self::get_price_point_worthing_value($product->get_variation_price()).'</span>', '<span class="point-value-inline max">'.self::get_price_point_worthing_value($product->get_variation_price('max')).'</span>'); ?></div>
                    <?php
                } elseif ($process) {
                    ?>
                    <div class="product-points-value product-grid-page"><?php echo self::get_rewardpoints_inline_image().sprintf(__("With this product, you will collect %s points.", 'j2t-reward-points-for-woocommerce'), '<span class="point-value-inline">'.self::get_price_point_worthing_value($product_price).'</span>'); ?></div>
                    <?php
                }
                if( $product->is_type('variable') && $product_price) {
                    ?>
                    <div class="product-points-value product-grid-page"><?php echo sprintf(__("If you purchase this product as per your selection, you will collect %s points.", 'j2t-reward-points-for-woocommerce'), '<span class="point-value-inline configured">'.self::get_price_point_worthing_value($product_price).'</span>'); ?></div>
                    <?php
                }
            } 
        }

        public static function rewardpoints_registration_form_hook() {
            if (self::get_registration_points() > 0): ?>
                <div class="points-registration-info">
                    <?php printf(__("When you register on this store, you will get %s point(s).", 'j2t-reward-points-for-woocommerce'), self::get_registration_points()); ?>
                </div>
            <?php endif;
        }

        public function rewardpoints_registration_save( $user_id ) {
            global $wpdb;
            //record points on registration
            $points = self::get_registration_points();
            if ($points > 0) {
                //record registration points
                $recordPointsModel = self::insert_update_points($user_id, self::TYPE_POINTS_REGISTRATION, $points, 0, date('Y-m-d h:i:s'), null, null, true);
            }


            //record referrer registration points
            $referrer_id = WC()->session->get( 'referral_user' );
            $customer = new WC_Customer( $user_id );

            //if registered customer id = referrer customer id, forbid insertion in rewardpoints_referral table
            if ($referrer_id == $user_id) {
                WC()->session->__unset( 'referral_user' );
                $customer->delete_meta_data('_rewardpoints_referrer');
                $customer->save();
            }
            if ($referrer_id && $customer->get_id()) {
                $referrer_id = intval($referrer_id);
                $customer_referrer_id = $customer->get_meta('_rewardpoints_referrer');
                if (!$customer_referrer_id && ($email = $customer->get_email())) {
                    $customer->update_meta_data( '_rewardpoints_referrer', $referrer_id);
                    $customer->save();

                    $table_name = $wpdb->prefix . 'rewardpoints_referral';
                    $insert_array = array( 
                        'rewardpoints_referral_parent_id' => $referrer_id, 
                        'rewardpoints_referral_email' => $email, 
                        'rewardpoints_referral_name' => $customer->get_first_name().' '.$customer->get_last_name(), 
                        'store_id' => null
                    );

                    $recorded = $wpdb->get_row( "SELECT * FROM $table_name WHERE rewardpoints_referral_email LIKE '$email'" );
                    $rewardpoints_referral_id = (is_object($recorded)) ? $recorded->rewardpoints_referral_id : 0;                     

                    //insert if not already there
                    if (!$rewardpoints_referral_id) {
                        $wpdb->insert($table_name, $insert_array);
                        $rewardpoints_referral_id = $wpdb->insert_id;
                        //record extra points for customer child registration (for the parent)
                        if ($parent_points = self::get_conf_referral_registration_points()) {
                            $recordPointsModel = self::insert_update_points($referrer_id, self::TYPE_POINTS_REFERRAL_REGISTRATION, $parent_points, 0, date('Y-m-d h:i:s'), null, null, true, $rewardpoints_referral_id);
                        }
                        //record extra points for customer child registration (for the child)
                        if ($child_point = self::get_conf_referrer_registration_points()) {
                            $recordPointsModel = self::insert_update_points($user_id, self::TYPE_POINTS_REFERRAL_REGISTRATION, $child_point, 0, date('Y-m-d h:i:s'), null, null, true, $rewardpoints_referral_id);
                        }
                        //TODO : send referral registration email                
                    }
                }
            }
        }

        public static function is_child_order($id, $current_user_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rewardpoints_referral';
            $recorded = $wpdb->get_row( "SELECT * FROM $table_name WHERE rewardpoints_referral_id LIKE '$id'" );
            $rewardpoints_referral_id = (is_object($recorded)) ? $recorded->rewardpoints_referral_id : 0;
            if ($rewardpoints_referral_id) {
                $is_parent = ($current_user_id == $recorded->rewardpoints_referral_parent_id) ? true : false;
                
                return !$is_parent;              
            }
            return true;
        }

        public static function get_referred_friend_customer($id, $current_user_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rewardpoints_referral';
            $recorded = $wpdb->get_row( "SELECT * FROM $table_name WHERE rewardpoints_referral_id LIKE '$id'" );
            $rewardpoints_referral_id = (is_object($recorded)) ? $recorded->rewardpoints_referral_id : 0;
            if ($rewardpoints_referral_id) {
                $is_parent = ($current_user_id == $recorded->rewardpoints_referral_parent_id) ? true : false;
                if (!$is_parent) {
                    $customer = new WC_Customer( $recorded->rewardpoints_referral_parent_id );
                } else {
                    $customer = new WC_Customer( $recorded->rewardpoints_referral_child_id );
                }
                return $customer;              
            }
            return null;
        }


        public static function process_referral_treatment($order_id) {
            global $wpdb;
            $order = new WC_Order( $order_id ); 
            if (!$order->get_id()) {
                return;
            }
                        
            $userId = 0;
            $customer = new WC_Customer( $order->get_customer_id() );
            $customer_meta_rewardpoints_referrer_id = $customer->get_meta('_rewardpoints_referrer');
            
            $session_referrer_id = (!is_admin()) ? WC()->session->get( 'referral_user' ) : null;
            if ($session_referrer_id == $order->get_customer_id() ) { 
                if (!is_admin())
                    WC()->session->__unset( 'referral_user' );
                $customer->delete_meta_data('_rewardpoints_referrer');
                $customer->save();
            }

            if ((int)$customer_meta_rewardpoints_referrer_id) {
                $userId = (int)$customer_meta_rewardpoints_referrer_id;
            } else if ($session_referrer_id && $session_referrer_id != $order->get_customer_id()) {
                $userId = (int)$session_referrer_id;
            }
            
            //check if referral from link...
            if ($userId) {
                $email = $customer->get_email();
                $table_name = $wpdb->prefix . 'rewardpoints_referral';
                $insert_array = array( 
                    'rewardpoints_referral_parent_id' => $userId, 
                    'rewardpoints_referral_email' => $email, 
                    'rewardpoints_referral_name' => $customer->get_first_name().' '.$customer->get_last_name(), 
                    'store_id' => null
                );

                $recorded = $wpdb->get_row( "SELECT * FROM $table_name WHERE rewardpoints_referral_email LIKE '$email'" );
                $rewardpoints_referral_id = (is_object($recorded)) ? $recorded->rewardpoints_referral_id : 0; 

                
                //insert if not already there
                if (!$rewardpoints_referral_id) {
                    $wpdb->insert($table_name, $insert_array);
                    $rewardpoints_referral_id = $wpdb->insert_id;
                    //record extra points for customer child registration (for the parent)
                    if ($parent_points = self::get_conf_referral_registration_points()) {
                        $recordPointsModel = self::insert_update_points($referrer_id, self::TYPE_POINTS_REFERRAL_REGISTRATION, $parent_points, 0, date('Y-m-d h:i:s'), null, null, true, $rewardpoints_referral_id);
                    }
                    //record extra points for customer child registration (for the child)
                    if ($child_point = self::get_conf_referrer_registration_points()) {
                        $recordPointsModel = self::insert_update_points($user_id, self::TYPE_POINTS_REFERRAL_REGISTRATION, $child_point, 0, date('Y-m-d h:i:s'), null, null, true, $rewardpoints_referral_id);
                    }               
                }
                if (!is_admin())
                    WC()->session->__unset( 'referral_user' );
            }
            
            $rewardPoints = self::get_conf_referral_point_value();
            $rewardPointsChild = self::get_conf_referral_child_point_value();
            $rewardPointsReferralMinOrder = self::get_conf_referral_min_order();
            
            $referralPointMethod = self::get_conf_referral_point_method();
            $rewardPoints = self::referral_points_entry($order, $referralPointMethod, $rewardPoints);
            $rewardPointsChild = self::referral_child_points_entry($order, $rewardPointsChild);
    
            
            $base_subtotal = self::get_cart_value_without_discount($order);
            /*if ($this->getExcludeTax($order->getStoreId())) {
                $base_subtotal = $base_subtotal - $order->getBaseTaxAmount();
            }*/
    
            if ((($rewardPoints > 0 || $rewardPointsChild > 0) && $email) && ($rewardPointsReferralMinOrder == 0 || $rewardPointsReferralMinOrder <= $base_subtotal)) {
                self::process_referral_insertion($order, $email, $rewardPoints, $rewardPointsChild, false);
            }
        }

        public static function referral_points_entry($order, $referralPointMethod, $rewardPoints = 0) {

            $order_id = $order->get_id();            
            $cart_value = self::get_cart_value_without_discount($order);

            /*if (!$order->getBaseSubtotalInclTax()) {
                $order->setBaseSubtotalInclTax($order->getBaseSubtotal() + $order->getBaseTaxAmount());
            }*/
            if ($referralPointMethod == self::RATIO_POINTS) {
                //$rate = $order->getBaseToOrderRate();
                $rate = 1;
                if ($rewardPoints > 0) {
                    //$items = $order->getAllVisibleItems();
                    //$points = $this->getAllItemsPointsValue($items, $order->getQuote(), true, $rate);
                    //$rewardPointsChild = $this->getPointMax($points);
                }
            } else if ($referralPointMethod == self::CART_SUMMARY) {
                if (($base_subtotal = $cart_value) && $rewardPoints > 0) {
                    $summary_points = $base_subtotal * $rewardPoints;
                    /*if ($this->getExcludeTax($order->getStoreId())) {
                        $summary_points = $summary_points - $order->getBaseTaxAmount();
                    }*/
                    //$rewardPoints = $this->mathActionOnTotalEarn($summary_points);
                    $rewardPoints = self::mathActionOnTotalEarn($summary_points);
                }
            }
            return $rewardPoints;
        }

        public static function referral_child_points_entry($order, $rewardPointsChild) {
            $order_id = $order->get_id();            
            $cart_value = self::get_cart_value_without_discount($order);
            $referralChildPointMethod = self::get_conf_referral_child_points_method();
            if ($referralChildPointMethod == self::RATIO_POINTS) {
                $rate = 1;
                if ($rewardPointsChild > 0) {
                    //$items = $order->getAllVisibleItems();
                    //$points = $this->getAllItemsPointsValue($items, $order->getQuote(), true, $rate);
                    //$rewardPointsChild = $this->getPointMax($points, $order->getStoreId());
                }
            } else if ($referralChildPointMethod == self::CART_SUMMARY) {
                if (($base_subtotal = $cart_value) && $rewardPointsChild > 0) {
                    $summary_points = $base_subtotal * $rewardPointsChild;
                    /*if ($this->getExcludeTax($order->getStoreId())) {
                        $summary_points = $summary_points - $order->getBaseTaxAmount();
                    }*/
                    $rewardPointsChild = self::mathActionOnTotalEarn($summary_points);
                }
            }
            return $rewardPointsChild;
        }


        public static function process_referral_insertion($order, $email, $rewardPoints, $rewardPointsChild, $escape_status_verification = false) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rewardpoints_referral';

            //check if referred friend is in referral table
            $recorded = $wpdb->get_row( "SELECT * FROM $table_name WHERE rewardpoints_referral_email LIKE '$email'" );
            $rewardpoints_referral_id = (is_object($recorded)) ? $recorded->rewardpoints_referral_id : 0; 

            if ($rewardpoints_referral_id) {
                if (!$recorded->rewardpoints_referral_status) {
                    //update status
                    $parent = new WC_Customer( $recorded->rewardpoints_referral_parent_id );
                    $child = new WC_Customer( $recorded->rewardpoints_referral_child_id );
                    $status = "wc-".$order->get_status();                    

                    if ($rewardPoints > 0) {                        
                        $recordPointsModel = self::insert_update_points($recorded->rewardpoints_referral_parent_id, $order->get_id(), $rewardPoints, 0, date('Y-m-d h:i:s'), $status, null, true, $rewardpoints_referral_id);                            
                    }

                    if ($rewardPointsChild > 0) {
                        $recordPointsModel = self::insert_update_points($recorded->rewardpoints_referral_child_id, $order->get_id(), $rewardPointsChild, 0, date('Y-m-d h:i:s'), $status, null, true, $rewardpoints_referral_id);
                    }

                    $insert_array = array(
                        'rewardpoints_referral_child_id' => $order->get_customer_id(), 
                        'rewardpoints_referral_status' => '1',
                        'rewardpoints_referral_name' => $order->get_billing_first_name().' '.$order->get_billing_last_name()
                    );
                    $result = $wpdb->update($table_name, $insert_array, array('rewardpoints_referral_id' => $rewardpoints_referral_id));

                    //TODO send confirmation email
                    //$referralModel->sendConfirmation($parent, $child, $parent->getEmail(), $parent->getName(), $storeId);
                    $email_referral = WC()->mailer()->get_emails()['WC_Rewardpoints_Referral'];
                    // Sending order confirmation email to customer
                    $email_referral->trigger( $parent, $child );
                }
            }
        }
        
        public static function get_referring_url($user_id)
        {
            return add_query_arg( 'referral', base64_encode('user-'.$user_id.'j2t'), home_url() );
        }

        public static function rewardpoints_referral_content() {
            global $wpdb;
            ?>
            <h3><?php echo __('My Referral Program', 'j2t-reward-points-for-woocommerce') ?></h3>


<?php

    $customer_id = get_current_user_ID();
?>
            
            


            <?php
                $table_name = $wpdb->prefix . 'rewardpoints_referral';
            
                $footerHtml     = "";
                $query             = "SELECT * FROM $table_name WHERE 	rewardpoints_referral_parent_id = '$customer_id'";
                $total_query     = "SELECT COUNT(1) FROM (${query}) AS combined_table";
                $total             = $wpdb->get_var( $total_query );
                $items_per_page = 15;
                $page             = isset( $_GET['cpage'] ) ? abs( (int) sanitize_text_field($_GET['cpage']) ) : 1;
                $offset         = ( $page * $items_per_page ) - $items_per_page;
                $results         = $wpdb->get_results( $query . " ORDER BY rewardpoints_referral_id DESC LIMIT ${offset}, ${items_per_page}" );
                $totalPage         = ceil($total / $items_per_page);

                //pagination
                if($totalPage > 1){

                    $footerHtml     =  '<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination"><span>Page '.$page.' of '.$totalPage.'</span> '.paginate_links( array(
                    'base' => add_query_arg( 'cpage', '%#%' ),
                    'format' => '',
                    'prev_text' => __('Previous', 'woocommerce'),
                    'next_text' => __('Next', 'woocommerce'),
                    'total' => $totalPage,
                    'current' => $page
                    )).'</div>';
                }

                $hasReferredFriends = (count($results)) ? true : false;



                $template_base = plugin_dir_path( __FILE__ ).'templates/';

                echo wc_get_template_html( 'frontend/wc-account-referral.php', array(
                    'referral_registration_points'   => self::get_conf_referral_registration_points(),
                    'referrer_registration_points' => self::get_conf_referrer_registration_points(),
                    'referral_point_value' => self::get_conf_referral_point_value(),
                    'referral_child_points_method' => self::get_conf_referral_child_points_method(),
                    'static_method' => self::STATIC_VALUE,
                    'ratio_method' => self::RATIO_POINTS,
                    'cart_summary_method' => self::CART_SUMMARY,
                    'referral_child_point_value' => self::get_conf_referral_child_point_value(),
                    'referral_min_order' => self::get_conf_referral_min_order(),
                    'referral_share_with_addthis' => self::get_conf_referral_share_with_addthis(),
                    'referral_permanent' => self::get_conf_referral_permanent(),
                    'referral_url' => self::get_referring_url($customer_id),
                    'referral_custom_code' => self::get_conf_referral_custom_code(),
                    'referral_addthis_code' => self::get_conf_referral_addthis_code(),
                    'referral_addthis_account_name' => self::get_conf_referral_addthis_account_name(),
                    'hasReferredFriends' => $hasReferredFriends,
                    'results' => $results,
                    'footerHtml' => $footerHtml
                ), '', $template_base ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped


                
            
        }

        public static function rewardpoints_content() {
            global $wpdb;
            ?>
            <h3><?php echo __('Reward Points', 'j2t-reward-points-for-woocommerce') ?></h3>
            <!--<p>
            <?php echo __('In this area, you can review your point history.', 'j2t-reward-points-for-woocommerce') ?>
            </p>-->
            <?php

            $customer_id = get_current_user_ID();

            $table_name = $wpdb->prefix . 'rewardpoints_account';
            
            $footerHtml     = "";
            $query             = "SELECT * FROM $table_name WHERE customer_id = '$customer_id'";
            $total_query     = "SELECT COUNT(1) FROM (${query}) AS combined_table";
            $total             = $wpdb->get_var( $total_query );
            $items_per_page = 15;
            $page             = isset( $_GET['cpage'] ) ? abs( (int) sanitize_text_field($_GET['cpage']) ) : 1;
            $offset         = ( $page * $items_per_page ) - $items_per_page;
            $results         = $wpdb->get_results( $query . " ORDER BY date_order DESC LIMIT ${offset}, ${items_per_page}" );
            $totalPage         = ceil($total / $items_per_page);

            //pagination
            if($totalPage > 1){

                $footerHtml     =  '<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination"><span>Page '.$page.' of '.$totalPage.'</span> '.paginate_links( array(
                'base' => add_query_arg( 'cpage', '%#%' ),
                'format' => '',
                'prev_text' => __('Previous', 'woocommerce'),
                'next_text' => __('Next', 'woocommerce'),
                'total' => $totalPage,
                'current' => $page
                )).'</div>';
            }

            $hasPoints = (count($results)) ? true : false;

            $class = new J2t_Rewardpoints();
            $points_type = $class->getPointsTypeToArray();

            $template_base = plugin_dir_path( __FILE__ ).'templates/';

            //<div class="referral-program-points">'.__('Referral Extra Points').'</div>'

            echo wc_get_template_html( 'frontend/wc-account-statistics.php', array(
                'price_unit'   => wc_price(1),
                'point_price_unit'	=> self::get_conf_money_point_value(),
                'price_point_unit' => self::get_conf_points_money_value(),
                'birthday_points' => self::get_birthday_points(),
                'registration_points' => self::get_registration_points(),
                'hasPoints' => $hasPoints,
                'points_type' => $points_type,
                'review_product_type' => self::TYPE_POINTS_REVIEW,
                'available_points' => self::get_user_available_points($customer_id),
                'gathered_points' => self::get_user_total_gathered_points($customer_id),
                'total_points' => self::get_user_total_used_points($customer_id),
                'results' => $results,
                'footerHtml' => $footerHtml
            ), '', $template_base ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped


            //echo '<p>Welcome to the WooCommerce support area. As a premium customer, you can submit a ticket should you have any WooCommerce issues with your website, snippets or customization. <i>Please contact your theme/plugin developer for theme/plugin-related support.</i></p>';
            //echo do_shortcode( ' /* your shortcode here */ ' );
        }


        public static function before_checkout_create_order( $order, $data ) {
            $gatheringPoints = self::get_cart_point_worthing_value($order);
            
            $order->update_meta_data( '_rewardpoints_gathered', $gatheringPoints );
            $pointsUsed = WC()->session->get( 'j2t_rewardpoints' );
            $order->update_meta_data( '_rewardpoints_used', $pointsUsed );
            WC()->session->__unset( 'j2t_rewardpoints' );            
        }

        public static function add_points_order_note( $order_id ) {
            $order = new WC_Order( $order_id ); 
            $gatheringPoints = self::get_cart_point_worthing_value($order);
            // Add the note
            $note = sprintf(__( '%s point(s) collected on this order', 'j2t-reward-points-for-woocommerce' ), $gatheringPoints); 
            $order->add_order_note( $note );
        }

        public static function rewardpoints_add_email_order_meta( $order_obj, $sent_to_admin, $plain_text ){
            $gathered_points = get_post_meta( $order_obj->get_order_number(), '_rewardpoints_gathered', true );
            $used_points = get_post_meta( $order_obj->get_order_number(), '_rewardpoints_used', true );

            $gathered_points = ($gathered_points) ? $gathered_points : 0;
            $used_points = ($used_points) ? $used_points : 0;
            
            // don't display if gathered points is empty
            //if( empty( $gathered_points ) )
            //    return;

            // if not plaintext email
            if ( $plain_text === false ) {
            ?>
                <h2>
                    <?php echo __('Rewardpoint Information', 'j2t-reward-points-for-woocommerce');?>
                </h2>
                <ul>
                <li><strong><?php echo sprintf(__('Gathered Points: %s', 'j2t-reward-points-for-woocommerce'), $gathered_points);?></strong></li>
                <li><strong><?php echo sprintf(__('Used Points: %s', 'j2t-reward-points-for-woocommerce'), $used_points);?></strong></li>
                </ul>

            <?php             
            } else {            
                echo __('Rewardpoint Information', 'j2t-reward-points-for-woocommerce')."\n
                ".sprintf(__('Gathered Points: %s', 'j2t-reward-points-for-woocommerce'), $gathered_points)."
                ".sprintf(__('Used Points: %s', 'j2t-reward-points-for-woocommerce'), $used_points);            
            }
            
        }

        public static function get_current_user_roles() {
            if( is_user_logged_in() ) {
                $user = wp_get_current_user();
                $roles = ( array ) $user->roles;
                return $roles;
            } else {
                return array();
            }
        }

        public static function is_dob_activated_by_user() {
            return (get_option( 'wc_settings_tab_j2trewards_dob_add_field', false ));
        }
        public static function get_birthday_points() {
            return (get_option( 'wc_settings_tab_j2trewards_birthday_points', 0 ));
        }


        public static function get_rewardpoints_inline_image() {
            $image_id = get_option( 'wc_settings_tab_j2trewards_rewardpoints_id_inline_image', 0 );
            return ($image_id) ? '<span class="inline-rewardpoints_img">'.wp_get_attachment_image($image_id, 'thumbnail').'</span>' : null;
            
        }
        public static function get_registration_points() {
            return (get_option( 'wc_settings_tab_j2trewards_registration_points', 0 ));
        }
        public static function get_birthday_prior_verification() {
            return (get_option( 'wc_settings_tab_j2trewards_dob_prior_verification', 0 ));
        }
        public static function get_birthday_delay() {
            return (get_option( 'wc_settings_tab_j2trewards_dob_points_delay', 0 ));
        }
        public static function get_birthday_duration() {
            return (get_option( 'wc_settings_tab_j2trewards_dob_points_curation', 0 ));
        }


        //check if the module has been activated by user
        public static function is_module_activated_by_user() {            
            $current_user_roles = self::get_current_user_roles();

            $exclude_user = false;
            $excluded_roles = get_option( 'wc_settings_tab_j2trewards_exluded_roles', array() );

            if (count($current_user_roles) > 0) {
                foreach ($current_user_roles as $current_user_role) {
                    if (in_array($current_user_role, $excluded_roles)) {
                        $exclude_user = true;
                    }
                }
            }
            return (get_option( 'wc_settings_tab_j2trewards_active', false ) && !$exclude_user);
        }

        public static function show_on_grid() {
            return (get_option( 'wc_settings_tab_j2trewards_show_on_grid', false ));
        }
        public static function show_on_product_page() {
            return (get_option( 'wc_settings_tab_j2trewards_show_on_product_page', false ));
        }

        //wc_settings_tab_j2trewards_trash_action
        //wc_settings_tab_j2trewards_trash_delete
        public static function allow_trash_action() {
            return (get_option( 'wc_settings_tab_j2trewards_trash_action', false ));
        }
        public static function allow_trash_delete() {
            return (get_option( 'wc_settings_tab_j2trewards_trash_delete', false ));
        }



        public static function mathActionOnTotalEarn($value) {
            switch (self::get_math_total_earn_method()) {
                case self::MATH_CEIL:
                    $value = ceil($value);
                    break;
                case self::MATH_FLOOR:
                    $value = floor($value);
                    break;
                case self::MATH_ROUND:
                    $value = round($value);
                    break;
            }
            return $value;
        }

        public static function mathActionOnCatalogEarn($value) {
            switch (self::get_math_catalog_pages_method()) {
                case self::MATH_CEIL:
                    $value = ceil($value);
                    break;
                case self::MATH_FLOOR:
                    $value = floor($value);
                    break;
                case self::MATH_ROUND:
                    $value = round($value);
                    break;
            }
            return $value;
        }

        public static function getJSMathMethod()
        {
            $value = 'total_points';
            switch (self::get_math_catalog_pages_method()) {
                case self::MATH_CEIL:
                    $value = 'Math.ceil(total_points)';
                    break;
                case self::MATH_FLOOR:
                    $value = 'Math.floor(total_points)';
                    break;
                case self::MATH_ROUND:
                    $value = 'Math.round(total_points)';
                    break;
            }
            return $value;
        }


        public static function get_math_total_earn_method() {            
            return (get_option( 'wc_settings_tab_j2trewards_math_total_earn', 'ceil' ));
        }
        public static function get_math_catalog_pages_method() {            
            return (get_option( 'wc_settings_tab_j2trewards_math_catalog_pages', 'ceil' ));
        }
        

        public static function get_user_available_points($customer_id) {
            return self::get_user_total_gathered_points($customer_id) - self::get_user_total_used_points($customer_id);
        }
        public static function get_user_total_used_points($customer_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rewardpoints_account';

            $valid_used_statuses = get_option( 'wc_settings_tab_j2trewards_valid_used_statuses', array() );
            $recorded = $wpdb->get_row( "SELECT SUM(points_spent) as used_points FROM $table_name WHERE 
                (
                    rewardpoints_status IN ('".implode("','", $valid_used_statuses)."') OR order_id < 0
                ) AND customer_id = $customer_id
                " );
            
            return (is_object($recorded)) ? $recorded->used_points : 0;
        }
        public static function get_user_total_gathered_points($customer_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rewardpoints_account';
            $valid_gathered_statuses = get_option( 'wc_settings_tab_j2trewards_valid_statuses', array() );
            $recorded = $wpdb->get_row( "SELECT SUM(points_current) as accumulated_points FROM $table_name WHERE 
                ( rewardpoints_status IN ('".implode("','", $valid_gathered_statuses)."') OR order_id < 0)
                AND customer_id = $customer_id" );                
            return (is_object($recorded)) ? $recorded->accumulated_points : 0;
        }


        public static function save_events_points($customer_id, $type, $linker_id = null) {
            if ($type == 'newsletter' && $points = self::get_conf_newsletter_points()) {
                $recordPointsModel = self::insert_update_points($customer_id, self::TYPE_POINTS_NEWSLETTER, $points, 0, date('Y-m-d h:i:s'), null, null, true);
            }
            if ($type == 'product_review' && $points = self::get_conf_review_points()) {
                //add extra verification id (product id)
                $recordPointsModel = self::insert_update_points($customer_id, self::TYPE_POINTS_REVIEW, $points, 0, date('Y-m-d h:i:s'), null, null, true, null, $linker_id);
            }
        }

        public static function rewardpoints_review_status_update($comment_id, $status) {
            
            $comment = get_comment( $comment_id );
            $customer_id = $comment->user_id;
            $linker_id = $comment->comment_post_ID;
            //'hold', '0', 'approve', '1', 'spam', and 'trash'.
            if (($status == 1 || $status == 'approve') && $customer_id && $comment->post_type == 'product') {
                self::save_events_points($customer_id, 'product_review', $linker_id);
            }
        }

        public static function rewardpoints_delete_order($order_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rewardpoints_account';
            $valid_gathered_statuses = get_option( 'wc_settings_tab_j2trewards_valid_statuses', array() );

            $order = new WC_Order( $order_id ); 
            
            if (is_object($order) && $order->get_id()) {
                //delete all order without any valid statuses
                $wpdb->query( "DELETE FROM $table_name WHERE rewardpoints_status NOT IN ('".implode("','", $valid_gathered_statuses)."') AND order_id = $order_id" );

                $update_array = array( 
                    'order_id' => '-1', 
                    'rewardpoints_description' => sprintf(__('Order #%sDeleted by Admin', 'j2t-reward-points-for-woocommerce'), $order_id)

                );
                $wpdb->update($table_name, $update_array, array('order_id' => $order_id));
            }
                        
        }


        public static function update_rewardpoint_status($order_id) {
            global $wpdb;
            $order = new WC_Order( $order_id ); 
            
            if (is_object($order) && $order->get_id()) {
                $table_name = $wpdb->prefix . 'rewardpoints_account';
                $status = "wc-".$order->get_status();
                
                $update_array = array( 
                    'rewardpoints_status' => $status,
                    'rewardpoints_state' => $status
                );
                $updated_data_qty = $wpdb->update($table_name, $update_array, array('order_id' => $order_id));

                //refresh stats for all users linked to this order id
                if ($updated_data_qty !== false && $updated_data_qty > 1) {
                    $results = $wpdb->get_results( "SELECT * FROM $table_name WHERE order_id = $order_id" );
                    foreach ($results as $result) {
                        self::reward_save_flat_account($result->customer_id);
                    }
                }                
            }
        }

        public static function rewardpoints_order_status_change_trash($order_id) {
            self::update_rewardpoint_status($order_id);
        }

        public static function rewardpoints_order_status_change($order_id) {            
            self::update_rewardpoint_status($order_id);
        }

        public static function reward_save_order($order) {
            $order_id = $order->get_id();
            $order = wc_get_order($order_id); //get the freshest order data (to prevent twite insertion)
            $gatheringPoints = self::get_cart_point_worthing_value($order);
            //check if points already recorded, insert or update

            $customer_id = $order->get_customer_id();
            $user_id = $order->get_user_id();
            $created_date = ($order->get_date_created()) ? $order->get_date_created()->date("Y-m-d, H:i:s") : $order->get_date_created();
            $modified_date = ($order->get_date_modified()) ? $order->get_date_modified()->date("Y-m-d, H:i:s") : $order->get_date_modified();
            $completed_date = ($order->get_date_completed()) ? $order->get_date_completed()->date("Y-m-d, H:i:s") : $order->get_date_completed();
            $total_discount = $order->get_discount_total();
            $status = "wc-".$order->get_status();

            //avaiable order meta : _rewardpoints_gathered & _rewardpoints_used
            $usedPoints = $order->get_meta('_rewardpoints_used');
            self::insert_update_points($customer_id, $order_id, $gatheringPoints, $usedPoints, $created_date, $status);
            self::process_referral_treatment($order_id);
            //WC()->session->__unset( 'j2t_rewardpoints' );
        }

        public static function reward_save_flat_account($customer_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rewardpoints_flat_account';
            
            $insert_array = array( 
                'user_id' => $customer_id, 
                //'store_id' => '', 
                'points_collected' => self::get_user_total_gathered_points($customer_id), 
                'points_used' => self::get_user_total_used_points($customer_id), 
                'points_current' => self::get_user_total_gathered_points($customer_id) - self::get_user_total_used_points($customer_id),
                //'points_waiting' => '',
                //'points_not_available' => '', 
                //'points_lost' => ''
            );
            $recorded = $wpdb->get_row( "SELECT * FROM $table_name WHERE user_id = $customer_id" );
            $flat_account_id = (is_object($recorded)) ? $recorded->flat_account_id : 0;

            //insert or update if data exists
            if ($flat_account_id) {
                $result = $wpdb->update($table_name, $insert_array, array('flat_account_id' => $flat_account_id));
            } else {
                $wpdb->insert($table_name, $insert_array);
            }
        }

        public function save_admin_points($customer_id, $points, $description, $date) {
            $used_points = ($points < 0 ) ? abs($points) : null;
            $points_added = ($points > 0 ) ? $points : null;
            
            $res = self::insert_update_points($customer_id, self::TYPE_POINTS_ADMIN, $points_added, $used_points, $date, null, $description);

            $gathered_points = self::get_user_total_gathered_points($customer_id);
            $used_points = self::get_user_total_used_points($customer_id);
            $available_points = $gathered_points - $used_points;

            return array(
                'gathered_points' => $gathered_points,
                'used_points' => $used_points,
                'available_points' => $available_points,
                'return_val' => $res
            );
        }

        public static function insert_update_points($customer_id, $order_id, $points, $usedPoints, $order_date, $status = null, $description = null, $process_once = false, $referrer_id = null, $linker_id = null) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'rewardpoints_account';
            
            $insert_array = array( 
                'customer_id' => $customer_id, 
                //'store_id' => $welcome_name, 
                'order_id' => $order_id, 
                //'rewardpoints_description' => $welcome_name, 
                //'rewardpoints_linker' => $welcome_text,
                //'date_start' => $welcome_name, 
                //'date_end' => $welcome_text,
                //'convertion_rate' => $welcome_name, 
                //'rewardpoints_referral_id' => $welcome_text,
                'date_order' => $order_date, 
                'date_insertion' => $order_date,
                //'period' => $welcome_name, 
                //'quote_id' => $welcome_text
            ) ;

            if ($description) {
                $insert_array['rewardpoints_description'] = $description;
            }

            //if ($points > 0) {
                $insert_array['points_current'] = $points;
            //} 
            //if ($usedPoints > 0) {
                $insert_array['points_spent'] = $usedPoints;
            //}
            if ($status) {
                $insert_array['rewardpoints_status'] = $status;
                $insert_array['rewardpoints_state'] = $status;
            }
            if ($referrer_id) {
                $insert_array['rewardpoints_referral_id'] = $referrer_id;
            }
            if ($linker_id) {
                $insert_array['rewardpoints_linker'] = $linker_id;
            }


            $result = false;
            if ($order_id > 0) {
                //insert or update
                if ($referrer_id) {
                    $extra = "AND rewardpoints_referral_id = $referrer_id";
                } else {
                    $extra = "AND (rewardpoints_referral_id IS NULL OR rewardpoints_referral_id = '')";
                }

                $recorded = $wpdb->get_row( "SELECT * FROM $table_name WHERE order_id = $order_id AND customer_id = $customer_id $extra" );
                $rewardpoints_account_id = (is_object($recorded)) ? $recorded->rewardpoints_account_id : 0; 

                //insert or update if data exists
                if ($rewardpoints_account_id) {
                    //$result = $wpdb->update($table_name, $insert_array, array('order_id' => $order_id, 'customer_id' => $customer_id));
                    //remove points update feature in order to only update status (avoid miscalculation for referral points)
                    //unset($insert_array['points_current']);
                    //unset($insert_array['points_spent']);
                    $result = $wpdb->update($table_name, $insert_array, array('rewardpoints_account_id' => $rewardpoints_account_id));
                } else {
                    $result =$wpdb->insert($table_name, $insert_array);
                }
                //update status in case of referral program insertion
                if ($status) {
                    $wpdb->update($table_name, array('rewardpoints_status' => $status, 'rewardpoints_state' => $status), array('order_id' => $order_id));
                }

            } else {
                $can_process = true;
                if ($process_once) {
                    $extra = "";
                    if ($referrer_id) {
                        $extra = " AND rewardpoints_referral_id = $referrer_id";
                    }
                    if ($linker_id) {
                        $extra .= " AND rewardpoints_linker = $linker_id";
                    }
                    $recorded = $wpdb->get_row( "SELECT * FROM $table_name WHERE order_id = $order_id AND customer_id = $customer_id $extra" );
                    $can_process = (is_object($recorded) && $recorded->rewardpoints_account_id) ? false : $can_process; 
                }
                if ($can_process) {
                    $result = $wpdb->insert( 
                        $table_name, 
                        $insert_array
                    );
                    
                }                
            }
            self::reward_save_flat_account($customer_id);
            return ($result === false) ? true : false;
        }

        public static function rewardpoints_front_scripts() { 
            wp_enqueue_style( 'j2trewardpoints',  plugin_dir_url( __FILE__ ) . '/front.css' );
            wp_enqueue_script( 'j2trewardpoints', plugin_dir_url( __FILE__ ) . '/front.js', array( 'jquery', 'clipboard' ) );
            
            $j2tRewardpoints = array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'j2trewardpoints_nonce' => wp_create_nonce( 'j2t-rewardpoints-verify-nonce' ),
            );
            wp_localize_script( 'j2trewardpoints', 'j2t_rewardpoints', $j2tRewardpoints );
        }



        public static function get_max_points_on_cart( $cart, $usedValue ) {
            $pointUsageValue = self::get_conf_points_money_value();
            
            $userId = get_current_user_ID();
            $userPoints = self::get_user_available_points($userId);

            //check max point user can apply on shopping cart
            $maxPoint = round($cart->get_subtotal() * $pointUsageValue);
            $maxPoint = min($userPoints, $maxPoint);
            return min($maxPoint, $usedValue);
        }


        public static function reward_add_discount( $cart_object ) {
            $pointUsageValue = self::get_conf_points_money_value();
            if ( (is_admin() && ! defined( 'DOING_AJAX' )) || !get_current_user_ID() ) return;

            $cart_total = $cart_object->subtotal_ex_tax;            
            $pointsUsed = WC()->session->get( 'j2t_rewardpoints' );
            
            if ($pointsUsed) {
                $maxPoint = self::get_max_points_on_cart( $cart_object, $pointsUsed );
                if ($maxPoint < $pointsUsed) {
                    WC()->session->set( 'j2t_rewardpoints', $maxPoint );
                    $pointsUsed = $maxPoint;
                }
                if ($pointsUsed) {
                    $label_text = sprintf(__( "Points (%s used)", 'j2t-reward-points-for-woocommerce' ), $pointsUsed);
                    $discount = $pointsUsed/$pointUsageValue;
                    $cart_object->add_fee( $label_text, -$discount, false );
                }
            }
            
        }


        public static function rewardpoints_checkout_coupons( $subtotal, $compound, $cart ) {     
            return true;
            $pointUsageValue = self::get_conf_points_money_value();
            
            $userId = get_current_user_ID();
            $userPoints = self::get_user_available_points($userId);

            //check max point user can apply on shopping cart
            $maxPoint = round($cart->get_subtotal() * $pointUsageValue);
            $maxPoint = min($userPoints, $maxPoint);

            $pointsUsed = WC()->session->get( 'j2t_rewardpoints' );
            
            if ($maxPoint < $pointsUsed) {
                WC()->session->set( 'j2t_rewardpoints', $maxPoint );
                $pointsUsed = $maxPoint;
            }
            
            if ($pointsUsed) {
                $store_credit = $pointsUsed/$pointUsageValue;
        
                if($store_credit > 0){
                    // Setup our virtual coupon
                    $coupon_name = 'points';
                    $coupons = array($coupon_name => $store_credit);       

                    $originalCoupons = WC()->cart->get_applied_coupons();
                    $appliedCoupons = WC()->cart->get_applied_coupons();
                    $appliedCoupons[] = $coupon_name;
                    
                    $appliedCouponsObj = WC()->cart->coupon_discount_totals;

                    foreach ($appliedCouponsObj as $code => $amount) {
                        $coupons[$code] = $amount;
                    }                    
                    
                    // Apply the store credit coupon to the cart & update totals
                    WC()->cart->applied_coupons = $appliedCoupons;
                    $totalDiscount = $store_credit+WC()->cart->get_discount_total();

                    WC()->cart->set_discount_total($totalDiscount);
                    WC()->cart->set_total( WC()->cart->get_subtotal() - $totalDiscount);
                    
                    WC()->cart->coupon_discount_totals = $coupons;
                }
            }
        
            return $subtotal; 
        }

        public static function get_cart_value_without_discount($order = null) {
            $pointUsageValue = self::get_conf_points_money_value();
            //cart value without discount
            if ($order != null) {
                $cartValue = $order->get_total();
            } else {
                $cartValue = WC()->cart->get_cart_contents_total();
            }
            
            //get current point usage value            
            if ($order == null && $pointsUsed = WC()->session->get( 'j2t_rewardpoints' )) {
                $pointsDiscountValue = $pointsUsed/$pointUsageValue;
                $cartValue -= $pointsDiscountValue;
            }
            return $cartValue;
        }

        public static function get_conf_money_point_value() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_money_points', 1 );
        }

        public static function get_conf_points_money_value() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_points_money', 50 );
        }

        public static function get_cart_point_worthing_value($order = null) {
            $pointGatheringRatio = self::get_conf_money_point_value();
            $cartValue = self::get_cart_value_without_discount($order);
            $gatheringPoints = self::mathActionOnTotalEarn($cartValue * $pointGatheringRatio);
            return $gatheringPoints;
        }


        public static function get_conf_referral_point_value() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_points', 0 );
        }
        public static function get_conf_referral_point_method() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_points_method', 'static' );
        }
        public static function get_conf_referral_child_point_value() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_child_points', 0 );
        }
        public static function get_conf_referral_child_points_method() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_child_points_method', 'static' );
        }
        public static function get_conf_referral_min_order() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_min_order', 0 );
        }
        public static function get_conf_referral_permanent() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_permanent', 0 );
        }
        public static function get_conf_referral_redirection() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_redirection', '' );
        }
        public static function get_conf_referral_share_with_addthis() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_addthis', 0 );
        }
        public static function get_conf_referral_addthis_account_name() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_addthis_account', 0 );
        }
        public static function get_conf_referral_addthis_code() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_addthis_code', 0 );
        }
        public static function get_conf_referral_custom_code() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_custom_code', 0 );
        }
        public static function get_conf_referral_registration_points() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referred_registration_points', 0 );
        }

        public static function get_conf_referrer_registration_points() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referrer_registration_points', 0 );
            
        }

        public static function get_conf_referral_guestdisabled() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_referral_guestdisabled', 0 );
        }

        public static function get_conf_newsletter_points() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_newsletter_points', 0 );
            
        }
        public static function get_conf_review_points() {
            return WC_Admin_Settings::get_option( 'wc_settings_tab_j2trewards_review_points', 0 );
            
        }
        

        public static function reward_checkout_form() {
            $gatheringPoints = self::get_cart_point_worthing_value();
            $userId = get_current_user_ID();
            $userPoints = 0;
            
            if ($userId) {
                $userPoints = self::get_user_available_points($userId);
                ?>

                <div class="rewardpoints-checkout-form">
                    <label for="reward_points" class=""><?php echo __('Reward Points', 'j2t-reward-points-for-woocommerce')?>&nbsp;</label>
                    <!--<h3><?php echo __('Reward Points', 'j2t-reward-points-for-woocommerce')?></h3>-->
                    <div class="coupon j2t-rewardpoints-coupon">
                            <input type="number" min="0" name="reward_points" class="input-text" id="reward_points" value="<?php echo esc_attr(WC()->session->get( 'j2t_rewardpoints' ));?>" placeholder="<?php esc_attr_e( 'Points', 'j2t-reward-points-for-woocommerce' ); ?>"/>
                            <button onclick="$('#remove_points').val(0)" class="button j2t_points_apply" name="j2t_points_apply" id="j2t_points_apply" value="<?php esc_attr_e( 'Apply Points', 'j2t-reward-points-for-woocommerce' ); ?>" data-point="<?php echo esc_attr( $userPoints ); ?>" data-id="<?php echo esc_attr( $userId ); ?>" data-order-limit="0"><?php esc_html_e( 'Apply Points', 'j2trewardoints' ); ?></button>
                            <?php if (WC()->session->get( 'j2t_rewardpoints' ) > 0):?>
                                <button onclick="jQuery('#remove_points').val(1)" class="button j2t_points_remove" name="j2t_points_remove" id="j2t_points_remove" value="<?php esc_attr_e( 'Remove Points', 'j2t-reward-points-for-woocommerce' ); ?>" data-point="<?php echo esc_attr( $userPoints ); ?>" data-id="<?php echo esc_attr( $userId ); ?>" data-order-limit="0"><?php esc_html_e( 'Remove Points', 'j2trewardoints' ); ?></button>
                            <?php endif; ?>
                            <input type="hidden" name="remove_points" id="remove_points" value="0" />
                            <p class="points-user-has"><?php printf(
                                    __('You currently have %s point(s)', 'j2t-reward-points-for-woocommerce'),
                                    esc_html( $userPoints )
                                );?></p>
                            <p class="points-order-gather"><?php printf(
                                    __('With this shopping cart, you will gather %s point(s).', 'j2t-reward-points-for-woocommerce'),
                                    esc_html( $gatheringPoints )
                                );?></p>
                            <?php if (WC()->session->get( 'j2t_rewardpoints' ) > 0) {?>
                                <p class="points-currently-used"><?php printf(
                                    __('You are currently using %s point(s).', 'j2t-reward-points-for-woocommerce'),
                                    esc_html( WC()->session->get( 'j2t_rewardpoints' ) )
                                );?></p>
                            <?php }?>
                    </div>	
                </div>
                <?php
            } else {
                ?>
                <div class="rewardpoints-checkout-form">
                    <label for="reward_points" class=""><?php echo __('Reward Points', 'j2t-reward-points-for-woocommerce')?>&nbsp;</label>
                    <div class="coupon j2t-rewardpoints-coupon">
                    <p><?php printf(
                        __('You must be <a href="%s">logged</a> in order to gather or use points.', 'j2t-reward-points-for-woocommerce'),
                        get_permalink( get_option('woocommerce_myaccount_page_id') )
                    );?></p>
                    </div>
                </div>
                <?php
            }
        }

        public static function reward_cart_form() {
            $gatheringPoints = self::get_cart_point_worthing_value();
            $userId = get_current_user_ID();
            $userPoints = 0;
        
            if ($userId) {
                $userPoints = self::get_user_available_points($userId);
                ?>
                <div class="coupon j2t-rewardpoints-coupon">
                        <input type="number" min="0" name="reward_points" class="input-text" id="reward_points" value="<?php echo WC()->session->get( 'j2t_rewardpoints' );?>" placeholder="<?php esc_attr_e( 'Points', 'j2t-reward-points-for-woocommerce' ); ?>"/>
                        <button onclick="$('#remove_points').val(0)" class="button j2t_points_apply" name="j2t_points_apply" id="j2t_points_apply" value="<?php esc_html_e( 'Apply Points', 'j2t-reward-points-for-woocommerce' ); ?>" data-point="<?php echo esc_html( $userPoints ); ?>" data-id="<?php echo esc_html( $userId ); ?>" data-order-limit="0"><?php esc_html_e( 'Apply Points', 'j2trewardoints' ); ?></button>
                        <?php if (WC()->session->get( 'j2t_rewardpoints' ) > 0):?>
                            <button onclick="jQuery('#remove_points').val(1)" class="button j2t_points_remove" name="j2t_points_remove" id="j2t_points_remove" value="<?php esc_html_e( 'Remove Points', 'j2t-reward-points-for-woocommerce' ); ?>" data-point="<?php echo esc_html( $userPoints ); ?>" data-id="<?php echo esc_html( $userId ); ?>" data-order-limit="0"><?php esc_html_e( 'Remove Points', 'j2trewardoints' ); ?></button>
                        <?php endif; ?>
                        <input type="hidden" name="remove_points" id="remove_points" value="0" />
                        <p class="points-user-has"><?php printf(
                                __('You currently have %s point(s)', 'j2t-reward-points-for-woocommerce'),
                                esc_html( $userPoints )
                            );?></p>
                        <p class="points-order-gather"><?php printf(
                                __('With this shopping cart, you will gather %s point(s).', 'j2t-reward-points-for-woocommerce'),
                                esc_html( $gatheringPoints )
                            );?></p>
                        <?php if (WC()->session->get( 'j2t_rewardpoints' ) > 0) {?>
                            <p class="points-currently-used"><?php printf(
                                __('You are currently using %s point(s).', 'j2t-reward-points-for-woocommerce'),
                                esc_html( WC()->session->get( 'j2t_rewardpoints' ) )
                            );?></p>
                        <?php }?>
                </div>	
                <?php
            } else {
                ?>
                <div class="coupon j2t-rewardpoints-coupon">
                <p class="login-reward-sentence"><?php printf(
                    __('You must be <a href="%s">logged</a> in order to gather or use points.', 'j2t-reward-points-for-woocommerce'),
                    get_permalink( get_option('woocommerce_myaccount_page_id') )
                );?></p>
                </div>
                <?php
            }
        }

        

        public static function apply_points_on_cart() {
            check_ajax_referer( 'j2t-rewardpoints-verify-nonce', 'j2trewardpoints_nonce' );
            $response['result'] = false;
            $response['message'] = __( 'Unable to apply points for the moment.', 'j2t-reward-points-for-woocommerce' );

            if ( ! empty( $_POST['removePoints'] ) && isset( $_POST['removePoints'] ) && $_POST['removePoints'] == 1 ) {
                WC()->session->__unset( 'j2t_rewardpoints' );
                $response['result'] = true;
                $response['message'] = esc_html__( 'Points removed with success.', 'j2t-reward-points-for-woocommerce' );
                wc_add_notice( __('Points removed with success.', "success" ));
            } else {
                if ( ! empty( $_POST['userId'] ) && isset( $_POST['userId'] ) ) {
                    $userId = sanitize_text_field( wp_unslash( $_POST['userId'] ) );
                }
                if ( ! empty( $_POST['pointsValue'] ) && isset( $_POST['pointsValue'] ) ) {
                    $rewardPoints = sanitize_text_field( wp_unslash( $_POST['pointsValue'] ) );
                    
                    $cart = WC()->cart;
                    //verify max value to be applied on cart
                    $pointUsageValue = self::get_conf_points_money_value();
                    
                    $userId = get_current_user_ID();
                    $userPoints = self::get_user_available_points($userId);
    
                    //check max point user can apply on shopping cart
                    $maxPoint = round($cart->get_subtotal() * $pointUsageValue);
                    $maxPoint = min($userPoints, $maxPoint);
                    
                    if ($maxPoint < $rewardPoints) {
                        $rewardPoints = $maxPoint;
                    }
                }
                
                if ( isset( $userId ) && ! empty( $userId ) ) {
                        if ( isset( $rewardPoints ) && ! empty( $rewardPoints ) ) {
                                WC()->session->set( 'j2t_rewardpoints', $rewardPoints );
                                $response['result'] = true;
                                $response['message'] = $rewardPoints . ' >> ' . esc_html__( 'Points applied with success.', 'j2t-reward-points-for-woocommerce' );
    
    
                                $text_apply_point = sprintf(
                                        __('%s point(s) applied with success.', 'j2t-reward-points-for-woocommerce'),
                                        esc_html( $rewardPoints )
                                    );
    
                                wc_add_notice( $text_apply_point, "success" );
                        } else {
                                $response['result'] = false;
                                $response['message'] = __( 'Unable to apply points.', 'j2t-reward-points-for-woocommerce' );
                                wc_add_notice( __( "Unable to apply points.", "j2treward" ), "error" );
                        }
                }
            }
            wp_send_json( $response );
        }
        
        
        
        public static function removed_points_on_cart( $coupon_code ) { 
            if ($coupon_code == 'points') {
                //remove session
                WC()->session->__unset( 'j2t_rewardpoints');
                wc_add_notice( __( "Points removed from cart with success.", "j2treward" ), "success" );
            }
        }
        
        //admin settings
        public static function update_settings() {
            woocommerce_update_options( self::get_settings() );
        }

        public static function add_settings_tab( $settings_tabs ) {
            $settings_tabs['settings_tab_j2trewards'] = __( 'J2T Points & Rewards', 'j2t-reward-points-for-woocommerce' );
            return $settings_tabs;
        }
        
        public static function settings_tab() {
            woocommerce_admin_fields( self::get_settings() );
        }

        protected function get_sections() {
            $sections = array(
                    ''        => __( 'General', 'j2t-reward-points-for-woocommerce' ),
                    'birthday_points' => __( 'Birthday Points', 'j2t-reward-points-for-woocommerce' ),
                    'referral' => __( 'Referral Program', 'j2t-reward-points-for-woocommerce' ),
            );
            return $sections;
        }

        public static function output_sections() {
            global $current_section;

            $sections = array(
                ''        => __( 'General', 'j2t-reward-points-for-woocommerce' ),
                'birthday_points' => __( 'Birthday Points', 'j2t-reward-points-for-woocommerce' ),
                'referral' => __( 'Referral Program', 'j2t-reward-points-for-woocommerce' ),
            );

            if ( empty( $sections ) || 1 === count( $sections ) ) {
                    return;
            }

            echo '<ul class="subsubsub">';

            $array_keys = array_keys( $sections );

            foreach ( $sections as $id => $label ) {
                    $url       = admin_url( 'admin.php?page=wc-settings&tab=settings_tab_j2trewards&section=' . sanitize_title( $id ) );
                    $class     = ( $current_section === $id ? 'current' : '' );
                    $separator = ( end( $array_keys ) === $id ? '' : '|' );
                    $text      = esc_html( $label );
                    echo "<li><a href='$url' class='$class'>$text</a> $separator </li>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            echo '</ul><br class="clear" />';
        }

        
        public static function get_settings() {
            global $current_section;
            $statuses = wc_get_order_statuses();
            $temp = wc_get_order_statuses();
            unset($temp['wc-cancelled']);
            unset($temp['wc-refunded']);
            unset($temp['wc-failed']);
            $default_status_used = array_keys($temp);
            
            if (!function_exists('get_editable_roles')) {
                require_once(ABSPATH . '/wp-admin/includes/user.php');
            }

            $roles = get_editable_roles();
            
            $usedRoles = array();
            foreach ($roles as $role_key => $role_array) {
                $usedRoles[$role_key] = $role_array['name'];
            }

            
            $settings = array(                
                'section_title_values' => array(
                    'name'     => __( 'General', 'j2t-reward-points-for-woocommerce' ),
                    'type'     => 'title',
                    'desc'     => '',
                    'id'       => 'wc_settings_tab_j2trewards_section_title'
                ),
                'active' => array(
                    'name' => __( 'Active', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'select',
                    'default'=> 0,                    
                    'id'   => 'wc_settings_tab_j2trewards_active',
                    'options' => array(
                            '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                            '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                    )
                ),
                'money_points' => array(
                    'name' => __( 'Points gathered / unit of money spent', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'number',
                    'default'=> 1,
                    'desc' => __( 'Points gathered for 1 unit of money spent. (e.g. if set to 2, you will obtain 100 points for $50 spent).', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_money_points'
                ),
                'points_money' => array(
                    'name' => __( 'Points to obtain 1 unit of money', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'number',
                    'default'=> 50,
                    'desc' => __( 'Amount of points required to obtain a discount.', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_points_money'
                ),
                'valid_statuses' => array(
                    'name' => __( 'Valid Gathering Statuses', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'multiselect',
                    'default'=> array('wc-completed'),
                    'desc' => __( 'Order statuses used to validate points (modify this only if you know what you are doing).', 'j2t-reward-points-for-woocommerce' ),
                    'options' => $statuses,
                    'id'   => 'wc_settings_tab_j2trewards_valid_statuses'
                ),
                'valid_used_statuses' => array(
                    'name' => __( 'Valid Gathering Statuses', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'multiselect',
                    'default'=> $default_status_used,
                    'desc' => __( 'Order statuses used to validate used points (modify this only if you know what you are doing).', 'j2t-reward-points-for-woocommerce' ),
                    'options' => $statuses,
                    'id'   => 'wc_settings_tab_j2trewards_valid_used_statuses'
                ),
                'exluded_roles' => array(
                    'name' => __( 'Excluded Roles', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'multiselect',
                    'desc' => __( 'Select user roles for whom this module will not be active. Note that if a user has multiple roles, if one of the role is selected as excluded, this user will not be able to see or use the module.', 'j2t-reward-points-for-woocommerce' ),
                    'options' => $usedRoles,
                    'id'   => 'wc_settings_tab_j2trewards_exluded_roles'
                ),
                'show_on_grid' => array(
                    'name' => __( 'Show Info on Lists', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'select',
                    'default'=> 0,
                    'desc' => __( 'Show point information on product list pages.', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_show_on_grid',
                    'options' => array(
                            '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                            '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                    )
                ),
                'show_on_product_page' => array(
                    'name' => __( 'Show Info on Product Page', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'select',
                    'default'=> 0,
                    'desc' => __( 'Show point information on product view pages.', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_show_on_product_page',
                    'options' => array(
                            '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                            '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                    )
                ),
                'registration_points' => array(
                    'name' => __( 'Registration Points', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'number',
                    'default'=> 0,
                    'desc' => __( 'Points earned upon customer registration (points are allocated when customer logs into the system for the first time).', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_registration_points'
                ),
                /*'newsletter_points' => array(
                    'name' => __( 'Newsletter Subscription', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'number',
                    'default'=> 0,
                    'desc' => __( 'Points earned on newsletter subscription (note that unsubscribing will not remove points). Use 0 for no point review value.', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_newsletter_points'
                ),*/
                'review_points' => array(
                    'name' => __( 'Product Review', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'number',
                    'default'=> 0,
                    'desc' => __( 'Points earned on valid review (review must be validated by store admin). Use 0 for no point review value.', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_review_points'
                ),
                'rewardpoints_id_inline_image' => array(
                    'name' => __( 'Inline Image ID', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'number',
                    'desc' => __( 'ID of the image which will be shown in front of inline rewarpoints sentences on your store.', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_rewardpoints_id_inline_image'
                ),
                'math_total_earn' => array(
                    'name' => __( 'Total Gathered Math', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'select',
                    'default'=> 0,
                    'desc' => __( 'Apply math action on total gathered points on current customer cart. Note that no modifications will be done on item point calculation but only on total earning points.', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_math_total_earn',
                    'options' => array(
                            'ceil'  => __( 'Ceil', 'j2t-reward-points-for-woocommerce' ),
                            'floor' => __( 'Floor', 'j2t-reward-points-for-woocommerce' ),
                            'round' => __( 'Round', 'j2t-reward-points-for-woocommerce' )
                    )
                ),
                'math_catalog_pages' => array(
                    'name' => __( 'Catalog Pages Math', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'select',
                    'default'=> 0,
                    'desc' => __( 'Apply math action on points calculation of catalog pages. Note that this will only affect product catalog view.', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_math_catalog_pages',
                    'options' => array(
                            'ceil'  => __( 'Ceil', 'j2t-reward-points-for-woocommerce' ),
                            'floor' => __( 'Floor', 'j2t-reward-points-for-woocommerce' ),
                            'round' => __( 'Round', 'j2t-reward-points-for-woocommerce' )
                    )
                ),
                'trash_action' => array(
                    'name' => __( 'Trash Action', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'select',
                    'default'=> 0,
                    'desc' => __( 'When order is trashed, process status change (any point status will be changed to trashed). This could lead to bad point calculation.', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_trash_action',
                    'options' => array(
                            '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                            '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                    )
                ),
                'delete_action' => array(
                    'name' => __( 'Delete Action', 'j2t-reward-points-for-woocommerce' ),
                    'type' => 'select',
                    'default'=> 0,
                    'desc' => __( 'If set to yes, when deleting an order, point type will be changed to admin gift and description will be added stating that order related to the point has been deleted. In case of non valid status, points will be removed.', 'j2t-reward-points-for-woocommerce' ),
                    'id'   => 'wc_settings_tab_j2trewards_delete_action',
                    'options' => array(
                            '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                            '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                    )
                ),
                
                'section_end' => array(
                     'type' => 'sectionend',
                     'id' => 'wc_settings_tab_j2trewards_section_end'
                )
            );

            if ($current_section == 'birthday_points') {
                $settings = array(                    
                                    'section_title_values' => array(
                                        'name'     => __( 'Birthday Points', 'woocommerce-settings-tab-j2trewards' ),
                                        'type'     => 'title',
                                        'desc'     => '',
                                        'id'       => 'wc_settings_tab_j2trewards_section_dob_title'
                                    ),
                                    'birthday_points' => array(
                                        'name' => __( 'Points', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Points gathered on customer birthday. Use 0 to deactivate the feature.', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_birthday_points'
                                    ),
                                    'dob_prior_verification' => array(
                                        'name' => __( 'Prior Verification', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Verify and send email before actual date of birth (in days).', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_dob_prior_verification'
                                    ),
                                    'dob_add_field' => array(
                                        'name' => __( 'Add DOB', 'woocommerce-settings-tab-j2trewards' ),
                                        'type' => 'select',
                                        'default'=> 0,
                                        'id'   => 'wc_settings_tab_j2trewards_dob_add_field',
                                        'desc' => __( 'The module will add new DOB field. Without this, it will not be possible to give your customers points for their birthday. Added field is name is: dob_field.', 'j2t-reward-points-for-woocommerce' ),
                                        'options' => array(
                                                '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                                                '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),
                                    'record_dob_logs' => array(
                                        'name' => __( 'Recording logs', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'select',
                                        'default'=> 0,
                                        'desc' => __( 'Recording logs when DOB cron is running. In wp-content directory, debug.log file will be used. define( \'WP_DEBUG\', true ); define( \'WP_DEBUG_LOG\', true ); define( \'WP_DEBUG_DISPLAY\', true); must be added to wp-config.php', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_record_dob_logs',
                                        'options' => array(
                                                '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                                                '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),
                                    /*'dob_points_delay' => array(
                                        'name' => __( 'Availability Delay', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Delaying the birthday points availability (use 0 for no-delay).', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_dob_points_delay'
                                    ),
                                    'dob_points_duration' => array(
                                        'name' => __( 'Validity Duration', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Birthday points validity duration in days (use 0 for unlimited).', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_dob_points_duration'
                                    ),*/
                                    'section_end' => array(
                                         'type' => 'sectionend',
                                         'id' => 'wc_settings_tab_j2trewards_section_end'
                                    )
                                );
            }

            if ($current_section == 'referral') {
                $settings = array(                    
                                    'section_title_values' => array(
                                        'name'     => __( 'Referral Program', 'woocommerce-settings-tab-j2trewards' ),
                                        'type'     => 'title',
                                        'desc'     => '',
                                        'id'       => 'wc_settings_tab_j2trewards_section_dob_title'
                                    ),
                                    'referral_show' => array(
                                        'name' => __( 'Show Referral Link', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'select',
                                        'default'=> 0,  
                                        'desc' => __('Show refer a friend link in user account.', 'j2t-reward-points-for-woocommerce'),                  
                                        'id'   => 'wc_settings_tab_j2trewards_referral_show',
                                        'options' => array(
                                                '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                                                '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),                                    
                                    'referral_points' => array(
                                        'name' => __( 'Referral Points Or Ratio', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Points earned when referred friend orders.', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_referral_points'
                                    ),
                                    'referral_points_method' => array(
                                        'name' => __( 'Calculation Type (Referral Points)', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'select',
                                        'default'=> 'static',  
                                        'desc' => __('Calculation type used. "Cart summary Ratio points" multiplies cart subtotal by inserted value, "Ratio points" uses inserted value to calculate points and "static value" uses configuration value without any calculation. Note that any configured rules or points on product page will override Ratio points calculation.', 'j2t-reward-points-for-woocommerce'),                  
                                        'id'   => 'wc_settings_tab_j2trewards_referral_points_method',
                                        'options' => array(
                                                'static'  => __( 'Static Value', 'j2t-reward-points-for-woocommerce' ),
                                                'ratio' => __( 'Ratio Points', 'j2t-reward-points-for-woocommerce' ),
                                                'cart_summary_ratio' => __( 'Cart Summary Ratio Points', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),                                    
                                    'referral_child_points' => array(
                                        'name' => __( 'Referral Child Points Or Ratio', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Points earned by referred friend for first order.', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_referral_child_points'
                                    ),
                                    'referral_child_points_method' => array(
                                        'name' => __( 'Calculation Type (Child Points)', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'select',
                                        'default'=> 'static',  
                                        'desc' => __('Calculation type used. "Cart summary Ratio points" multiplies cart subtotal by inserted value, "Ratio points" uses inserted value to calculate points and "static value" uses configuration value without any calculation. Note that any configured rules or points on product page will override Ratio points calculation.', 'j2t-reward-points-for-woocommerce'),                  
                                        'id'   => 'wc_settings_tab_j2trewards_referral_child_points_method',
                                        'options' => array(
                                                'static'  => __( 'Static Value', 'j2t-reward-points-for-woocommerce' ),
                                                'ratio' => __( 'Ratio Points', 'j2t-reward-points-for-woocommerce' ),
                                                'cart_summary_ratio' => __( 'Cart Summary Ratio Points', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),                                    
                                    'referral_min_order' => array(
                                        'name' => __( 'Referral Min. Order', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Referral minimum order amount (base subtotal amount without shipping fees) in order to process referral actions (0 for no minimum amount).', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_referral_min_order'
                                    ),
                                    'referral_permanent' => array(
                                        'name' => __( 'Show Referral Permanent Link', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'select',
                                        'default'=> 0,  
                                        'desc' => __('This will allow customers to share permanent their permanent link to refer friends.', 'j2t-reward-points-for-woocommerce'),                  
                                        'id'   => 'wc_settings_tab_j2trewards_referral_permanent',
                                        'options' => array(
                                                '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                                                '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),
                                    'referral_redirection' => array(
                                        'name' => __( 'Referral Redirection', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'text',  
                                        'desc' => __('Redirection path for permanent link, excluding domain name and index.php/ part (e.g. electronics/cell-phones.html). Leave blank to use default redirection.', 'j2t-reward-points-for-woocommerce'),                  
                                        'id'   => 'wc_settings_tab_j2trewards_referral_redirection',
                                        'options' => array(
                                                '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                                                '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),
                                    'referral_addthis' => array(
                                        'name' => __( 'Share With addthis', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'select',
                                        'default'=> 0,  
                                        'desc' => __('Allow customers to share permanent link using addthis. Visit addthis.com to create an account.', 'j2t-reward-points-for-woocommerce'),                  
                                        'id'   => 'wc_settings_tab_j2trewards_referral_addthis',
                                        'options' => array(
                                                '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                                                '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),
                                    'referral_addthis_account' => array(
                                        'name' => __( 'addthis Account Name', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'text',                
                                        'id'   => 'wc_settings_tab_j2trewards_referral_addthis_account',
                                        'options' => array(
                                                '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                                                '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),
                                    'referral_addthis_code' => array(
                                        'name' => __( 'addthis Code', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'textarea',   
                                        'default' => '<div class="addthis_toolbox addthis_default_style addthis_32x32_style">
    <a class="addthis_button_preferred_1"></a>
    <a class="addthis_button_preferred_2"></a>
    <a class="addthis_button_preferred_3"></a>
    <a class="addthis_button_preferred_4"></a>
    <a class="addthis_button_compact"></a>
    <a class="addthis_counter addthis_bubble_style"></a>
</div>',
                                        'desc' => 'Addthis buttons code without javascript element.',           
                                        'id'   => 'wc_settings_tab_j2trewards_referral_addthis_code',
                                        'options' => array(
                                                '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                                                '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),
                                    'referral_custom_code' => array(
                                        'name' => __( 'Custom Social Share Code', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'textarea',
                                        'desc' => 'Custom social share code. You can integrate any other social network sharing tool (such as jiathis, sharethis, addinto, etc.) in order to share referral url. Referral url must be integrated to the code with the following: {{referral_url}}.',           
                                        'id'   => 'wc_settings_tab_j2trewards_referral_custom_code',
                                        'options' => array(
                                                '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                                                '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),
                                    'referred_registration_points' => array(
                                        'name' => __( 'Referred Registration Points', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Extra points earned by referred customer upon registration (points are allocated when customer logs into the system for the first time).', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_referred_registration_points'
                                    ),
                                    'referrer_registration_points' => array(
                                        'name' => __( 'Referrer Registration Points', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Extra points earned by referrer customer upon referred registration (points are allocated when customer logs into the system for the first time).', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_referrer_registration_points'
                                    ),
                                    
                                    'referral_guestdisabled' => array(
                                        'name' => __( 'Disable guest orders', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'select',
                                        'default'=> 1,  
                                        'desc' => __('Disable guest orders when customer orders from referral url/email.', 'j2t-reward-points-for-woocommerce'),                  
                                        'id'   => 'wc_settings_tab_j2trewards_referral_guestdisabled',
                                        'options' => array(
                                                '1'  => __( 'Yes', 'j2t-reward-points-for-woocommerce' ),
                                                '0' => __( 'No', 'j2t-reward-points-for-woocommerce' )
                                        )
                                    ),
                                    
                                    /*'dob_points_delay' => array(
                                        'name' => __( 'Availability Delay', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Delaying the birthday points availability (use 0 for no-delay).', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_dob_points_delay'
                                    ),
                                    'dob_points_duration' => array(
                                        'name' => __( 'Validity Duration', 'j2t-reward-points-for-woocommerce' ),
                                        'type' => 'number',
                                        'default'=> 0,
                                        'desc' => __( 'Birthday points validity duration in days (use 0 for unlimited).', 'j2t-reward-points-for-woocommerce' ),
                                        'id'   => 'wc_settings_tab_j2trewards_dob_points_duration'
                                    ),*/
                                    'section_end' => array(
                                         'type' => 'sectionend',
                                         'id' => 'wc_settings_tab_j2trewards_section_end'
                                    )
                                );
            }
            return apply_filters( 'wc_settings_tab_j2trewards_settings', $settings );
        }
        
        


        /**
         * Main Extension Instance.
         * Ensures only one instance of the extension is loaded or can be loaded.
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
 
            return self::$_instance;
        }
 
        /**
         * Cloning is forbidden.
         */
        public function __clone() {
            // Override this PHP function to prevent unwanted copies of your instance.
            //   Implement your own error or use `wc_doing_it_wrong()`
        }
 
        /**
         * Unserializing instances of this class is forbidden.
         */
        public function __wakeup() {
            // Override this PHP function to prevent unwanted copies of your instance.
            //   Implement your own error or use `wc_doing_it_wrong()`
        }
    }
endif;


function j2t_rewardpoints_woocommerce_initialize() {
    J2t_Rewardpoints::init();

    if(!class_exists('Rewardpoints_List_Table')){      
        require_once dirname( __FILE__ ) . '/' . 'AdminList.php';
    }
    if(!class_exists('RewardEmails')){      
        require_once dirname( __FILE__ ) . '/' . 'RewardEmails.php';
    }
}
add_action( 'plugins_loaded', 'j2t_rewardpoints_woocommerce_initialize', 10 );

