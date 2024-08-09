<?php


abstract class FlashPayAbstractEnum
{
    protected static $validValues = [];

    public static function valueExists($value)
    {
        return array_key_exists($value, static::$validValues);
    }

    public static function getValidValues()
    {
        return array_keys(static::$validValues);
    }

    public static function getEnabledValues()
    {
        $result = array();
        foreach (static::$validValues as $key => $enabled) {
            if ($enabled) {
                $result[] = $key;
            }
        }
        return $result;
    }
}
