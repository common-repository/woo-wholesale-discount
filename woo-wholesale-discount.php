<?php
/**
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Wholesale Discount
 * Description:       Manage your shop discounts rules.
 * Version:           1.4
 * Author:            Minerva Infotech
 * Author URI:        http://minervainfotech.com
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       Minerva
*/

// If this file is called directly, abort.
if(!defined( 'ABSPATH')) 
{
	die;
}

global $WOOCOMMERCE_WHOLESALE_DISCOUNT_VER;
$WOOCOMMERCE_WHOLESALE_DISCOUNT_VER = "1.4";

define('WOOCOMMERCE_WHOLESALE_DISCOUNT_PATH', plugins_url().'/'. basename(dirname(__FILE__)).'/');
define('WOOCOMMERCE_WHOLESALE_DISCOUNT_IMAGES', plugins_url().'/'. basename(dirname(__FILE__)).'/images/');
define('WOOCOMMERCE_WHOLESALE_DISCOUNT_PHYSICAL_PATH', plugin_dir_path(__FILE__));
define('WOOCOMMERCE_WHOLESALE_DISCOUNT_TITLE', 'WooCommerce Wholesale Discount');


/*------------- Include main class for plugin*/
require_once WOOCOMMERCE_WHOLESALE_DISCOUNT_PHYSICAL_PATH . 'classes/class-wwd-main.php';
$_WooWholesaleDiscount = new WwsdWholesaleDiscountClass();


/*----------- Set plugin version*/
$_WooWholesaleDiscount->wwsd_activate_plugin();


/*----------- Create a woocommerce tab for Wholesale Discount*/
$_WooWholesaleDiscount->init();


function wwsd_plugin_settings_link($links) 
{ 
	$settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=wwsd_wholesale_settings').'">'.__('Settings', 'wwsd-wholesale-discount').'</a>';
	array_unshift($links, $settings_link); 
	return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'wwsd_plugin_settings_link' );
?>