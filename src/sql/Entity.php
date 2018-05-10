<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 05.05.2018
 * Time: 0:41
 */

namespace vivace\db\sql;


class Entity implements \vivace\db\Entity
{
    protected $data = [];
    /**
     * @var \vivace\db\sql\Storage
     */
    protected $storage;
    /**
     * @var array
     */
    protected $projection;
    private $stored = [];

    /**
     * Entity constructor.
     *
     * @param \vivace\db\sql\Storage $storage
     * @param array $projection
     * @param array $data
     */
    public function __construct(Storage $storage, array $projection, array $data = [])
    {
        $this->storage = $storage;
        $this->data = $data;
        $this->projection = $projection;
    }

    /** @inheritdoc */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]) || isset($this->stored[$offset]);
    }

    /** @inheritdoc */
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? $this->stored[$offset];
    }

    /** @inheritdoc */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /** @inheritdoc */
    public function offsetUnset($offset)
    {
        $this->data[$offset] = null;
    }

    /** @inheritdoc */
    public function isStored(): bool
    {
        return !empty($this->stored) && empty($this->data);
    }

    public function restore(array $projection, array $data): \vivace\db\Entity
    {
        $o = clone $this;
        $o->projection = $projection;
        $o->data = [];
        $o->stored = $data;
        return $o;
    }

    public function projection(array $projection): \vivace\db\Entity
    {
        $o = clone $this;
        $o->projection = $projection;
        return $o;
    }
}