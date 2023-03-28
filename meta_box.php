<?php

require_once WP_PLUGIN_DIR.'/assessment/utils.php';

class UnitMetaBox {
	public function __construct( $fields ) {
		$this->fields = $fields;
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post',      array( $this, 'save'         ) );

	}
	public $fields;

	/**
	 * Registers meta boxes per field.
	 */
	public function add_meta_box() {
		foreach ( $this->fields as $key => $value ) {
			add_meta_box(
		    $key,
		    esc_html__( $value, 'example' ),
		    array( $this, 'render_meta_box_content' ),
		    'assessment-unit',
		    'side',
		    'default'
		  );
		}
	}

	/**
	 * Renders meta boxes html with nonce.
	 */
	public function render_meta_box_content( $post, $args ) {
		$value = get_post_meta( $post->ID, "{$args['id']}", true );

		wp_nonce_field( "{$args['id']}", "{$args['id']}_nonce" );

		?>
		<input type="text" id="<?php echo $args['id'] ?>" name="<?php echo $args['id'] ?>" value="<?php echo esc_attr( $value ); ?>" size="10" />
		<?php
	}

	/**
	 * Guard clauses for the save function.
	 */
	public function do_save_guards( $post_id, $nonce, $is_valid_nonce ) {
		if ( !isset( $nonce ) ) {
			return false;
		}
		if ( !is_valid_nonce ) {
			return false;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}
		if ( 'assessment-unit' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) ) {
				return false;
			}
		} else {
			if ( !current_user_can( 'edit_post', $post_id ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Save hook implementation. 
	 */
	public function save( $post_id ) {
		foreach ( $this->fields as $key => $value ) {
			$continue = $this->do_save_guards( $post_id, $_POST["{$key}_nonce"], wp_verify_nonce( $nonce, "{$key}" ) );

			if ( !$continue ) {
				return $post_id;
			}

			$data = sanitize_text_field( $_POST["{$key}"] );
			update_post_meta( $post_id, "{$key}", $data );			
		}
	}
}