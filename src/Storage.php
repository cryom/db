<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 28.02.2018
 * Time: 14:33
 */

namespace vivace\db;


use vivace\db\Relation;

interface Storage
{
    /**
     * @param null|array $filter
     *
     * @return \vivace\db\Finder
     */
    public function filter(?array $filter);

    /**
     * @param int|null $value
     *
     * @return \vivace\db\Finder
     */
    public function limit(?int $value);

    /**
     * @param int|null $value
     *
     * @return \vivace\db\Finder
     */
    public function skip(?int $value);

    /**
     * @param array|null $sort An array where the key is the name of the attribute, and the value is one of two options.
     * For sorting in descending order use -1 and for sorting in ascending order use 1.
     * Pass null for reset.
     *
     * @return \vivace\db\Finder
     *
     * @example sort(['id' => -1, 'age' => 1])
     */
    public function sort(?array $sort);

    /**
     * @param array $map Assoc array
     *
     * @return \vivace\db\Finder
     */
    public function projection(?array $map);

    /**
     * @return \vivace\db\Finder
     */
    public function find();

    /**
     * @return \vivace\db\Reader
     */
    public function fetch();

    /**
     * Update all entities
     * @param array $data
     * @return int
     */
    public function update(array $data): int;

    /**
     * Update/Insert data to storage
     * @param array $data Assoc or indexed array.
     * @uses
     * @return bool
     */
    public function save(array $data): bool;

    public function delete(): int;

    /**
     * Total number of entities
     * @return int
     */
    public function count(): int;

}