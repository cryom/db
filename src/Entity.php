<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 04.05.2018
 * Time: 23:50
 */

namespace vivace\db;


interface Entity extends \ArrayAccess, Data
{
    public function projection(array $projection): Entity;

    public function restore(array $projection, array $data): Entity;
}