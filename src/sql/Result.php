<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 14.05.2018
 * Time: 0:47
 */

namespace vivace\db\sql;


interface Result
{
    public function getAffected(): ?int;

    public function getInsertedId(): ?int;
}