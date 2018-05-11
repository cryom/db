<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 10.05.2018
 * Time: 18:10
 */

namespace vivace\db\sql;


interface Field
{
    const TYPE_INT = 'int';
    const TYPE_STRING = 'string';
    const TYPE_BOOL = 'bool';
    const TYPE_FLOAT = 'float';
    const TYPE_TIMESTAMP = 'timestamp';

    public function isPrimary(): bool;

    public function isUnique(): bool;

    public function getName(): string;

    public function getType(): string;
}