<?php


namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class DocumentorClient extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'DocumentorClient';
    }
}
