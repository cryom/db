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
    const INVALID_STATEMENT = 100100;
    const INVALID_PROJECTION = 200100;

    public static function invalidStatement(string $message)
    {
        return new Exception($message, self::INVALID_STATEMENT);
    }

    public static function invalidProjection(string $message)
    {
        return new Exception($message, self::INVALID_PROJECTION);
    }
}