<?php

// Add action hook only if action=download_csv
if ( isset($_GET['action'] ) && $_GET['action'] == 'j2t_rewardpoints_export_csv' )  {
	// Handle CSV Export
	add_action( 'admin_init', 'j2t_rewardpoints_export_csv') ;
}

function j2t_rewardpoints_export_csv() {

    // Check for current user privileges 
    if( !current_user_can( 'manage_options' ) ){ return false; }

    // Check if we are in WP-Admin
    if( !is_admin() ){ return false; }

    // Nonce Check
    /*$nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'download_csv' ) ) {
        die( 'Security check error' );
    }*/
    if ( ! current_user_can( 'manage_options' ) )
        return;
    global $wpdb;
    $table_name = $wpdb->prefix . 'rewardpoints_flat_account';
                 
    $usermeta_table_name = $wpdb->prefix . 'usermeta';  
    $users_table_name = $wpdb->prefix . 'users';  

    $output = fopen('php://output', 'w');
    if(isset($_GET['s']))
    {            
        $search=sanitize_text_field($_GET['s']);  
        $search = trim($search);  
        $result = $wpdb->get_results("
            SELECT main_table.`user_id`,`points_collected`,`points_used`, `points_current`, firstT.meta_value AS 'first_name',
            lastT.meta_value AS 'last_name', user_email, CONCAT(firstT.meta_value, ' ', lastT.meta_value) as full_name FROM $table_name main_table
            LEFT JOIN
                $usermeta_table_name firstT ON firstT.user_id = main_table.user_id
            LEFT JOIN
                $usermeta_table_name lastT ON lastT.user_id = main_table.user_id
            LEFT JOIN
                $users_table_name usr ON usr.ID = main_table.user_id
            WHERE firstT.meta_key = 'first_name'
            AND lastT.meta_key = 'last_name'
            AND (firstT.meta_key LIKE '%".$wpdb->esc_like($search)."%' OR lastT.meta_key LIKE '%".$wpdb->esc_like($search)."%' OR user_email LIKE '%".$wpdb->esc_like($search)."%')
            ");  
    }  
    else{
        $result = $wpdb->get_results("
            SELECT main_table.`user_id`,`points_collected`,`points_used`, `points_current`, firstT.meta_value AS 'first_name',
            lastT.meta_value AS 'last_name', user_email, CONCAT(firstT.meta_value, ' ', lastT.meta_value) as full_name FROM $table_name main_table
            LEFT OUTER JOIN
                $usermeta_table_name firstT ON firstT.user_id = main_table.user_id
            LEFT OUTER JOIN
                $usermeta_table_name lastT ON lastT.user_id = main_table.user_id
            LEFT OUTER JOIN
                $users_table_name usr ON usr.ID = main_table.user_id
            WHERE firstT.meta_key = 'first_name'
            AND lastT.meta_key = 'last_name'
        ");
    }

    fputcsv( $output, array('Customer', 'Email', ' Available Points'));
    foreach ( $result as $key => $rewardpoints ) {
        $modified_values = array(
                        $rewardpoints->full_name,
                        $rewardpoints->user_email,
                        $rewardpoints->points_current
        );
        fputcsv( $output, $modified_values );
    }
    $filename = 'j2trewardpoints-export';
    $date = date("Y-m-d H:i:s");
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false);
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"" . $filename . "-" . $date . ".csv\";" );
    header("Content-Transfer-Encoding: binary");exit;
}

add_action( 'admin_menu','j2t_register_rewardpoints_menu_page');

function j2t_load_j2t_scripts(){
    wp_register_script( 
        'inline-user-points-update', 
        plugin_dir_url( __FILE__ ) . 'admin.js', 
        array( 'jquery', 'tags-suggest', 'wp-a11y' ),
        false,
        1
    );
    wp_enqueue_script( 'inline-user-points-update' );
}
add_action('admin_enqueue_scripts', 'j2t_load_j2t_scripts');


