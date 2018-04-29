<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 23.04.2018
 * Time: 12:32
 */

namespace vivace\db;


interface Filtrable
{
    /**
     * @param null|array $condition
     *
     * @return static|$this
     * @example filter(['id' => 1])
     * @example filter(['id' => [1,2,3,4]])
     * @example filter(['id' => 1, 'age' => 2])
     * @example filter(['>=', 'id', 1])
     * @example filter(['>', 'id', 1])
     * @example filter(['=', 'id', 1])
     * @example filter(['<', 'id', 1])
     * @example filter(['<=', 'id', 1])
     * @example filter(['!=', 'id', 1])
     * @example filter(['in', 'id', [1,2,3]])
     * @example filter(['between', 'id', 1, 2])
     * @example filter(['and', ['!=', 'id', 1], ['>', 'id', 2], ['or', ['id'=> 2], ['<=', 'id', 1]] ])
     */
    public function filter(?array $condition);

    /**
     * @param array $condition
     *
     * @see \vivace\db\Finder::filter()
     * @return static|$this
     */
    public function and (array $condition);

    /**
     * @param array $condition
     *
     * @see \vivace\db\Finder::filter()
     * @return static|$this
     */
    public function or (array $condition);
}