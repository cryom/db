<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 01.05.2018
 * Time: 21:26
 */

namespace vivace\db;


class Schema
{
    protected $properties = [];
    /** @var string */
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @param \vivace\db\Property|string $property
     */
    public function set(string $name, $property)
    {
        $this->properties[$name] = $property;
    }

    public function get(string $name): Property
    {
        $property = $this->properties[$name];
        return is_string($property) ? $this->get($property) : $property;
    }

    public function has(string $name): bool
    {
        return isset($this->properties[$name]);
    }
}