<?php
/*
 * Plugin Name: Unit Assessment
 * Author: Adam Roberts
 */

require_once WP_PLUGIN_DIR . '/assessment/meta_box.php';

class UnitPlugin {
	public $post_type = 'assessment-unit';

	public function __construct() {
		add_action( 'init', array( $this, 'def_assessment_unit_type' ) );

		add_filter( 'manage_assessment-unit_posts_columns', array( $this, 'register_custom_columns' ), 10, 2 );
		add_action( 'manage_assessment-unit_posts_custom_column', array( $this, 'handle_floor_plan_column'), 10, 2 );

		add_action( 'load-post.php', array( $this, 'setup_meta_boxes' ) );
		add_action( 'load-post-new.php', array( $this, 'setup_meta_boxes' ) );

		add_action( 'admin_menu', array( $this, 'setup_menu') );

		add_action( 'wp_ajax_call_from_ui', array( $this, 'call_from_ui' ) );
		add_action( 'wp_ajax_nopriv_call_from_ui', array( $this, 'verify_login' ) );
		
		add_shortcode( 'units', array( $this, 'def_shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_plugin_css' ) );
	}

	/**
	 * Registers custom unit post type.
	 */
	public function def_assessment_unit_type() {
		register_post_type('assessment-unit',
			array(
				'labels'      => array(
					'name'          => __('Units', 'textdomain'),
					'singular_name' => __('Unit', 'textdomain'),
					'edit_item' =>  __('Edit Unit', 'textdomain'),
					'add_new' =>__('Add Unit', 'textdomain'),
					'add_new_item' =>__('Add New Unit', 'textdomain')
				),
					'public'      => true,
					'has_archive' => true,
			)
		);
	}

	/**
	 * Registers custom columns.
	 */
	public function register_custom_columns( $defaults ) {
		$defaults['floor-plan-id'] = 'Floor Plan Id';
		$defaults[ 'title' ] = 'Unit #';
		return $defaults;
	}

	/**
	 * Displays floor plan id in custom column.
	 */
	public function handle_floor_plan_column( $column_name, $post_id ) {
		if ( $column_name == 'floor-plan-id' ) {
			echo get_post_meta( $post_id, "unit-floor-plan-id", true );
		}
	}

	/**
	 * Dynamically sets up meta boxes with array of fields.
	 */
	public function setup_meta_boxes() {
		$fields = array(
			'unit-asset-id' => "Asset Id", 
			'unit-building-id' => "Building Id", 
			'unit-floor-id' => "Floor Id", 
			'unit-floor-plan-id' => "Floor Plan Id", 
			'unit-area-id' => "Area Id");

		$new = new UnitMetaBox( $fields );
	}

	/**
	 * Sets up the plugin menu.
	 */
	public function setup_menu(){
	    add_menu_page( 'Assessment Menu', 'Assessment Menu', 'manage_options', 'assessment-plugin', array( $this, 'init_menu' ) );
	}

	/**
	 * Initializes the plugin menu.
	 */
	public function init_menu(){
		$success = get_option( 'generate_units_success' );

		$nonce = wp_create_nonce( 'unit_menu_nonce' );
		$link = admin_url( 'admin-ajax.php?action=call_from_ui&nonce=' . $nonce );
		echo '<br><div style="display: inline; padding: .5em; background-color: orange;"><a data-nonce="' . $nonce . '" href="' . $link . '">Generate units from API</a></div><br><br>' . $success ;
	}

