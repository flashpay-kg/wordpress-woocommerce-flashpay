<?php

class FlashPayLanguage
{
    const MAPPING = [
        'ru_RU' => 'ru',
        'fr-FR' => 'fr',
        'en_US' => 'en',
        'en_GB' => 'en',
    ];

    const DEFAULT_LANGUAGE = 'ru';

    public static function getLanguage($locale) {
        if (isset(self::MAPPING['$locale'])) {
            return self::MAPPING['$locale'];
        }

        return self::DEFAULT_LANGUAGE;
    }
}
