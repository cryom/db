<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 28.02.2018
 * Time: 17:51
 */

namespace vivace\db\sql\statement;


final class Update implements Modifier
{
    /**
     * @var string
     */
    public $source;
    public $set = [];
    public $where;
    public $limit;
    public $offset;
    public $order;

    public function __construct(string $source, array $set)
    {
        $this->source = $source;
        $this->set = $set;
    }
}