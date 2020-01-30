<?php


namespace App\Providers;


use App\Tools\DocumentorEncryptor;
use Carbon\Laravel\ServiceProvider;

class EncryptServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('DocumentorEncryptor', function() {
            return new DocumentorEncryptor();
        });
    }
}