function j2t_admin_ajax_call() {
    $point_update = sanitize_text_field($_POST['point_update']);
	$user_id = (int) sanitize_text_field($_POST['user_id']);
	$user_points = sanitize_text_field($_POST['user_points']);
	$user_points_description = sanitize_text_field($_POST['user_points_description']);
    
    $res = J2t_Rewardpoints::save_admin_points($user_id, $user_points, $user_points_description, date('Y-m-d H:i:s'));

    $return = array (
        'user_id' => $user_id,
        'available_points' => $res['available_points'],
	    'used_points' => $res['used_points'],
	    'gathered_points' => $res['gathered_points'],
        'in_error' => $res['return_val'],
        'error_message' => __('Error while processing the query.', 'j2t-reward-points-for-woocommerce')
    );
    
    header('Content-Type: application/json');
    echo json_encode($return);
	wp_die();
}

add_action('wp_ajax_inline_rewardpoints_save', 'j2t_admin_ajax_call');

function j2t_register_rewardpoints_menu_page(){
 
    global $new_menu_page;
    // creating admin menu 
    add_menu_page('J2T Points & Rewards', 'J2T Points & Rewards', 'edit_posts','j2trewardpoints','j2t_rewardpoints_list_page', plugin_dir_url( __FILE__ ).'/images/rewardpoints.png', 8 );
    $hook=add_submenu_page("j2trewardpoints","J2T Points & Rewards","J2T Points & Rewards",'edit_posts', "j2trewardpoints", "j2t_rewardpoints_list_page");
  
    // adding submenu 
    $stat_hook =  add_submenu_page("null","Rewardpoints Statistics","Rewardpoints Statistics",'edit_posts',"rewardpoints_detail", "j2t_rewardpoints_detail_page");
    $referral_hook =  add_submenu_page("null","Referral Statistics","Referral Statistics",'edit_posts',"referral_detail", "j2t_referral_detail_page");

 
    // creating options like per page data(pagination)
    add_action( "load-".$hook, 'j2t_add_options' ); 
    add_action( "load-".$stat_hook, 'j2t_add_options' ); 
    add_action( "load-".$referral_hook, 'j2t_add_options' ); 
    
    add_action( 'current_screen','j2t_rewardpoints_admin_add_help_tab');
    
}
function j2t_rewardpoints_admin_add_help_tab() {
 
    $screen = get_current_screen(); 
    $screen->add_help_tab( array( 
        'id'    => 'rewardpoints_tab', 
        'title' => 'Rewardpoints Tab', 
        'content'   => '<p>' . __( 'Browse rewardpoints here.', 'j2t-reward-points-for-woocommerce' ) . '</p>',
 ) );
}
function j2t_add_options() {
    $option = 'per_page';
    $args = array(
        'label' => 'Results',
        'default' => 10,
        'option' => 'results_per_page'
    );
    add_screen_option( $option, $args );
}

