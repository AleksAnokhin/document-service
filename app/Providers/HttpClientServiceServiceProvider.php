<?php


namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Tools\DocumentorClient;

class HttpClientServiceServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('DocumentorClient', function() {
            return new DocumentorClient();
        });
    }
}
