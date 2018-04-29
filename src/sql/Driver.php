<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 14.02.2018
 * Time: 14:18
 */

namespace vivace\db\sql;


interface Driver
{
    /**
     * @param \vivace\db\sql\expression\Statement $statement
     * @param array $params
     *
     * @return array [string $query, array $params]
     */
    public function build(expression\Statement $statement, array $params = []): array;

    /**
     * @param \vivace\db\sql\expression\Read $query
     *
     * @return \vivace\db\sql\Fetcher|array[]
     */
    public function read(expression\Read $query): \vivace\db\Reader;

    public function execute(expression\Modifier $query): int;

}

