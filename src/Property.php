<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 01.05.2018
 * Time: 21:26
 */

namespace vivace\db;


class Property
{
    const TYPE_STRING = 'string';
    const TYPE_INT = 'int';
    const TYPE_BOOL = 'boolean';
    const TYPE_FLOAT = 'float';
    const TYPE_DATETIME = \DateTime::class;

    protected $name;
    protected $isNullable;
    protected $default;
    protected $type;
    protected $meta = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * @param mixed $nullable
     */
    public function setNullable(bool $nullable): void
    {
        $this->isNullable = $nullable;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param mixed $default
     */
    public function setDefault($default): void
    {
        $this->default = $default;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }


}