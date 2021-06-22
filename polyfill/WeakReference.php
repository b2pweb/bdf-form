<?php

if (!class_exists(WeakReference::class)) {
    /**
     * Pseudo polyfill for get the WeakReference API on PHP < 7.4
     *
     * https://www.php.net/manual/en/class.weakreference.php
     *
     * @template T as object
     */
    class WeakReference
    {
        /**
         * @var T
         */
        private $obj;

        /**
         * WeakReference constructor.
         *
         * @param T $obj
         */
        private function __construct($obj)
        {
            $this->obj = $obj;
        }

        /**
         * Get a weakly referenced Object
         *
         * @return T|null
         */
        public function get()
        {
            return $this->obj;
        }

        /**
         * Create a new weak reference
         *
         * @param T $obj
         *
         * @return WeakReference
         */
        public static function create($obj): WeakReference
        {
            return new self($obj);
        }
    }
}
