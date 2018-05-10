<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 04.05.2018
 * Time: 23:43
 */

namespace vivace\db\sql;


use Throwable;

class PDOException extends \Exception
{
    public function __construct(string $sqlstate = "", int $driverCode = 0, $message, \Throwable $previous = null)
    {
        parent::__construct("SQLSTATE[$sqlstate]: ($driverCode) $message", $sqlstate, $previous);
    }

}