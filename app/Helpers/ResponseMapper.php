<?php

/* @A simple wrapper for array_map in collection
 * @Author <arnacmj@gmail.com>
 * */

namespace App\Helpers;


class ResponseMapper
{
    protected $items;

    public function __construct($items = []) {
        $this->items = $this->arrayAble($items);
    }

    public function mapper(callable $callback) {
        $key = array_keys($this->items);
        $items = array_map($callback, $this->items, $key);
        return new self(array_combine($key, $items));
    }

    public function arrayAble($items) {
        if(is_array($items))
            return $items;
        return $items->toArray();
    }
}