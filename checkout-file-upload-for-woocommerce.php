<?php
/*
Plugin Name: Checkout File Upload for WooCommerce
Description: Easily enable customers to upload patterns, images,...before adding to checkout.
Author: add-ons.org
Version: 2.1.5
Requires Plugins: woocommerce
Author URI: https://add-ons.org/
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
define( 'SUPERADDONS_WOO_CHECKOUT_UPLOADS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SUPERADDONS_WOO_CHECKOUT_UPLOADS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
function Superaddons_Checkout_Uploads_Init(){
    include SUPERADDONS_WOO_CHECKOUT_UPLOADS_PLUGIN_PATH."backend/upload_block.php";
    include SUPERADDONS_WOO_CHECKOUT_UPLOADS_PLUGIN_PATH."backend/index.php";
    include SUPERADDONS_WOO_CHECKOUT_UPLOADS_PLUGIN_PATH."frontend/index.php";
}
add_action( 'woocommerce_loaded', 'Superaddons_Checkout_Uploads_Init', 10, 1 ); 