<?php
/*
Plugin Name: WooCommerce Product Personalised Message
Plugin URI: https://github.com/mightymuke/woocommerce-product-personalised-message
Description: Add an option to your products to enable personalised messages. Optionally charge a fee.
Version: 1.1.0
Author: Marcus Bristol
Author URI: http://rededge.co.nz
Requires at least: 3.5
Tested up to: 4.0
Text Domain: woocommerce-product-personalised-message
Domain Path: /languages/

	Copyright: � 2015 Marcus Bristol.
	Copied from plugin: WooCommerce Product Gift Wrap (https://github.com/mikejolley/woocommerce-product-gift-wrap)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Localisation
 */
load_plugin_textdomain( 'woocommerce-product-personalised-message', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

/**
 * WC_Product_Personalised_Message class.
 */
class WC_Product_Personalised_Message {

	/**
	 * Hook us in :)
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$default_message = sprintf( __( "Add a personalised message to your item for %s?", "woocommerce-product-personalised-message" ), '{price}' ) . ' {textarea}';

		$this->personalised_message_enabled = get_option( 'product_personalised_message_enabled' ) == 'yes' ? true : false;
		$this->personalised_message_cost    = get_option( 'product_personalised_message_cost', 0 );
		$this->personalised_message_label   = get_option( 'product_personalised_message_label' );

		if ( ! $this->personalised_message_label ) {
			$this->personalised_message_label = $default_message;
		}

		add_option( 'product_personalised_message_enabled', 'no' );
		add_option( 'product_personalised_message_cost', '0' );
		add_option( 'product_personalised_message_label', $default_message );

		// Init settings
		$this->settings = array(
			array(
				'name'     => __( 'Personalised Messages Enabled by Default?', 'woocommerce-product-personalised-message' ),
				'desc'     => __( 'Enable this to allow personalised messages by default.', 'woocommerce-product-personalised-message' ),
				'id'       => 'product_personalised_message_enabled',
				'type'     => 'checkbox',
			),
			array(
				'name'     => __( 'Default Personalised Message Cost', 'woocommerce-product-personalised-message' ),
				'desc'     => __( 'The cost of personalised messages unless overridden per-product.', 'woocommerce-product-personalised-message' ),
				'id'       => 'product_personalised_message_cost',
				'type'     => 'text',
				'desc_tip' => true
			),
			array(
				'name'     => __( 'Personalised Message Label', 'woocommerce-product-personalised-message' ),
				'id'       => 'product_personalised_message_label',
				'desc' 		=> __( 'Note: <code>{textarea}</code> will be replaced with a textarea and <code>{price}</code> will be replaced with the personalised message cost.', 'woocommerce-product-personalised-message' ),
				'type'     => 'text',
				'desc_tip' => __( 'The label shown to the user on the frontend.', 'woocommerce-product-personalised-message' )
			)
		);

		// Display on the front end
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'personalised_message_html' ), 10 );

		// Filters for cart actions
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
		add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 10, 1 );
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_order_item_meta' ), 10, 2 );

		// Write Panels
		add_action( 'woocommerce_product_options_pricing', array( $this, 'write_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'write_panel_save' ) );

		// Admin
		add_action( 'woocommerce_settings_general_options_end', array( $this, 'admin_settings' ) );
		add_action( 'woocommerce_update_options_general', array( $this, 'save_admin_settings' ) );
	}

	/**
	 * Show the personalised message on the frontend
	 *
	 * @access public
	 * @return void
	 */
	public function personalised_message_html() {
		global $post;

		$is_personalisable = get_post_meta( $post->ID, '_is_personalisable', true );

		if ( $is_personalisable == '' && $this->personalised_message_enabled ) {
			$is_personalisable = 'yes';
		}

		if ( $is_personalisable == 'yes' ) {

			$current_value = ! empty( $_REQUEST['personalised_message'] ) ? $_REQUEST['personalised_message'] : '';

			$cost = get_post_meta( $post->ID, '_personalised_message_cost', true );

			if ( $cost == '' ) {
				$cost = $this->personalised_message_cost;
			}

			$price_text    = $cost > 0 ? woocommerce_price( $cost ) : __( 'free', 'woocommerce-product-personalised-message' );
			$textarea      = '<textarea name="personalised_message"></textarea>';

			woocommerce_get_template( 'personalised-message.php', array(
				'personalised_message_label' => $this->personalised_message_label,
				'textarea'                   => $textarea,
				'price_text'                 => $price_text
			), 'woocommerce-product-personalised-message', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
		}
	}

	/**
	 * When added to cart, save any personalised message
	 *
	 * @access public
	 * @param mixed $cart_item_meta
	 * @param mixed $product_id
	 * @return void
	 */
	public function add_cart_item_data( $cart_item_meta, $product_id ) {
		$is_personalisable = get_post_meta( $product_id, '_is_personalisable', true );

		if ( $is_personalisable == '' && $this->personalised_message_enabled ) {
			$is_personalisable = 'yes';
		}

		if ( ! empty( $_POST['personalised_message'] ) && $is_personalisable == 'yes' ) {
			$cart_item_meta['personalised_message_exists'] = true;
			$cart_item_meta['personalised_message'] = $_POST['personalised_message'];
		}

		return $cart_item_meta;
	}

	/**
	 * Get the gift data from the session on page load
	 *
	 * @access public
	 * @param mixed $cart_item
	 * @param mixed $values
	 * @return void
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {

		if ( ! empty( $values['personalised_message'] ) ) {
			$cart_item['personalised_message'] = $values['personalised_message'];

			$cost = get_post_meta( $cart_item['data']->id, '_personalised_message_cost', true );

			if ( $cost == '' ) {
				$cost = $this->personalised_message_cost;
			}

			$cart_item['data']->adjust_price( $cost );
		}

		return $cart_item;
	}

	/**
	 * Display gift data if present in the cart
	 *
	 * @access public
	 * @param mixed $other_data
	 * @param mixed $cart_item
	 * @return void
	 */
	public function get_item_data( $item_data, $cart_item ) {
		if ( ! empty( $cart_item['personalised_message'] ) )
			$item_data[] = array(
				'name'    => __( 'Personalised Message', 'woocommerce-product-personalised-message' ),
				'value'   => __( 'personalised_message', 'woocommerce-product-personalised-message' ),
				'display' => $cart_item['personalised_message']
			);

		return $item_data;
	}

	/**
	 * Adjust price after adding to cart
	 *
	 * @access public
	 * @param mixed $cart_item
	 * @return void
	 */
	public function add_cart_item( $cart_item ) {
		if ( ! empty( $cart_item['personalised_message'] ) ) {

			$cost = get_post_meta( $cart_item['data']->id, '_personalised_message_cost', true );

			if ( $cost == '' ) {
				$cost = $this->personalised_message_cost;
			}

			$cart_item['data']->adjust_price( $cost );
		}

		return $cart_item;
	}

	/**
	 * After ordering, add the data to the order line items.
	 *
	 * @access public
	 * @param mixed $item_id
	 * @param mixed $values
	 * @return void
	 */
	public function add_order_item_meta( $item_id, $cart_item ) {
		if ( ! empty( $cart_item['personalised_message'] ) ) {
			woocommerce_add_order_item_meta( $item_id, __( 'Personalised Message', 'woocommerce-product-personalised-message' ), __( $cart_item['personalised_message'], 'woocommerce-product-personalised-message' ) );
		}
	}

	/**
	 * write_panel function.
	 *
	 * @access public
	 * @return void
	 */
	public function write_panel() {
		global $post;

		echo '</div><div class="options_group show_if_simple show_if_variable">';

		$is_personalisable = get_post_meta( $post->ID, '_is_personalisable', true );

		if ( $is_personalisable == '' && $this->personalised_message_enabled ) {
			$is_personalisable = 'yes';
		}

		woocommerce_wp_checkbox( array(
				'id'            => '_is_personalisable',
				'wrapper_class' => '',
				'value'         => $is_personalisable,
				'label'         => __( 'Personalisable', 'woocommerce-product-personalised-message' ),
				'description'   => __( 'Enable this option if the customer can choose personalised messages.', 'woocommerce-product-personalised-message' ),
			) );

		woocommerce_wp_text_input( array(
				'id'          => '_personalised_message_cost',
				'label'       => __( 'Personalised Message Cost', 'woocommerce-product-personalised-message' ),
				'placeholder' => $this->personalised_message_cost,
				'desc_tip'    => true,
				'description' => __( 'Override the default cost by inputting a cost here.', 'woocommerce-product-personalised-message' ),
			) );

		wc_enqueue_js( "
			jQuery('input#_is_personalisable').change(function(){

				jQuery('._personalised_message_cost_field').hide();

				if ( jQuery('#_is_personalisable').is(':checked') ) {
					jQuery('._personalised_message_cost_field').show();
				}

			}).change();
		" );
	}

	/**
	 * write_panel_save function.
	 *
	 * @access public
	 * @param mixed $post_id
	 * @return void
	 */
	public function write_panel_save( $post_id ) {
		$_is_personalisable = ! empty( $_POST['_is_personalisable'] ) ? 'yes' : 'no';
		$_personalised_message_cost   = ! empty( $_POST['_personalised_message_cost'] ) ? woocommerce_clean( $_POST['_personalised_message_cost'] ) : '';

		update_post_meta( $post_id, '_is_personalisable', $_is_personalisable );
		update_post_meta( $post_id, '_personalised_message_cost', $_personalised_message_cost );
	}

	/**
	 * admin_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_settings() {
		woocommerce_admin_fields( $this->settings );
	}

	/**
	 * save_admin_settings function.
	 *
	 * @access public
	 * @return void
	 */
	public function save_admin_settings() {
		woocommerce_update_options( $this->settings );
	}
}

new WC_Product_Personalised_Message();
