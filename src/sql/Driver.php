<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 14.02.2018
 * Time: 14:18
 */

namespace vivace\db\sql;


use vivace\db\Property;

abstract class Driver
{
    protected static $schemas = [];

    /**
     * @param \vivace\db\sql\statement\Statement|array $statement
     *
     * @return array [string $query, array $params]
     */
    abstract public function build($statement): array;

    /**
     * @param \vivace\db\sql\statement\Read $query
     *
     * @return \vivace\db\Reader
     */
    abstract public function read(statement\Read $query): \vivace\db\Reader;

    abstract public function execute(statement\Modifier $query): int;

    /**
     * @param \vivace\db\Property $property
     * @param $value
     *
     * @return mixed
     */
    abstract function typecastIn(Property $property, $value);
    abstract function typecastOut(Property $property, $value);

}

