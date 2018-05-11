<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 11.05.2018
 * Time: 1:42
 */

namespace vivace\db\sql\Statement;


final class Count implements Read
{
    public $column;
    /** @var string */
    public $source;
    /** @var Join[] */
    public $join;
    /** @var array */
    public $where;
    /** @var int */
    public $limit;
    public $offset;
    public $order;

    /**
     * Count constructor.
     *
     * @param string $source
     */
    public function __construct(string $source)
    {
        $this->source = $source;
    }

}