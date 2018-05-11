<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 11.05.2018
 * Time: 21:18
 */

namespace vivace\db\sql\Statement;


final class Insert implements Modifier
{
    public $source;
    public $columns = [];
    public $values = [];

    /**
     * Insert constructor.
     *
     * @param $source
     * @param array $columns
     * @param array $values
     */
    public function __construct($source, array $columns, array $values)
    {
        $this->source = $source;
        $this->columns = $columns;
        $this->values = $values;
    }

    public function add(array $value)
    {
        $this->values[] = $value;
    }
}