<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 28.04.2018
 * Time: 2:06
 */

namespace vivace\db\sql;


use Traversable;
use vivace\db\Reader;

class Caster implements \vivace\db\Reader
{
    /**
     * @var \Traversable
     */
    protected $iterator;
    /**
     * @var array
     */
    protected $schema;

    /**
     * Caster constructor.
     *
     * @param \Traversable $iterator
     * @param array $schema
     */
    public function __construct(Traversable $iterator, array $schema)
    {
        $this->iterator = $iterator;
        $this->schema = $schema;
    }

    /**
     * @return \Generator|\Traversable
     */
    public function getIterator()
    {
        foreach ($this->iterator as $item) {
            foreach ($item as $name => &$value) {
                if (!isset($this->schema[$name]) || ($value === null && $this->schema[$name]['nullable'])) {
                    continue;
                }
                switch ($this->schema[$name]['type']) {
                    case 'int':
                    case 'float':
                        settype($value, $this->schema[$name]['type']);
                        break;
                    case \DateTime::class:
                        $value = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
                        break;
                }
            }

            yield $item;
        }
    }

    /**
     * @return array|null
     */
    public function one(): ?array
    {
        foreach ($this->getIterator() as $item) {
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

    public function chunk(int $size): Reader
    {
        return new Chunker($this, $size);
    }
}