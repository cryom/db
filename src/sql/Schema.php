<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 10.05.2018
 * Time: 18:08
 */

namespace vivace\db\sql;


interface Schema extends \IteratorAggregate, \Countable
{

    public function getAutoincrement(): ?Field;

    /**
     * @return array|Field[]
     */
    public function getPrimary(): ?array;

    /**
     * @return array|Field[]
     */
    public function getUnique(): ?array;

    /**
     * @param string $key
     *
     * @throws \Exception
     * @return \vivace\db\sql\Field
     */
    public function get(string $key): Field;

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    public function getNames(): array;


}