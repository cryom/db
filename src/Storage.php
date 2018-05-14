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
    public function filter(array $filter);

    /**
     * @param int|null $value
     *
     * @return \vivace\db\Finder
     */
    public function limit(int $value);

    /**
     * @param int|null $value
     *
     * @return \vivace\db\Finder
     */
    public function skip(int $value);

    /**
     * @param array $sort
     *
     * @return \vivace\db\Finder
     */
    public function sort(array $sort);

    /**
     * @param array $map Assoc array
     *
     * @return \vivace\db\Finder
     * @example $finder->projection(['user_id' => 'id', 'is_active' => false])
     */
    public function projection(array $map);

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
     *
     * @param array $data
     *
     * @return int
     * @example $storage->update(['type' => 'some', 'parent_id' => 9]);
     */
    public function update(array $data): int;

    public function save(array $data): bool;

    public function delete(): int;

    /**
     * Total number of entities
     *
     * @return int
     */
    public function count(): int;

}