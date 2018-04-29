<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 17.04.2018
 * Time: 16:42
 */

namespace vivace\db\sql\expression;


final class Columns implements Read
{
    /**
     * @var string
     */
    public $sourceName;

    public function __construct(string $source)
    {
        $this->sourceName = $source;
    }
}