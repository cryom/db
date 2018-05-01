<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 28.02.2018
 * Time: 15:44
 */

namespace vivace\db\sql\statement;


final class Delete implements Modifier
{
    /** @var string */
    public $from;
    /** @var array */
    public $where;
    /** @var int */
    public $limit;
    public $offset;
    public $order;

    /**
     * Delete constructor.
     * @param string $from
     */
    public function __construct(string $from)
    {
        $this->from = $from;
    }

}