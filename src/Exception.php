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
    const STATEMENT_NOT_EXPECTED = 100100;

    public static function statementNotExpected(string $message)
    {
        return new Exception($message, self::STATEMENT_NOT_EXPECTED);
    }
}