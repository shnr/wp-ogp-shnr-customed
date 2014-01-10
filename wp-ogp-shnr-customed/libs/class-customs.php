<?php
/**
* Add custom fields on page
*
*/
if ( ! class_exists('_VM_CustomMetaBox') ) {

	class _VM_CustomMetaBox {

		var $prefix = '';

		var $postTypes = array("page","post");

		var $boxTitle = "OGP Image";

		var $customFields =	array(
			array(
				"name"			=> "my_ogp",
				"description"	=> "",
				"type"			=> "image",
				"scope"			=>	array("page","post"),
				"capability"	=> "manage_options"
			),
		);

		function __construct() {
			add_action( 'admin_enqueue_scripts', array( &$this, 'my_admin_scripts' ) );
			add_action( 'add_meta_boxes', array( &$this, 'createCustomFields' ) );
			add_action( 'save_post', array( &$this, 'saveCustomFields' ), 1, 2 );
		}

		function createCustomFields() {
			if ( function_exists( 'add_meta_box' ) ) {
				foreach ( $this->postTypes as $postType ) {
					add_meta_box( 'my-custom-fields', $this->boxTitle, array( &$this, 'displayCustomFields' ), $postType, 'side', 'low' );
				}
			}
		}

		function displayCustomFields() {
			global $post;
			?>
			<div class="form-wrap">
				<?php
				wp_nonce_field( 'my-custom-fields', 'my-custom-fields_wpnonce', false, true );
				foreach ( $this->customFields as $customField ) {
					// Check scope
					$scope = $customField[ 'scope' ];
					$output = false;
					foreach ( $scope as $scopeItem ) {
						switch ( $scopeItem ) {
							default: {
								if ( $post->post_type == $scopeItem )
									$output = true;
								break;
							}
						}
						if ( $output ) break;
					}
					// Check capability
					if ( !current_user_can( $customField['capability'], $post->ID ) )
						$output = false;
					// Output if allowed
					if ( $output ) { ?>
						<div class="form-field form-required" id="ogp_img_box">
							<?php
							switch ( $customField[ 'type' ] ) {
								case "image":
									// display thumb
									$ogp_img = get_post_meta( $post->ID, $this->prefix . $customField[ 'name' ], true );
									echo '<img src="'.$ogp_img.'" class="ogp_image" />';
									echo '<input type="hidden" name="' . $this->prefix . $customField[ 'name' ] . '" id="' . $this->prefix . $customField[ 'name' ] . '_url" value="' . htmlspecialchars( get_post_meta( $post->ID, $this->prefix . $customField[ 'name' ], true ) ) . '" />';
									echo '<input type="button" class="extra_thumb_button" name="' . $this->prefix . $customField[ 'name' ] . '_bottun" id="' . $this->prefix . $customField[ 'name' ] . '" value="'.__('Click to upload', WPOGP_SHNR_CUS_DOMAIN).'" />';
									break;
								
								default:
									// Plain text field
									echo '<label for="' . $this->prefix . $customField[ 'name' ] .'"><b>' . $customField[ 'title' ] . '</b></label>';
									echo '<input type="text" name="' . $this->prefix . $customField[ 'name' ] . '" id="' . $this->prefix . $customField[ 'name' ] . '" value="' . htmlspecialchars( get_post_meta( $post->ID, $this->prefix . $customField[ 'name' ], true ) ) . '" />';
									break;
							
							}
							?>
							<?php if ( $customField[ 'description' ] ) echo '<p>' . $customField[ 'description' ] . '</p>'; ?>
						</div>
					<?php
					}
				} ?>
			</div>
			<?php
		}

		function saveCustomFields( $post_id, $post ) {
			if ( !isset( $_POST[ 'my-custom-fields_wpnonce' ] ) || !wp_verify_nonce( $_POST[ 'my-custom-fields_wpnonce' ], 'my-custom-fields' ) )
				return;
			if ( !current_user_can( 'edit_post', $post_id ) )
				return;
			if ( ! in_array( $post->post_type, $this->postTypes ) )
				return;
			foreach ( $this->customFields as $customField ) {
				if ( current_user_can( $customField['capability'], $post_id ) ) {
					if ( isset( $_POST[ $this->prefix . $customField['name'] ] ) && trim( $_POST[ $this->prefix . $customField['name'] ] ) ) {
						$value = $_POST[ $this->prefix . $customField['name'] ];
						// Auto-paragraphs for any WYSIWYG
						if ( $customField['type'] == "wysiwyg" ) $value = wpautop( $value );
						update_post_meta( $post_id, $this->prefix . $customField[ 'name' ], $value );
					} else {
						delete_post_meta( $post_id, $this->prefix . $customField[ 'name' ] );
					}
				}
			}
		}

		function my_admin_scripts() {
			wp_enqueue_media();
			wp_register_script('admin-upload-js', WP_PLUGIN_URL.'/'.WPOGP_SHNR_CUS_DOMAIN.'/js/admin-upload.js', array('jquery'));
			wp_enqueue_script('admin-upload-js');
	        wp_enqueue_style( 'upload_style', WP_PLUGIN_URL.'/'.WPOGP_SHNR_CUS_DOMAIN.'/css/upload.css', array(), false, 'all' );   
		}

	}

}

$myCustomFields_var = new _VM_CustomMetaBox();
