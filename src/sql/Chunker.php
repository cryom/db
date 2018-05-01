<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 27.04.2018
 * Time: 13:15
 */

namespace vivace\db\sql;


use Traversable;
use vivace\db\Reader;

class Chunker implements \vivace\db\Reader
{
    /**
     * @var \Traversable
     */
    protected $iterator;
    /**
     * @var int
     */
    protected $size;

    /**
     * ChunkFetcher constructor.
     *
     * @param \Traversable $reader
     * @param int $size
     */
    public function __construct(Traversable $reader, int $size)
    {
        $this->iterator = $reader;
        $this->size = $size;
    }

    /**
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        foreach ($this->chunks() as $chunk) {
            yield from $chunk;
        }
    }

    protected function chunks()
    {
        $items = [];
        $i = 0;
        foreach ($this->iterator as $item) {
            $items[] = $item;
            if (++$i % $this->size === 0) {
                yield $items;
                $items = [];
            }
        }
        if ($items) {
            yield $items;
        }
    }

    /**
     * @return array|null
     */
    public function one(): ?array
    {
        if ($this->iterator instanceof Reader) {
            return $this->iterator->one();
        }
        foreach ($this->iterator as $item) {
            return $item;
        }
    }

    public function chunk(int $size): Reader
    {
        $o = clone $this;
        $o->size = $size;
        return $o;
    }

    public function all(): array
    {
        if ($this->iterator instanceof Reader) {
            return $this->iterator->all();
        }
        return iterator_to_array($this->iterator);
    }

    public function count(): int
    {
        if ($this->iterator instanceof \Countable) {
            return $this->iterator->count();
        }
        return iterator_count($this->iterator);
    }
}