function j2t_rewardpoints_list_page(){
    
    if(!class_exists('J2t_Rewardpoints_List_Table')){    
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );    
    }

    class J2t_Rewardpoints_List_Table extends WP_List_Table {
    
        
        protected $points_type;
    
        function __construct() {  
            parent::__construct( array(  
                'singular'  => 'singular_name', 
                'plural'    => 'plural_name',
                'ajax'      => false 
            ) );  
        }

        public function single_row( $item ) {
            echo '<tr data-user-id="'.esc_attr($item['user_id']).'" class="item-row-user-'.esc_attr($item['user_id']).'" id="item-row-user-'.esc_attr($item['user_id']).'">';
            $this->single_row_columns( $item );
            echo '</tr>';
        }
 

        public function column_default( $item, $column_name )
        {
            switch( $column_name ) {
                case 'full_name':

                    $format = '<button type="button" data-user-id="%d" data-action="%s" class="%s button-link" aria-expanded="false" aria-label="%s">%s</button>';

                    
                    $quick_edit_bt = '';

                    $actions['view'] = sprintf(
                        '<a href="admin.php?page=rewardpoints_detail&user=%s" rel="bookmark" aria-label="%s">%s</a>',
                        $item['user_id'],
                        esc_attr( sprintf( __( 'Show user Point History' ), 'j2t-reward-points-for-woocommerce' ) ),
                        __( 'Point History', 'j2t-reward-points-for-woocommerce' )
                    );
                    $actions['view show-referral'] = sprintf(
                        '<a href="admin.php?page=referral_detail&user=%s" rel="bookmark" aria-label="%s">%s</a>',
                        $item['user_id'],
                        esc_attr( sprintf( __( 'Show Referral History' ), 'j2t-reward-points-for-woocommerce' ) ),
                        __( 'Show Referral History', 'j2t-reward-points-for-woocommerce' )
                    );
                    $actions['inline hide-if-no-js'] = sprintf(
                        '<button type="button" data-user-id="%d" class="button-link editinline" aria-label="%s" aria-expanded="false" id="user-%s">%s</button>',
                        $item['user_id'],
                        esc_attr( sprintf( __( 'Add or Remove points to this customer' ), 'j2t-reward-points-for-woocommerce' ) ),
                        $item['user_id'],
                        __( 'Add/Remove Points', 'j2t-reward-points-for-woocommerce' )
                    );

                    $quick_edit_bt = $this->row_actions( $actions );
                    return $item[$column_name].' '. $quick_edit_bt;
                case 'points_current':
                    return '<strong class="inline-val points_current">'.$item[$column_name]."</strong>";
                case 'points_used':
                    return '<strong class="inline-val points_used">'.$item[$column_name]."</strong>";
                case 'points_collected':
                        return '<span class="inline-val points_collected">'.$item[$column_name]."</span>";
                case 'user_email':
                    return "<strong>".$item[$column_name]."</strong>";
                default:  
                    return $item[$column_name] ;
    
            }
        }
        public function get_sortable_columns() {
            $sortable_columns = array(
                'user_email'     => array('user_email',true), 
                'points_current'     => array('points_current',true), 
                'points_collected'     => array('points_collected',true), 
                'points_used' => array('points_used',true) ); 
            return $sortable_columns;
        }
        public function get_hidden_columns()
        {
            // Setup Hidden columns and return them
            return array();
        }
        
        function first_column_name($item) {
            
            $actions = array(
                'export'      => sprintf('<a href="?page=rewardpoints_export&user=%s">',$item['id']).__('Export').'</a>',
            );
            return sprintf('%1$s %2$s', $item['first_column_name'], $this->row_actions($actions) );
        }
        function get_bulk_actions()
        {
            $actions = array(
                'export'    => __('Export Data', 'j2t-reward-points-for-woocommerce')
            );  
            return $actions;
        }
        public function process_bulk_action()
        {  
            global $wpdb;
            
            if ('export' === $this->current_action()) {
                if(isset($_GET['s']))
                {
                    $search=sanitize_text_field($_GET['s']);
                    wp_redirect( admin_url( '/admin.php?action=j2t_rewardpoints_export_csv&s='.$search ) );
                } else {
                    wp_redirect( admin_url( '/admin.php?action=j2t_rewardpoints_export_csv' ) );
                }
                
                exit;
            }
    
        }
        
        private function table_data()
        {      
            global $wpdb;  
            $table_name = $wpdb->prefix . 'rewardpoints_flat_account';  
            $usermeta_table_name = $wpdb->prefix . 'usermeta';  
            $users_table_name = $wpdb->prefix . 'users';  
            $data=array();  
            
            if(isset($_GET['s']))
            {            
                $search=sanitize_text_field($_GET['s']);  
                $search = trim($search);  
                $rewardpoints_entries = $wpdb->get_results("
                    SELECT main_table.`user_id`,`points_collected`,`points_used`, `points_current`, firstT.meta_value AS 'first_name',
                    lastT.meta_value AS 'last_name', user_email, CONCAT(firstT.meta_value, ' ', lastT.meta_value) as full_name FROM $table_name main_table
                    LEFT JOIN
                        $usermeta_table_name firstT ON firstT.user_id = main_table.user_id
                    LEFT JOIN
                        $usermeta_table_name lastT ON lastT.user_id = main_table.user_id
                    LEFT JOIN
                        $users_table_name usr ON usr.ID = main_table.user_id
                    WHERE firstT.meta_key = 'first_name'
                    AND lastT.meta_key = 'last_name'
                    AND (firstT.meta_key LIKE '%".$wpdb->esc_like($search)."%' OR lastT.meta_key LIKE '%".$wpdb->esc_like($search)."%' OR user_email LIKE '%".$wpdb->esc_like($search)."%')
                    ");  
            }  
            else{
                $rewardpoints_entries=$wpdb->get_results("
                    SELECT main_table.`user_id`,`points_collected`,`points_used`, `points_current`, firstT.meta_value AS 'first_name',
                    lastT.meta_value AS 'last_name', user_email, CONCAT(firstT.meta_value, ' ', lastT.meta_value) as full_name FROM $table_name main_table
                    LEFT OUTER JOIN
                        $usermeta_table_name firstT ON firstT.user_id = main_table.user_id
                    LEFT OUTER JOIN
                        $usermeta_table_name lastT ON lastT.user_id = main_table.user_id
                    LEFT OUTER JOIN
                        $users_table_name usr ON usr.ID = main_table.user_id
                    WHERE firstT.meta_key = 'first_name'
                    AND lastT.meta_key = 'last_name'
                ");
            }
            $i=0;
    
            foreach ($rewardpoints_entries as $rewardpoints_data) {
                $data[] = array(  
                    'user_id'  => $rewardpoints_data->user_id,
                        'points_collected'  => $rewardpoints_data->points_collected,  
                        'points_used' =>   $rewardpoints_data->points_used,
                        'points_current' =>   $rewardpoints_data->points_current,
                        'first_name' =>   $rewardpoints_data->first_name,
                        'last_name' =>   $rewardpoints_data->last_name,
                        'user_email' =>   $rewardpoints_data->user_email,
                        'full_name' =>   $rewardpoints_data->full_name
                    ); 
                $i++;  
            }  
            return $data;  
        }

        public function prepare_items()  
        {
            global $wpdb;
            $columns = $this->get_columns();  
            $sortable = $this->get_sortable_columns();  
            $hidden=$this->get_hidden_columns();  
            $this->process_bulk_action();  
            $data = $this->table_data();              
            $totalitems = count($data);  
            $user = get_current_user_id();  
            $screen = get_current_screen();  
            $option = $screen->get_option('per_page', 'option');   
            $perpage = get_user_meta($user, $option, true);
            $perpage = $perpage ? $perpage : 10;
            $this->_column_headers = array($columns,$hidden,$sortable);  
            if ( empty ( $per_page) || $per_page < 1 ) {              
                $per_page = $screen->get_option( 'per_page', 'default' );   
            }  

            function usort_reorder($a,$b){  
                $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'user_email'; //If no sort, default to title  
                $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc'; //If no order, default to asc  
                $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order  
                return ($order==='asc') ? $result : -$result; //Send final sort direction to usort  
            }

            usort($data, 'usort_reorder');
            $totalpages = ceil($totalitems/$perpage);   
            $currentPage = $this->get_pagenum();                  
            $data = array_slice($data,(($currentPage-1)*$perpage),$perpage);  
            $this->set_pagination_args( array(  
                "total_items" => $totalitems,  
                "total_pages" => $totalpages,  
                "per_page" => $perpage,
            ) );
            $this->items =$data;
        }

        /**
         * Display text for when there are no items.
         */
        public function no_items() {
            esc_html_e( 'No rewardpoints found.', 'j2t-reward-points-for-woocommerce' );
        }

        public function get_columns() {


            return array(
                //'cb'     => '<input type="checkbox"/>',
                'full_name'  => __( 'Customer', 'j2t-reward-points-for-woocommerce' ),
                'user_email'  => __( 'Email', 'j2t-reward-points-for-woocommerce' ),
                'points_current'   => __( 'Available Points', 'j2t-reward-points-for-woocommerce' ),
                'points_used'   => __( 'Used Points', 'j2t-reward-points-for-woocommerce' ),
                'points_collected'   => __( 'Collected Points', 'j2t-reward-points-for-woocommerce' ),
            );
        }
    
    }
    $wp_list_table = new J2t_Rewardpoints_List_Table();  

    ?>
	<div class="wrap">
		<h2><?php esc_html_e( 'Points & Rewards Statistic', 'j2t-reward-points-for-woocommerce' ); ?></h2>
		<form id="all-drafts" method="get">
			<input type="hidden" name="page" value="j2trewardpoints" />

			<?php
			$wp_list_table->prepare_items();
			$wp_list_table->search_box( 'Search', 'search' );
			$wp_list_table->display();
			?>
		</form>
	</div>
    
    <form method="get" class="j2t_inline_edit">
        
        <div style="display: none"><tbody id="inlineedit">
            <div id="inlineedit">
                <div id="inline-edit" class="inline-edit-row inline-edit-row-page" style="display: none">
                    <div colspan="<?php /*echo $this->get_column_count();*/ ?>" class="colspanchange">
                    <fieldset class="inline-edit-col-left">
                            <legend class="inline-edit-legend"><?php echo __( 'Add/Remove Points', 'j2t-reward-points-for-woocommerce' ); ?></legend>
                            <div class="inline-edit-col">
                                
                            
                                <label>
                                    <span class="title"><?php _e( 'Points' ); ?></span>
                                    <span class="input-text-wrap"><input type="number" name="user_points" class="ptitle user_points" value=""></span>
                                    <span class="desc"><?php _e( 'Use negative values in order to remove points to this user.' ); ?></span>
                                </label>
                                <label>
                                    <span class="title"><?php _e( 'Description' ); ?></span>
                                    <span class="input-text-wrap"><input type="text" name="user_points_description" class="ptitle user_points_description" value=""></span>
                                    <span class="desc"><?php _e( 'Add description to point insertion, otherwise point type will be defined as Admin Points.' ); ?></span>
                                </label>
                            </div>


                            </div>
                        </fieldset>
                        <div class="submit inline-edit-save">
                        <button type="button" class="button cancel alignleft"><?php _e( 'Cancel' ); ?></button>
                        
                        <?php wp_nonce_field( 'inlineeditnonce', '_inline_edit', false ); ?>
                        <button type="button" class="button button-primary save alignright"><?php _e( 'Update' ); ?></button>
                        <span class="spinner"></span>
                        
                        <?php /*?><input type="hidden" name="post_view" value="<?php echo esc_attr( $m ); ?>" />
                        <input type="hidden" name="screen" value="<?php echo esc_attr( $screen->id ); ?>" />
                        <?php */?>
                        <br class="clear" />

                        <div class="notice notice-error notice-alt inline hidden">
                            <p class="error"></p>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </form>   

	<?php

}

function j2t_referral_detail_page() {
    if(!class_exists('J2t_Rewardpoints_List_Table')){    
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );    
    }

    class J2t_Rewardpoints_List_Table extends WP_List_Table {  
        
        protected $points_type;    
        function __construct() {  
            parent::__construct( array(  
                'singular'  => 'singular_name', 
                'plural'    => 'plural_name', 
                'ajax'      => false 
            ) );             
            //$this->points_type = J2t_Rewardpoints::getPointsTypeToArray();
        }    
        
        public function column_default( $item, $column_name )
        {
            switch( $column_name ) { 
                case 'rewardpoints_referral_status':
                    return ($item[$column_name]) ? __('Order placed', 'j2t-reward-points-for-woocommerce') : __('NA', 'j2t-reward-points-for-woocommerce');
                case 'rewardpoints_referral_name':
                    return (trim($item[$column_name])) ? $item[$column_name] : __('NA', 'j2t-reward-points-for-woocommerce');
                case 'rewardpoints_referral_email':
                    return "<strong>".$item[$column_name]."</strong>";
                default:  
                    return $item[$column_name];    
            }
        }
        public function get_sortable_columns() {
            $sortable_columns = array(
                'rewardpoints_referral_status'  => array('rewardpoints_referral_status',true), 
                'rewardpoints_referral_email'     => array('rewardpoints_referral_email',true), 
                'rewardpoints_referral_name' => array('rewardpoints_referral_name',true) ); 
            return $sortable_columns;
        }
        public function get_hidden_columns()
        {
            return array();
        }
        
        /*function first_column_name($item) {
            $actions = array(
                'export'      => sprintf('<a href="?page=j2t_rewardpoints_detail_page&user=%s">Export</a>',$item['id']),
            );
            return sprintf('%1$s %2$s', $item['first_column_name'], $this->row_actions($actions) );
        }*/
        
        /*public function process_bulk_action()
        {  
            global $wpdb;
            if ('export' === $this->current_action()) {
                echo 'export';
                die;
            }
        }*/
        
        private function table_data()
        {      
            global $wpdb;  
            $table_name = $wpdb->prefix . 'rewardpoints_referral'; 
            $data=array();  

            
            $user_id = isset($_GET['user']) ? sanitize_key($_GET['user']) : 0;
            if(isset($_GET['s']))
            {          

                $search=sanitize_text_field($_GET['s']);  
                $search = trim($search);  

                $rewardpoints_entries = $wpdb->get_results("
                    SELECT * FROM $table_name 
                    WHERE rewardpoints_referral_parent_id = '$user_id'
                    AND (rewardpoints_referral_name LIKE '%".$wpdb->esc_like($search)."%' OR rewardpoints_referral_email LIKE '%".$wpdb->esc_like($search)."%')
                    ");  
            }  
            else{
                $rewardpoints_entries=$wpdb->get_results("
                    SELECT * FROM $table_name 
                    WHERE rewardpoints_referral_parent_id = '$user_id'
                ");
            }
            $i=0;
    
            foreach ($rewardpoints_entries as $rewardpoints_data) {
                $data[] = array(  
                    'rewardpoints_referral_name'  => $rewardpoints_data->rewardpoints_referral_name,  
                    'rewardpoints_referral_email'  => $rewardpoints_data->rewardpoints_referral_email,  
                    'rewardpoints_referral_status'  => $rewardpoints_data->rewardpoints_referral_status
                    ); 
                $i++;  
            }  
            return $data;  
        }

        public function prepare_items()  
        {
            global $wpdb;
            $columns = $this->get_columns();  
            $sortable = $this->get_sortable_columns();  
            $hidden=$this->get_hidden_columns();  
            $this->process_bulk_action();  
            $data = $this->table_data();              
            $totalitems = count($data);  
            $user = get_current_user_id();  
            $screen = get_current_screen();  
            $option = $screen->get_option('per_page', 'option');   
            $perpage = get_user_meta($user, $option, true);
            $perpage = $perpage ? $perpage : 10;
            $this->_column_headers = array($columns,$hidden,$sortable);  
            if ( empty ( $per_page) || $per_page < 1 ) {              
                $per_page = $screen->get_option( 'per_page', 'default' );   
            }  

            function usort_reorder($a,$b){  
                $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'rewardpoints_referral_email'; //If no sort, default to title  
                $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc'; //If no order, default to asc  
                $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order  
                return ($order==='asc') ? $result : -$result; //Send final sort direction to usort  
            }

            usort($data, 'usort_reorder');
            $totalpages = ceil($totalitems/$perpage);   
            $currentPage = $this->get_pagenum();                  
            $data = array_slice($data,(($currentPage-1)*$perpage),$perpage);  
            $this->set_pagination_args( array(  
                "total_items" => $totalitems,  
                "total_pages" => $totalpages,  
                "per_page" => $perpage,
            ) );
            $this->items =$data;
        }

        /**
         * Display text for when there are no items.
         */
        public function no_items() {
            esc_html_e( 'No referral entry found.', 'j2t-reward-points-for-woocommerce' );
        }

        public function get_columns() {
            return array(
                'rewardpoints_referral_name'  => __( 'Name', 'j2t-reward-points-for-woocommerce' ),
                'rewardpoints_referral_email'  => __( 'Email Address', 'j2t-reward-points-for-woocommerce' ),
                'rewardpoints_referral_status'  => __( 'Status', 'j2t-reward-points-for-woocommerce' ),
            );
        }
    
    }
    $wp_list_table = new J2t_Rewardpoints_List_Table();  

    ?>
	<div class="wrap">
        <?php 
        $user = get_user_by( 'id', $_GET['user'] ); 
        $current_user = __( 'NA', 'j2t-reward-points-for-woocommerce' );
        if ($user !== false) {
            $current_user = sprintf(__( '%s %s (%s)', 'j2t-reward-points-for-woocommerce' ), $user->user_firstname, $user->user_lastname, $user->user_email);
        }
        ?>
		<h2><?php printf(__( 'Referral History for user : %s', 'j2t-reward-points-for-woocommerce' ), $current_user); ?></h2>
        <a href="?page=j2trewardpoints"><?php echo __('Back', 'j2t-reward-points-for-woocommerce'); ?></a>
		<form id="all-drafts" method="get">
			<input type="hidden" name="page" value="referral_detail" />
            <input type="hidden" name="user" value="<?php echo esc_attr($_GET['user']);?>" />

			<?php
			$wp_list_table->prepare_items();
			$wp_list_table->search_box( 'Search', 'search' );
			$wp_list_table->display();
			?>
		</form>
	</div>
	<?php
}



////////////////////////////////////////////////////////////////////////////////////////////


         
function j2t_rewardpoints_detail_page(){ 
    if(!class_exists('J2t_Rewardpoints_List_Table')){    
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );    
    }

    class J2t_Rewardpoints_List_Table extends WP_List_Table {  
        
        protected $points_type;    
        function __construct() {  
            parent::__construct( array(  
                'singular'  => 'singular_name', 
                'plural'    => 'plural_name', 
                'ajax'      => false 
            ) );             
            $this->points_type = J2t_Rewardpoints::getPointsTypeToArray();
        }    
        
        public function column_default( $item, $column_name )
        {
            switch( $column_name ) { 
                case 'order_id':
                    if ($item[$column_name] > 0) {
                        $extra = null;
                        if ($item['rewardpoints_referral_id']) {
                            $customer = J2t_Rewardpoints::get_referred_friend_customer($item['rewardpoints_referral_id'], $item['customer_id']);
                            if (is_object($customer) && ($email = $customer->get_email())) {
                                $extra = ' / '. sprintf(__('Referral Extra Points (user: %s)', 'j2t-reward-points-for-woocommerce'), $email);
                            } else {
                                $extra = ' / '. __('Referral Extra Points', 'j2t-reward-points-for-woocommerce');;
                            }
                        }

                        return sprintf(__("Related to order #%s", "j2treward"), '<a href="post.php?post='.$item[$column_name].'&action=edit">'.$item[$column_name].'</a>').$extra;
                    } else {
                        if (isset($this->points_type[$item[$column_name]])) {
                            $extra = null;
                            if ($item['rewardpoints_referral_id']) {
                                $customer = J2t_Rewardpoints::get_referred_friend_customer($item['rewardpoints_referral_id'], $item['customer_id']);
                                if (is_object($customer) && ($email = $customer->get_email())) {
                                    $extra = ' / '. sprintf(__('Linked to referred friend (user: %s)', 'j2t-reward-points-for-woocommerce'), $email);
                                } else {
                                    $extra = ' / '. __('Linked to referred friend', 'j2t-reward-points-for-woocommerce');;
                                }
                            }

                            $reviewed_product = null;
                            if ($item['order_id'] == J2t_Rewardpoints::TYPE_POINTS_REVIEW) {
                                $reviewed_product = wc_get_product($item['rewardpoints_linker']);
                            }
                            if (is_object($reviewed_product)) {
                                return sprintf(__('Review Points for product: %s.'), $reviewed_product->get_title()).$extra;
                            } else {
                                return $this->points_type[$item[$column_name]].$extra;
                            }
                            return $this->points_type[$item[$column_name]].$extra;
                        } else {
                            return __( 'NA', 'j2t-reward-points-for-woocommerce' );
                        }
                    }
                case 'points_current':
                case 'points_spent':
                case 'rewardpoints_description':
                    return "<strong>".$item[$column_name]."</strong>";
                case 'date_insertion':
                    return date_i18n( wc_date_format(), strtotime($item[$column_name]));
                default:  
                    return $item[$column_name];    
            }
        }
        public function get_sortable_columns() {
            $sortable_columns = array(
                'date_insertion'    => array('date_insertion', true),
                'points_current'     => array('points_current',true), 
                'points_spent' => array('points_spent',true) ); 
            return $sortable_columns;
        }
        public function get_hidden_columns()
        {
            return array();
        }
        
        function first_column_name($item) {
            $actions = array(
                'export'      => sprintf('<a href="?page=j2t_rewardpoints_detail_page&user=%s">Export</a>',$item['id']),
            );
            return sprintf('%1$s %2$s', $item['first_column_name'], $this->row_actions($actions) );
        }
        
        public function process_bulk_action()
        {  
            global $wpdb;
            if ('export' === $this->current_action()) {
                echo 'export';
                die;
            }
        }
        
        private function table_data()
        {      
            global $wpdb;  
            $table_name = $wpdb->prefix . 'rewardpoints_account';  
            $usermeta_table_name = $wpdb->prefix . 'usermeta';  
            $users_table_name = $wpdb->prefix . 'users';  
            $data=array();  

            
            $user_id = isset($_GET['user']) ? sanitize_key($_GET['user']) : 0;
            if(isset($_GET['s']))
            {          

                $search=sanitize_text_field($_GET['s']);  
                $search = trim($search);  

                $rewardpoints_entries = $wpdb->get_results("
                    SELECT * FROM $table_name 
                    WHERE customer_id = '$user_id'
                    AND (points_current LIKE '%".$wpdb->esc_like($search)."%' OR points_spent LIKE '%".$wpdb->esc_like($search)."%' OR rewardpoints_description LIKE '%".$wpdb->esc_like($search)."%')
                    ");  
            }  
            else{
                $rewardpoints_entries=$wpdb->get_results("
                    SELECT * FROM $table_name 
                    WHERE customer_id = '$user_id'
                ");
            }
            $i=0;
    
            foreach ($rewardpoints_entries as $rewardpoints_data) {
                $data[] = array(  
                    'order_id'  => $rewardpoints_data->order_id,  
                    'points_current'  => $rewardpoints_data->points_current,  
                    'customer_id'  => $rewardpoints_data->customer_id,
                    'rewardpoints_referral_id' => $rewardpoints_data->rewardpoints_referral_id,
                    'points_spent' =>   $rewardpoints_data->points_spent,
                    'rewardpoints_description' =>   $rewardpoints_data->rewardpoints_description,
                    'rewardpoints_linker' => $rewardpoints_data->rewardpoints_linker,
                    'date_insertion' => $rewardpoints_data->date_insertion
                    ); 
                $i++;  
            }  
            return $data;  
        }

        public function prepare_items()  
        {
            global $wpdb;
            $columns = $this->get_columns();  
            $sortable = $this->get_sortable_columns();  
            $hidden=$this->get_hidden_columns();  
            $this->process_bulk_action();  
            $data = $this->table_data();              
            $totalitems = count($data);  
            $user = get_current_user_id();  
            $screen = get_current_screen();  
            $option = $screen->get_option('per_page', 'option');   
            $perpage = get_user_meta($user, $option, true);
            $perpage = $perpage ? $perpage : 10;
            $this->_column_headers = array($columns,$hidden,$sortable);  
            if ( empty ( $per_page) || $per_page < 1 ) {              
                $per_page = $screen->get_option( 'per_page', 'default' );   
            }  

            function usort_reorder($a,$b){  
                $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'order_id'; //If no sort, default to title  
                $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'desc'; //If no order, default to asc  
                $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order  
                return ($order==='asc') ? $result : -$result; //Send final sort direction to usort  
            }

            usort($data, 'usort_reorder');
            $totalpages = ceil($totalitems/$perpage);   
            $currentPage = $this->get_pagenum();                  
            $data = array_slice($data,(($currentPage-1)*$perpage),$perpage);  
            $this->set_pagination_args( array(  
                "total_items" => $totalitems,  
                "total_pages" => $totalpages,  
                "per_page" => $perpage,
            ) );
            $this->items =$data;
        }

        /**
         * Display text for when there are no items.
         */
        public function no_items() {
            esc_html_e( 'No rewardpoints found.', 'j2t-reward-points-for-woocommerce' );
        }

        public function get_columns() {
            return array(
                'order_id'  => __( 'Type of points', 'j2t-reward-points-for-woocommerce' ),
                'points_current'  => __( 'Gathered points', 'j2t-reward-points-for-woocommerce' ),
                'points_spent'  => __( 'Used points', 'j2t-reward-points-for-woocommerce' ),
                'date_insertion'  => __( 'Insertion Date', 'j2t-reward-points-for-woocommerce' ),
                'rewardpoints_description'   => __( 'Description', 'j2t-reward-points-for-woocommerce' ),
            );
        }
    
    }
    $wp_list_table = new J2t_Rewardpoints_List_Table();  

    ?>
	<div class="wrap">
        <?php 
        $user = get_user_by( 'id', $_GET['user'] ); 
        $current_user = __( 'NA', 'j2t-reward-points-for-woocommerce' );
        if ($user !== false) {
            $current_user = sprintf(__( '%s %s (%s)', 'j2t-reward-points-for-woocommerce' ), $user->user_firstname, $user->user_lastname, $user->user_email);
        }
        ?>
		<h2><?php printf(__( 'Points & Rewards History for user : %s', 'j2t-reward-points-for-woocommerce' ), $current_user); ?></h2>
        <a href="?page=j2trewardpoints"><?php echo __('Back', 'j2t-reward-points-for-woocommerce'); ?></a>
		<form id="all-drafts" method="get">
			<input type="hidden" name="page" value="rewardpoints_detail" />
            <input type="hidden" name="user" value="<?php echo esc_attr($_GET['user']);?>" />

			<?php
			$wp_list_table->prepare_items();
			$wp_list_table->search_box( 'Search', 'search' );
			$wp_list_table->display();
			?>
		</form>
	</div>
	<?php
}


