<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 28.04.2018
 * Time: 2:06
 */

namespace vivace\db\sql;


use Traversable;
use vivace\db\Relation;
use vivace\db\Schema;

class Reader implements \vivace\db\Reader
{
    const BUFFER_LENGTH = 100;
    /**
     * @var \vivace\db\sql\Fetcher
     */
    protected $fetcher;
    /**
     * @var \vivace\db\sql\Storage
     */
    protected $storage;
    /**
     * @var Driver
     */
    protected $driver;
    /**
     * @var array
     */
    protected $projection;
    /** @var \vivace\db\Relation[] */
    protected $relation = [];

    /**
     * Caster constructor.
     *
     * @param \vivace\db\sql\Fetcher $fetcher
     * @param \vivace\db\sql\Storage $storage
     * @param array $projection
     */
    public function __construct(Fetcher $fetcher, Storage $storage, array $projection)
    {
        $this->fetcher = $fetcher;
        $this->storage = $storage;
        $this->projection = $projection;
        foreach ($projection as $key => $value) {
            if ($value instanceof Relation) {
                $this->relation[$key] = $value;
            }
        }
    }

    /**
     * @return \Generator|\Traversable
     * @throws \Exception
     */
    public function getIterator()
    {
        if ($this->relation) {
            foreach ($this->chunks() as $data) {
                $data = $this->storage->collection($data);
                foreach ($this->relation as $field => $relation) {
                    $relation->populate($data, $field);
                }
                yield from $data;
            }
        } else {
            foreach ($this->fetcher as $item) {
                $item = $this->normalize($item);
                yield $this->storage->entity()->restore($this->projection, $item);
            }
        }

    }

    protected function chunks()
    {
        $items = [];
        $i = 0;
        foreach ($this->fetcher as $item) {
            $items[] = $item;
            if (++$i % self::BUFFER_LENGTH === 0) {
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
     * @throws \Exception
     */
    public function one(): ?\vivace\db\Entity
    {
        $item = $this->fetcher->one();
        $item = $this->normalize($item);

        $item = $this->storage->entity()->restore($this->projection, $item);
        foreach ($this->relation as $field => $relation) {
            $relation->populate($item, $field);
        }
        return $item;
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    protected function normalize(array $data): array
    {
        $schema = $this->storage->schema();
        foreach ($data as $key => &$value) {
            if (is_string($this->projection[$key])) {
                $key = $this->projection[$key];
            }
            $value = $this->storage->driver()->typecastOut($schema->get($key), $value);
        }

        return $data;
    }

    public function all(): \vivace\db\Collection
    {
        $data = iterator_to_array($this);
        $data = $this->storage->collection($data);
        foreach ($this->relation as $field => $relation) {
            $relation->populate($data, $field);
        }
        return $data;
    }

    public function count(): int
    {
        return $this->fetcher->count();
    }
}