<?php

class FlashPayPaymentStatus extends FlashPayAbstractEnum
{
    const SUCCESS = 'success';
    const DECLINE = 'decline';

    protected static $validValues = [
        self::DECLINE => true,
        self::SUCCESS => true,
    ];
}