	/**
	 * Callback from admin-ajax.php; calls unit API and subsequently creates units.
	 */
	public function call_from_ui() {
		if ( !wp_verify_nonce( $_REQUEST['nonce'], "unit_menu_nonce")) {
	      exit( "Unverified nonce" );
	    }   

	    $url = UNIT_URL;
		$api_key = UNIT_API_KEY;
		
		$curl = curl_init();
		
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
			  "API-key: {$api_key}"
			),
		));

	    $response = curl_exec($curl);
	    $err = curl_error($curl);
	    curl_close($curl);
	    
	    if ( $err ) {
	    	Utils::write_log( $err );
	    } else {
		    $response_obj = json_decode($response);
		    $array = json_decode( json_encode( $response_obj ), true );

		    for ($i = 0; $i < 100; $i++) {
		    	$unit_number = $array['data'][$i]['unit_number'];
			    $area = $array['data'][$i]['area'];
			    $floor_plan_id = $array['data'][$i]['floor_plan_id'];
			    $floor_id = $array['data'][$i]['floor_id'];
			    $building_id = $array['data'][$i]['building_id'];
			    $asset_id = $array['data'][$i]['asset_id'];
			    
				$post = array(
					'post_title'   => $unit_number,
					'post_content' => "A new unit",
					'post_status'  => 'publish',
					'post_type'    => 'assessment-unit'
				);
				$post_id = wp_insert_post( $post );

				add_post_meta( $post_id, "unit-title", $unit_number );
				add_post_meta( $post_id, "unit-asset-id", $asset_id );
				add_post_meta( $post_id, "unit-building-id", $building_id );	
				add_post_meta( $post_id, "unit-floor-id", $floor_id );	
				add_post_meta( $post_id, "unit-floor-plan-id", $floor_plan_id );	
				add_post_meta( $post_id, "unit-area-id", $area );

				update_option('generate_units_success', "Successfully created 100 units!");
		    }  
	    }
	   
	    header("Location: " . $_SERVER["HTTP_REFERER"]); 
	    die();
	}


	/**
	 * Displays login message.
	 */
	public function verify_login() {
	   echo "You must log in to do that.";
	   die();
	}

	/**
	 * Defines [units] shortcode, which displays all units.
	 */
	public function def_shortcode() {
		$query_params_greater_than_one = array( 
			'post_type' => 'assessment-unit',
			'meta_query' => array(
		        array(
		            'key'     => 'unit-area-id',
		            'value'   => '1',
		            'compare' => '>',
		        ),
		    ),
		);

		$query_params_equals_one = array( 
			'post_type' => 'assessment-unit',
			'meta_query' => array(
		        array(
		            'key'     => 'unit-area-id',
		            'value'   => '1',
		            'compare' => '=',
		        ),
		    ),
		);

		$html_equals_one = "<h3>Units - area equals 1</h3>" . $this->query_and_build_html( $query_params_equals_one, get_the_ID() );
		$html_greater_than_one = "<h3>Units - area > 1</h3>" . $this->query_and_build_html( $query_params_greater_than_one, get_the_ID() );
		
		return $html_equals_one . $html_greater_than_one;
	}

	/**
	 * Queries and builds html for a given query.
	 */
	public function query_and_build_html( $query_params, $outer_id ) {	
		$query = new WP_Query( $query_params );

		$html = "";

		if ( $query->have_posts() ) {
			$html .= '<div class="unit-outer-flex">';
			$first = true;
			while ( $query->have_posts() ) {
				$id = get_the_ID();

				if ( $outer_id == $id ) {
					// Infinite loop without this
					$query->the_post();
				}
				else {
					// Infinite loop without this
					$query->the_post();
					
					$title = get_post_meta( $id, 'unit-title', true );
					$asset_id = get_post_meta( $id, 'unit-asset-id', true );
					$floor_id = get_post_meta( $id, 'unit-floor-id', true );
					$building_id = get_post_meta( $id, 'unit-building-id', true );
					$floor_plan_id = get_post_meta( $id, 'unit-floor-plan-id', true );
					$area_id = get_post_meta( $id, 'unit-area-id', true );

					$html .= '<div class="unit-inner-flex">';
					
					$html .= '<p>Unit #: ' . $title . '</p>';
					
					$html .= '<div class="unit-inner-flex-item"><li>Asset id: ' . $asset_id . '</li></div>';
					$html .= '<div class="unit-inner-flex-item"><li>Floor id: ' . $floor_id . '</li></div>';
					$html .= '<div class="unit-inner-flex-item"><li>Building id: ' . $building_id . '</li></div>';
					$html .= '<div class="unit-inner-flex-item"><li>Floor plan id: ' . $floor_plan_id . '</li></div>';
					$html .= '<div class="unit-inner-flex-item"><li>Area id: ' . $area_id . '</li></div>';

					$html .= '</div>';
				}
			}
			$html .= '</div>';
		}
		return $html;

	}

	/**
	 * Loads the css for the plugin. 
	 */
	public function load_plugin_css() {
	    $plugin_url = plugin_dir_url( __FILE__ );

	    wp_enqueue_style( 'style1', $plugin_url . 'style.css' );
	}
}

$unit = new UnitPlugin;

