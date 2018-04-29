<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 15.02.2018
 * Time: 18:45
 */

namespace vivace\db\sql\expression;

final class Select implements Read
{
    /** @var string */
    public $from;
    /** @var Join[] */
    public $join;
    /** @var array */
    public $where;
    /** @var int */
    public $limit;
    public $offset;
    public $having;
    public $order;
    public $projection;

    /**
     * Select constructor.
     *
     * @param string $from
     */
    public function __construct($from)
    {
        $this->from = $from;
    }

    /**
     * @param bool[]|array[]|null $projection
     *
     * @return static|$this Clone of current instance with changes
     */
    public function projection(?array $projection)
    {
        $obj = clone $this;
        $obj->projection = $projection;
        return $obj;
    }
}
