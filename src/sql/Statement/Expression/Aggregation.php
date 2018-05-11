<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 11.05.2018
 * Time: 14:08
 */

namespace vivace\db\sql\Statement\Expression;


abstract class Aggregation
{
    public $expression;

    /**
     * Aggregation constructor.
     *
     * @param $expression
     */
    public function __construct($expression)
    {
        $this->expression = $expression;
    }

}