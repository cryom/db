<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 28.04.2018
 * Time: 1:17
 */

namespace vivace\db\sql;


class Binder extends Chunker
{
    /**
     * @var \vivace\db\Relation[]
     */
    protected $relations;

    public function __construct(\Traversable $reader, int $size, array $relations)
    {
        parent::__construct($reader, $size);
        $this->relations = $relations;
    }

    public function getIterator()
    {
        foreach ($this->chunks() as $chunk) {
            foreach ($this->relations as $field => $relation) {
                $chunk = $relation->populate($chunk, $field);
            }
            yield from $chunk;
        }
    }

    public function one(): ?array
    {
        $item = parent::one();
        $result = [];
        foreach ($this->relations as $field => $relation) {
            $result[] = $relation->populate([$item], $field)[0];
        }

        return count($result) > 1 ? array_merge(...$result) : $result[0];
    }

    public function all(): array
    {
        $items = parent::all();
        $result = [];
        foreach ($this->relations as $field => $relation) {
            $result[] = $relation->populate($items, $field);
        }

        return count($result) > 1 ? array_replace_recursive(...$result) : $result[0];
    }


}