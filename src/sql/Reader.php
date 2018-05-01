<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 28.04.2018
 * Time: 2:06
 */

namespace vivace\db\sql;


use Traversable;
use vivace\db\Schema;

class Reader implements \vivace\db\Reader
{
    /**
     * @var \Traversable
     */
    protected $iterator;
    /**
     * @var \vivace\db\Schema
     */
    protected $schema;
    /**
     * @var \vivace\db\sql\Driver
     */
    protected $driver;


    /**
     * Caster constructor.
     *
     * @param \Traversable $iterator
     * @param \vivace\db\Schema $schema
     * @param \vivace\db\sql\Driver $driver
     */
    public function __construct(Traversable $iterator, Schema $schema, Driver $driver)
    {
        $this->iterator = $iterator;
        $this->schema = $schema;
        $this->driver = $driver;
    }

    /**
     * @return \Generator|\Traversable
     */
    public function getIterator()
    {
        foreach ($this->iterator as $item) {
            yield $this->cast($item);
        }
    }

    protected function cast(array $item)
    {
        foreach ($item as $name => &$value) {
            if (!$this->schema->has($name) || ($value === null && $this->schema->get($name)->isNullable())) {
                continue;
            }
            $value = $this->driver->typecastOut($this->schema->get($name), $value);
        }

        return $item;
    }

    /**
     * @return array|null
     */
    public function one(): ?array
    {
        if ($this->iterator instanceof \vivace\db\Reader) {
            return $this->cast($this->iterator->one());
        }
        foreach ($this->iterator as $item) {
            return $item;
        }
    }

    public function all(): array
    {
        return iterator_to_array($this);
    }

    public function count(): int
    {
        return iterator_count($this->iterator);
    }

    public function chunk(int $size): \vivace\db\Reader
    {
        return new Chunker($this, $size);
    }
}