<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 23.04.2018
 * Time: 17:49
 */

namespace vivace\db\sql;


interface Fetcher extends \IteratorAggregate, \Countable
{
    /**
     * @return array|null
     */
    public function one(): ?array;

    public function all(): array;

    public function count(): int;

}