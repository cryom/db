<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 14.02.2018
 * Time: 12:48
 */

namespace vivace\db\sql;

use vivace\db\Property;
use vivace\db\Relation;
use vivace\db\Reader;
use vivace\db\mixin;
use vivace\db\Schema;
use vivace\db\sql\statement\Select;

class Finder implements \vivace\db\Finder
{
    use mixin\Filter;
    protected $limit;
    protected $skip;
    protected $sort = [];
    protected $projection = [];
    protected $typecast = false;
    /** @var Storage */
    protected $storage;


    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
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

    public function typecast(bool $enable = true)
    {
        $o = clone $this;
        $o->typecast = $enable;
        return $o;
    }

    public function projection(array $map)
    {
        $o = clone $this;
        $o->projection = array_merge($this->projection, $map);
        return $o;
    }

    /**
     * @param \vivace\db\Schema $schema
     * @param array $filter
     *
     * @return array
     * @throws \Exception
     */
    protected function normalizeFilter(Schema $schema, array $filter): array
    {
        if (!$filter) {
            return $filter;
        }

        if (!isset($filter[0])) {
            $normalized = [];
            foreach ($filter as $key => $value) {
                $property = $schema->get($key);
                $key = $property->getName();
                $normalized[$key] = $this->storage()->driver()->typecastIn($property, $value);
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
                $property = $schema->get($filter[1]);
                $filter[1] = $property->getName();
                $filter[2] = $this->storage()->driver()->typecastIn($property, $filter[2]);
                break;
            case 'and':
            case 'or':
                $cnt = count($filter);
                for ($i = 1; $i < $cnt; $i++) {
                    $filter[$i] = $this->normalizeFilter($schema, $filter[$i]);
                }
                break;
            case 'in':
                $property = $schema->get($filter[1]);
                $filter[1] = $property->getName();
                foreach ($filter[2] as &$value) {
                    $value = $this->storage()->driver()->typecastIn($property, $value);
                }
                break;
            case 'between':
                $property = $schema->get($filter[1]);
                $filter[1] = $property->getName();
                $filter[2] = $this->storage()->driver()->typecastIn($property, $filter[2]);
                $filter[3] = $this->storage()->driver()->typecastIn($property, $filter[3]);
                break;
        }

        return $filter;
    }

    /**
     * @param \vivace\db\Schema $schema
     * @param array $sort
     *
     * @return array
     */
    protected function normalizeSort(Schema $schema, array $sort): array
    {
        $new = [];
        foreach ($sort as $key => $value) {
            $key = $schema->get($key)->getName();
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
        $schema = $this->storage()->schema();
        $query = new Select($schema->getName());
        $aliases = [];
        $relations = [];
        foreach ($this->projection as $name => $value) {
            if (is_string($value)) {
                $aliases[$name] = $value;
            } elseif ($value instanceof Relation) {
                $relations[$name] = $value;
            }
        }

        if ($aliases) {
            $schema = clone  $schema;
            foreach ($aliases as $alias => $name) {
                $query->projection[] = '*';
                $query->projection[$name] = $alias;
                $schema->set($alias, $name);
            }
        }

        if ($this->filter) {
            $query->where = $this->normalizeFilter($schema, $this->filter);
        }
        if ($this->sort) {
            $query->order = $this->normalizeSort($schema, $this->sort);
        }

        $query->limit = $this->limit;
        $query->offset = $this->skip;

        $reader = $this->storage()->driver()->read($query);

        if ($relations) {
            $reader = new Binder($reader, 500, $relations);
        }

        $reader = new \vivace\db\sql\Reader($reader, $schema, $this->storage()->driver());


        return $reader;
    }

}