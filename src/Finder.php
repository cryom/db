<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 14.02.2018
 * Time: 12:41
 */

namespace vivace\db;


/**
 * Interface Finder
 *
 * @package vivace\db
 */
interface Finder extends Filtrable
{


    /**
     * @param int|null $value
     *
     * @return $this|static
     */
    public function limit(?int $value);

    /**
     * @param int|null $value
     *
     * @return $this|static
     */
    public function skip(?int $value);

    /**
     * @param array $map
     *
     * @return $this|static
     */
    public function sort(array $map);

    /**
     * @param array $map
     *
     * @return $this|static
     */
    public function projection(array $map);

    /**
     * @return \vivace\db\Reader
     */
    public function fetch();

    public function update(array $data): int;

}
