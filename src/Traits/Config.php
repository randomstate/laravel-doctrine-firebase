<?php


namespace RandomState\LaravelDoctrineFirebase\Traits;


trait Config
{
    protected function getConfig($key)
    {
        return $this->app->make('config')->offsetGet(self::getConfigName() . '.' . $key);
    }
    private static function getConfigName()
    {
        return 'laravel-doctrine-firebase';
    }
    private static function getConfigPath()
    {
        return __DIR__ . '/../../config/' . self::getConfigName() . '.php';
    }
}