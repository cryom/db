<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 15.02.2018
 * Time: 18:45
 */

namespace vivace\db\sql\Statement;

final class Select implements Read
{
    /** @var string */
    public $source;
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
     * @param string $source
     */
    public function __construct($source)
    {
        $this->source = $source;
    }
}
