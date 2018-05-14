<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 29.04.2018
 * Time: 13:13
 */

namespace vivace\db;


class Exception extends \Exception
{
    const BUILDING = 0x0A000F;
    const SAVING = 0x0B000F;
    const EXECUTING = 0x0C000F;

    public static function onBuilding(string $message)
    {
        return new Exception($message, self::BUILDING);
    }

    public static function onSaving(string $message)
    {
        return new Exception($message, self::SAVING);
    }

    public static function onExecuting(string $message)
    {
        return new Exception($message, self::EXECUTING);
    }
}