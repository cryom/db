<?php
/**
 * Created by PhpStorm.
 * User: macbookpro
 * Date: 02.05.2018
 * Time: 2:01
 */

namespace vivace\db\mixin;


trait Projection
{
    /** @var array */
    protected $projection;

    /**
     * @param array|null $projection
     *
     * @return static|$this Clone of this instance
     */
    public function projection(?array $projection)
    {
        $o = clone $this;
        if ($projection === null || !$o->projection) {
            $o->projection = $projection;
        } else {
            $o->projection = array_replace($o->projection, $projection);
        }

        return $o;
    }
}