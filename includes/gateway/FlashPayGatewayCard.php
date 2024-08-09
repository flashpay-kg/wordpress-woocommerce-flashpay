<?php

if (!class_exists('FlashPayGateway')) {
    return;
}

class FlashPayGatewayCard extends FlashPayGateway
{
    public $paymentMethod = FlashPayPaymentMethodType::BANK_CARD;

    public $id = 'flashpay_bank_card';
    public $paymentMethodType = 'card';

    public function __construct()
    {
        parent::__construct();


        $this->icon                   = FlashPay::$pluginUrl.'/assets/images/ac.png';
        $this->method_description     = __('Оплата банковской картой', 'flashpay');
        $this->method_title           = __('Банковские карты', 'flashpay');

        $this->defaultTitle           = __('Банковские карты — Visa, Mastercard и Maestro, «Мир»', 'flashpay');
        $this->defaultDescription     = __('Оплата банковской картой', 'flashpay');

        $this->title                  = $this->getTitle();
        $this->description            = $this->getDescription();

        $this->enableRecurrentPayment = $this->get_option('save_payment_method') == 'yes';
        $this->supports               = array_merge($this->supports, array(
            'subscriptions',
            'tokenization',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_date_changes',
        ));
        $this->has_fields             = true;
    }

    public function init_form_fields()
    {
        parent::init_form_fields();
    }

    public function is_available()
    {
        if (is_add_payment_method_page() && !$this->enableRecurrentPayment) {
            return false;
        }

        return parent::is_available();
    }

    public function payment_fields()
    {
        parent::payment_fields();
        $displayTokenization = $this->supports('tokenization') && is_checkout() && $this->enableRecurrentPayment;
        if ($displayTokenization) {
            $this->saved_payment_methods();
            $this->save_payment_method_checkbox();
        }
    }
}
