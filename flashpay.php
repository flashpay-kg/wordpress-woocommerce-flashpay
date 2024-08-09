<?php

/**
 * @package           FlashPay
 *
 * @wordpress-plugin
 * Plugin Name:       FlashPay для WooCommerce
 * Plugin URI:        https://flashpay.kg
 * Description:       Платежный модуль для работы с сервисом FlashPay через плагин WooCommerce
 * Version:           1.0.0
 * Author:            FlashPay
 * Author URI:        https://flashpay.kg
 * License URI:       https://flashpay.kg
 * Text Domain:       flashpay
 * Domain Path:       /languages
 */

if (!defined('WPINC')) {
    die;
}

function flashpay_plugin_activate()
{
    if (!flashpay_check_woocommerce_plugin_status()) {
        deactivate_plugins(__FILE__);
        $error_message = __("Плагин Flashpay для WooCommerce требует, чтобы плагин <a href=\"https://wordpress.org/extend/plugins/woocommerce/\" target=\"_blank\">WooCommerce</a> был активен!", 'flashpay');
        wp_die($error_message);
    }
    require_once plugin_dir_path(__FILE__) . 'includes/install/FlashPayActivator.php';
    FlashPayActivator::activate();
}

function flashpay_plugin_deactivate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/install/FlashPayDeactivator.php';
    FlashPayDeactivator::deactivate();
}

function flashpay_check_woocommerce_plugin_status()
{
    if (defined("RUNNING_CUSTOM_WOOCOMMERCE") && RUNNING_CUSTOM_WOOCOMMERCE === true) {
        return true;
    }
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        return true;
    }
    if (!is_multisite()) return false;
    $plugins = get_site_option('active_sitewide_plugins');
    return isset($plugins['woocommerce/woocommerce.php']);
}

register_activation_hook(__FILE__, 'flashpay_plugin_activate');
register_deactivation_hook(__FILE__, 'flashpay_plugin_deactivate');

if (flashpay_check_woocommerce_plugin_status()) {
    require plugin_dir_path(__FILE__) . 'includes/FlashPay.php';

    $plugin = new FlashPay();

    define('FLASHPAY_VERSION', $plugin->getVersion());

    $plugin->run();
}
