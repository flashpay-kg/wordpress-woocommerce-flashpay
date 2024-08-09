<?php


class FlashPayPaymentMethodType extends FlashPayAbstractEnum
{

    const BANK_CARD      = 'bank_card';

    protected static $validValues = array(
        self::BANK_CARD      => true,
    );
}
