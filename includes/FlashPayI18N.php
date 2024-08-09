<?php


class FlashPayI18N
{
    public function load_plugin_textdomain()
    {

        load_plugin_textdomain(
            'flashpay',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );

    }

}
