<?php


namespace App\Contracts;


interface DocumentValidator
{
    public static function initPerson(string $user_id=null, array $document_set);
}
