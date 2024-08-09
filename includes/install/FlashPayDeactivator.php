<?php

require_once plugin_dir_path(__FILE__) . 'FlashPayInstaller.php';

/**
 * Fired during plugin deactivation
 */
class FlashPayDeactivator extends FlashPayInstaller
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     */
    public static function deactivate()
    {
        delete_option('woocommerce_flashpay_card_settings');

        self::log('info', 'FlashPay plugin deactivated!');
    }

}
