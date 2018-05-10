<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 23.04.2018
 * Time: 21:33
 */

namespace vivace\db;


interface Relation
{
    function populate(Data $data, string $field);
}