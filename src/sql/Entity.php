<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 05.05.2018
 * Time: 0:41
 */

namespace vivace\db\sql;


use vivace\db\sql\Statement\Insert;
use vivace\db\sql\Statement\Update;

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
        $offset = $this->realName($offset);
        return isset($this->data[$offset]) || isset($this->stored[$offset]);
    }

    protected function realName(string $name)
    {
        if (isset($this->projection[$name]) && is_string($this->projection[$name])) {
            return $this->projection[$name];
        }
        return $name;
    }

    /** @inheritdoc */
    public function offsetGet($offset)
    {
        $offset = $this->realName($offset);
        return $this->data[$offset] ?? $this->stored[$offset];
    }

    /** @inheritdoc */
    public function offsetSet($offset, $value)
    {
        $offset = $this->realName($offset);
        $this->data[$offset] = $value;
    }

    /** @inheritdoc */
    public function offsetUnset($offset)
    {
        $offset = $this->realName($offset);
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

    /**
     * @throws \Exception
     */
    public function save(): bool
    {
        $schema = $this->storage->schema();
        if (!$this->stored) {
            $data = [];
            foreach ($this->data as $name => $value) {
                $data[$name] = $this->storage->driver()->typecastIn($schema->get($name), $value);
            }
            if (!$data) {
                return false;
            }
            $query = new Insert($this->storage->getSource(), array_keys($data), [array_values($data)]);
        } else {
            $data = array_diff_assoc($this->data, $this->stored);
            if (!$data) {
                return false;
            }
            $query = new Update($this->storage->getSource(), $data);
            $pk = $schema->getPrimary() ?? $schema->getUnique();
            if ($pk) {
                foreach ($pk as $field) {
                    $name = $field->getName();
                    $query->where[$name] = $this->data[$name] ?? $this->stored[$name];
                }
            } else {
                foreach ($schema->getNames() as $name) {
                    $name = $schema->get($name)->getName();
                    $query->where[$name] = $this->data[$name] ?? $this->stored[$name];
                }
            }
            $query->limit = 1;
        }

        return (bool)$this->storage->driver()->execute($query);
    }
}