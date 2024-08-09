<?php


/**
 * Признак способа расчета передается в параметре `payment_mode`.
 */
class FlashPayPaymentMode extends FlashPayAbstractEnum
{
    /** Полная предоплата */
    const FULL_PREPAYMENT = 'full_prepayment';
    /** Частичная предоплата */
    const PARTIAL_PREPAYMENT = 'partial_prepayment';
    /** Аванс */
    const ADVANCE = 'advance';
    /** Полный расчет */
    const FULL_PAYMENT = 'full_payment';
    /** Частичный расчет и кредит */
    const PARTIAL_PAYMENT = 'partial_payment';
    /** Кредит */
    const CREDIT = 'credit';
    /** Выплата по кредиту */
    const CREDIT_PAYMENT = 'credit_payment';

    protected static $validValues = array(
        self::FULL_PREPAYMENT    => true,
        self::PARTIAL_PREPAYMENT => true,
        self::ADVANCE            => true,
        self::FULL_PAYMENT       => true,
        self::PARTIAL_PAYMENT    => true,
        self::CREDIT             => true,
        self::CREDIT_PAYMENT     => true,
    );
}
