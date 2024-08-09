<?php


class FlashPayCurrencyCode extends FlashPayAbstractEnum
{
    /** Российский рубль */
    const RUB = 'RUB';
    /** Доллар США */
    const USD = 'USD';
    /** Евро */
    const EUR = 'EUR';
    /** Белорусский рубль */
    const BYN = 'BYN';
    /** Китайская йена */
    const CNY = 'CNY';
    /** Казахский тенге */
    const KZT = 'KZT';
    /** Украинская гривна */
    const UAH = 'UAH';
    /** Узбекский сум */
    const UZS = 'UZS';
    /** Турецкая лира */
    const _TRY = 'TRY';
    /** Индийская рупия */
    const INR = 'INR';
    /** Молдавский лей */
    const MDL = 'MDL';
    /** Азербайджанский манат */
    const AZN = 'AZN';

    protected static $validValues = array(
        self::RUB => true,
        self::USD => true,
        self::EUR => true,
        self::BYN => true,
        self::CNY => true,
        self::KZT => true,
        self::UAH => true,
        self::UZS => true,
        self::_TRY => true,
        self::INR => true,
        self::MDL => true,
        self::AZN => true,
    );
}
