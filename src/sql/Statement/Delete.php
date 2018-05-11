<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 28.02.2018
 * Time: 15:44
 */

namespace vivace\db\sql\Statement;


final class Delete implements Modifier
{
    /** @var string */
    public $source;
    /** @var array */
    public $where;
    /** @var int */
    public $limit;
    public $offset;
    public $order;

    /**
     * Delete constructor.
     *
     * @param string $source
     */
    public function __construct(string $source)
    {
        $this->source = $source;
    }

}