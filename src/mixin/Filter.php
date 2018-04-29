<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 23.04.2018
 * Time: 21:42
 */

namespace vivace\db\mixin;


trait Filter
{
    protected $filter;

    /**
     * @param null|array $condition
     *
     * @return $this
     */
    public function filter(?array $condition)
    {
        $o = clone $this;
        $o->filter = $condition;
        return $o;
    }

    /**
     * @param array $condition
     *
     * @return $this|static
     */
    public function and (array $condition)
    {
        $o = clone $this;
        if ($this->filter) {
            $o->filter = ['and', $this->filter, $condition];
        } else {
            $o->filter = $condition;
        }
        return $o;
    }

    /**
     * @param array $condition
     *
     * @return $this|static
     */
    public function or (array $condition)
    {
        $o = clone $this;
        if ($this->filter) {
            $o->filter = ['or', $this->filter, $condition];
        } else {
            $o->filter = $condition;
        }

        return $o;
    }
}