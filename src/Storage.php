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
     * @param array $map
     *
     * @return \vivace\db\Finder
     * @example projection(['user_id' => 'id', 'is_active' => 'isActive'])
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
     * @param array $key Assoc array, when key is foreign key and value is field from this storage
     *
     * @return \vivace\db\Relation\Single
     */
    public function single(array $key);

    /**
     * @param array $key Assoc array, when key is foreign key and value is field from this storage
     *
     * @return \vivace\db\Relation\Many
     */
    public function many(array $key);

    public function schema(): Schema;

}