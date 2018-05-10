<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 05.05.2018
 * Time: 0:53
 */

namespace vivace\db\sql;


class Collection implements \vivace\db\Collection, \ArrayAccess
{
    /**
     * @var \vivace\db\sql\Storage
     */
    protected $storage;
    /**
     * @var \vivace\db\sql\Entity[]
     */
    protected $entities;


    public function __construct(Storage $storage, array $entities = [])
    {
        $this->storage = $storage;
        $this->entities = $entities;
    }


    public function isStored(): bool
    {
        foreach ($this->entities as $item) {
            if (!$item->isStored()) {
                return false;
            }
        }
        return true;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->entities);
    }

    /** @inheritdoc */
    public function offsetExists($offset)
    {
        return isset($this->entities[$offset]);
    }

    /** @inheritdoc */
    public function offsetGet($offset)
    {
        return $this->entities[$offset];
    }

    /** @inheritdoc */
    public function offsetSet($offset, $value)
    {
        $this->entities[$offset] = $value;
    }

    /** @inheritdoc */
    public function offsetUnset($offset)
    {
        unset($this->entities[$offset]);
    }
}