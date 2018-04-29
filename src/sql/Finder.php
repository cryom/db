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
use vivace\db\sql\expression\Select;

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

    public function fetch(): Reader
    {
        $tableName = $this->storage->getSourceName();

        $query = new Select($tableName);
        $query->where = $this->filter;
        $query->order = $this->sort;
        $query->limit = $this->limit;
        $query->offset = $this->skip;


        $reader = $this->storage->driver()->read($query);
        if ($this->typecast) {
            $reader = new Caster($reader, $this->storage->schema());
        }

        $relations = array_filter($this->projection, function ($value) {
            return $value instanceof Relation;
        });

        if ($relations) {
            $reader = new Binder($reader, 500, $relations);
        }

        return $reader;
    }


}