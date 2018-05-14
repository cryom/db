<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 14.02.2018
 * Time: 12:48
 */

namespace vivace\db\sql;

use vivace\db\Relation;
use vivace\db\Reader;
use vivace\db\mixin;
use vivace\db\sql\Statement\Count;
use vivace\db\sql\Statement\Delete;
use vivace\db\sql\Statement\Select;
use vivace\db\sql\Statement\Update;

class Finder implements \vivace\db\Finder
{
    use mixin\Filter;
    use mixin\Projection;

    protected $limit;
    protected $skip;
    protected $sort = [];
    /** @var Storage */
    protected $storage;


    public function __construct(Storage $storage, array $projection = [])
    {
        $this->storage = $storage;
        $this->projection = $projection;
    }

    /**
     * @param int|null $value
     *
     * @return Finder
     */
    public function limit(?int $value)
    {
        $o = clone $this;
        $o->limit = $value;
        return $o;
    }

    public function storage(): Storage
    {
        return $this->storage;
    }

    /**
     * @param int|null $value
     *
     * @return Finder
     */
    public function skip(?int $value)
    {
        $o = clone $this;
        $o->skip = $value;
        return $o;
    }

    public function sort(?array $map)
    {
        $o = clone $this;
        if ($map === null) {
            $o->sort = [];
        } elseif ($map) {
            $o->sort = array_merge($this->sort, $map);
        }

        return $o;
    }

    /**
     * @param array $projection
     * @param array $filter
     *
     * @return array
     * @throws \Exception
     */
    protected function normalizeFilter(?array $projection, array $filter): array
    {
        if (!$filter) {
            return $filter;
        }

        $schema = $this->storage()->schema();

        if (!isset($filter[0])) {
            $normalized = [];
            foreach ($filter as $key => $value) {
                $key = $projection && is_string($projection[$key]) ? $projection[$key] : $key;
                $normalized[$key] = $this->storage()->driver()->typecastIn($schema->get($key), $value);
            }
            return $normalized;
        }

        switch ($filter[0]) {
            case '=':
            case '>':
            case '<':
            case '>=':
            case '<=':
            case '!=':
                if ($projection && is_string($projection[$filter[1]])) {
                    $filter[1] = $projection[$filter[1]];
                }
                $property = $schema->get($filter[1]);
                $filter[2] = $this->storage()->driver()->typecastIn($property, $filter[2]);
                break;
            case 'and':
            case 'or':
                $cnt = count($filter);
                for ($i = 1; $i < $cnt; $i++) {
                    $filter[$i] = $this->normalizeFilter($projection, $filter[$i]);
                }
                break;
            case 'in':
                if (is_array($filter[1])) {
                    foreach ($filter[1] as $i => &$column) {

                        if ($projection && is_string($projection[$column])) {
                            $column = $projection[$column];
                        }
                        $property = $schema->get($column);

                        foreach ($filter[2] as &$values) {
                            $values[$i] = $this->storage()->driver()->typecastIn($property, $values[$i]);
                        }
                    }
                } else {
                    if ($projection && is_string($projection[$filter[1]])) {
                        $filter[1] = $projection[$filter[1]];
                    }
                    $property = $schema->get($filter[1]);
                    foreach ($filter[2] as &$value) {
                        $value = $this->storage()->driver()->typecastIn($property, $value);
                    }
                }

                break;
            case 'between':
                if ($projection && is_string($projection[$filter[1]])) {
                    $filter[1] = $projection[$filter[1]];
                }
                $property = $schema->get($filter[1]);
                $filter[2] = $this->storage()->driver()->typecastIn($property, $filter[2]);
                $filter[3] = $this->storage()->driver()->typecastIn($property, $filter[3]);
                break;
        }

        return $filter;
    }

    /**
     * @param array $projection
     * @param array $sort
     *
     * @return array
     */
    protected function normalizeSort(?array $projection, array $sort): array
    {
        $new = [];
        foreach ($sort as $key => $value) {
            if ($projection && is_string($projection[$key])) {
                $key = $projection[$key];
            }
            $new[$key] = $value;
        }

        return $new;
    }

    /**
     * @return \vivace\db\Reader
     * @throws \Exception
     */
    public function fetch(): Reader
    {
        $query = new Select($this->storage()->getSource());

        if ($this->filter) {
            $query->where = $this->normalizeFilter($this->projection, $this->filter);
        }
        if ($this->sort) {
            $query->order = $this->normalizeSort($this->projection, $this->sort);
        }

        $query->limit = $this->limit;
        $query->offset = $this->skip;
        if ($this->projection)
            foreach ($this->projection as $key => $value) {
                if (is_bool($value) && $value) {
                    $query->projection[] = $key;
                } elseif (is_string($value)) {
                    $query->projection[$value] = $key;
                }
            }

        $reader = $this->storage()->driver()->fetch($query);
        $reader = new \vivace\db\sql\Reader($reader, $this->storage(), $this->projection);

        return $reader;
    }

    /**
     * @param array $data
     *
     * @return int
     * @throws \Exception
     */
    public function update(array $data): int
    {
        $schema = $this->storage()->schema();
        $query = new Update($this->storage()->getSource(), $data);

        if ($this->projection) {
            $copyData = [];
            foreach ($query->set as $name => $value) {
                if (isset($this->projection[$name]) && is_string($this->projection[$name])) {
                    $name = $this->projection[$name];
                }
                $field = $schema->get($name);
                $copyData[$name] = $this->storage()->driver()->typecastIn($field, $value);
            }
            $query->set = $copyData;

            if ($this->filter) {
                $query->where = $this->normalizeFilter($this->projection, $this->filter);
            }
            if ($this->sort) {
                $query->order = $this->normalizeSort($this->projection, $this->sort);
            }
        }

        $query->limit = $this->limit;
        $query->offset = $this->skip;

        return (int)$this->storage()->driver()->execute($query)->getAffected();
    }

    /**
     * Delete found rows
     *
     * @return int Number deleted rows
     * @see \vivace\db\Storage::delete()
     * @throws \Exception
     */
    public function delete(): int
    {
        $query = new Delete($this->storage()->getSource());
        $query->limit = $this->limit;
        $query->offset = $this->skip;
        if ($this->filter) {
            $query->where = $this->normalizeFilter($this->projection, $this->filter);
        }
        if ($this->sort) {
            $query->order = $this->normalizeSort($this->projection, $this->sort);
        }

        return (int)$this->storage()->driver()->execute($query)->getAffected();
    }

    /**
     * Count found entities
     *
     * @return int
     * @see \vivace\db\Storage::count()
     * @throws \Exception
     */
    public function count(): int
    {
        $query = new Count($this->storage()->getSource());

        if ($this->filter) {
            $query->where = $this->normalizeFilter($this->projection, $this->filter);
        }
        if ($this->sort) {
            $query->order = $this->normalizeSort($this->projection, $this->sort);
        }

        $query->limit = $this->limit;
        $query->offset = $this->skip;

        return (int)$this->storage()->driver()->fetch($query)->scalar();
    }
}