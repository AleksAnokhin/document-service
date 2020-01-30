<?php


namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Tools\SumSubConnector;

class ToolsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('SumSubConnector', function() {
           return new SumSubConnector();
        });
    }
}
