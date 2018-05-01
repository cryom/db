<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 28.02.2018
 * Time: 17:51
 */

namespace vivace\db\sql\statement;


final class Update
{
    /**
     * @var string
     */
    public $table;
    public $set = [];
    public $where;
    public $limit;
    public $offset;
    public $order;

    public function __construct(string $table, array $set)
    {
        $this->table = $table;
        $this->set = $set;
    }

    public function set(array $data): Update
    {
        $this->set = $data;
        return $this;
    }
}