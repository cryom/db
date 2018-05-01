<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 15.02.2018
 * Time: 21:54
 */

namespace vivace\db\sql\statement;


final class Join
{
    const LEFT = 0;
    const RIGHT = 1;
    const INNER = 2;

    public $type = self::LEFT;
    /** @var string */
    public $source;
    /** @var array */
    public $on;

    private function __construct()
    {
    }

    public static function left($source, array $on = [])
    {
        $obj = new Join();
        $obj->type = self::LEFT;
        $obj->source = $source;
        $obj->on = $on;

        return $obj;
    }

    public static function right($source, array $on = [])
    {
        $obj = new Join();
        $obj->type = self::RIGHT;
        $obj->source = $source;
        $obj->on = $on;

        return $obj;
    }

    public static function inner($source, array $on = [])
    {
        $obj = new Join();
        $obj->type = self::INNER;
        $obj->source = $source;
        $obj->on = $on;

        return $obj;
    }
}