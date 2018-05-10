<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 18.04.2018
 * Time: 23:21
 */

namespace vivace\db\Relation;


use vivace\db\Collection;
use vivace\db\Data;
use vivace\db\Entity;
use vivace\db\Relation;
use vivace\db\Filtrable;
use vivace\db\mixin;
use vivace\db\Storage;

abstract class Single implements Filtrable, Relation
{
    use mixin\Filter;
    use mixin\Projection;
    /**
     * @var \vivace\db\Storage
     */
    protected $storage;
    /**
     * @var array
     */
    protected $key;

    /**
     * Single constructor.
     *
     * @param \vivace\db\Storage $storage
     * @param array $key Assoc array, when key is foreign key and value is field from this storage
     */
    public function __construct(Storage $storage, array $key)
    {
        $this->storage = $storage;
        $this->key = $key;
    }

    function populate(Data $data, string $field)
    {
        $simpleKey = count($this->key) == 1;
        $finder = $this->storage->find();
        if ($this->filter) {
            $finder = $finder->filter($this->filter);
        }

        if ($this->projection) {
            $finder = $finder->projection($this->projection);
        }

        if ($data instanceof Entity) {
            if ($simpleKey) {
                $internal = key($this->key);
                $external = current($this->key);
                $filter = [$external => $data[$internal]];
            } else {
                $filter = [];
                foreach ($this->key as $internal => $external) {
                    $filter[$external] = $data[$internal];
                }
            }
            $data[$field] = $finder->and($filter)->fetch()->one();

        } elseif ($data instanceof Collection) {
            $map = [];

            if ($simpleKey) {
                $internal = key($this->key);
                $external = current($this->key);
                $filter = [];
                foreach ($data as $item) {
                    $filter[] = $item[$internal];
                    $item[$field] = null;
                    $map[$item[$internal]][] = $item;
                }
                $filter = ['in', $external, array_unique($filter)];

                $founds = $finder->and($filter)->fetch();

                foreach ($founds as $found) {
                    $idx = $found[$external];
                    if (isset($map[$idx])) {
                        foreach ($map[$idx] as &$item) {
                            if (!isset($item[$field]))
                                $item[$field] = $found;
                        }
                    }
                }

            } else {
                $filter = ['or'];
                $i = 0;
                foreach ($data as $item) {
                    $idx = '';
                    foreach ($this->key as $internal => $external) {
                        $idx .= $item[$internal] . "\t";
                        $filter[++$i][$external] = $item[$internal];
                    }
                    $map[$idx][] = $item;
                }

                $founds = $finder->and($filter)->fetch();

                foreach ($founds as $found) {
                    $idx = '';
                    foreach ($this->key as $internal => $external) {
                        $idx .= $found[$external] . "\t";
                    }
                    if (isset($map[$idx])) {
                        foreach ($map[$idx] as &$item) {
                            if (!isset($item[$field]))
                                $item[$field] = $found;
                        }
                    }
                }

            }
        }
    }
}