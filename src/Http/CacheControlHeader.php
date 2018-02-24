<?php


namespace RandomState\LaravelDoctrineFirebase\Http;


class CacheControlHeader
{

    /**
     * @var string
     */
    protected $header;

    public function __construct($header)
    {
        $this->header = $header;
    }

    public function getMaxAge()
    {
        $matches = [];

        return preg_match('/max-age=(\d*)/', $this->header,$matches) ? $matches[1] : null;
    }
}