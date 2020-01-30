<?php


namespace App\Facades;


use Illuminate\Support\Facades\Facade;

class DocumentorEncryptor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'DocumentorEncryptor';
    }
}
