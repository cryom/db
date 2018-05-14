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

class Single implements Filtrable, Relation
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

    function populate(array &$data, string $field)
    {
        $finder = $this->storage->find();
        if ($this->filter) {
            $finder = $finder->filter($this->filter);
        }

        if ($this->projection) {
            $finder = $finder->projection($this->projection);
        }

        $isMultiple = isset($data[0]);

        if ($isMultiple && count($data) == 1) {
            $isMultiple = false;
            $w = &$data[0];
        } else {
            $w = &$data;
        }

        if (!$isMultiple) {
            $column = [];
            $values = [];
            foreach ($this->key as $internal => $external) {
                $column[] = $external;
                $values[0][] = $w[$internal];
            }
            if ($found = $finder->and(['in', $column, $values])->fetch()->one()) {
                $w[$field] = $found;
            }
        } else {
            $map = [];
            $column = array_values($this->key);
            $values = [];

            $i = 0;
            foreach ($w as &$item) {
                $cursor = &$map;
                foreach ($this->key as $internal => $external) {
                    if (!isset($cursor[$item[$internal]])) {
                        $cursor[$item[$internal]] = [];
                    }
                    $cursor = &$map[$item[$internal]];

                    $values[$i][] = $item[$internal];
                }
                $i++;
                $cursor[] = &$item;
            }

            $founds = $finder->and(['in', $column, $values])->fetch();

            foreach ($founds as $found) {
                $cursor = &$map;
                foreach ($this->key as $internal => $external) {
                    if (isset($cursor[$found[$external]])) {
                        $cursor = &$cursor[$found[$external]];
                    } else {
                        continue(2);
                    }
                }
                foreach ($cursor as &$item) {
                    $item[$field] = $found;
                }
            }
        }

        unset($cursor, $founds, $w, $map);
    }
}