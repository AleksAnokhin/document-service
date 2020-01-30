<?php


namespace App\Tools;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;

class DocumentorEncryptor
{
    /**
     * Encrypt file content with dinamic key
     * @param $content
     * @param $uid
     * @return string
     */
    public static function encrypt($content, $uid)
   {
         //36 - uid   51 - key
         $key = substr(env('APP_KEY'),0,24) . substr($uid,0,8);
         $encryptor = new Encrypter($key,env('CIPHER'));
         return $encryptor->encrypt($content);
   }

    /**
     * Decrypt file content
     * @param $content
     * @param $uid
     */
    public static function decrypt($content, $uid)
   {
       $key = substr(env('APP_KEY'),0,24) . substr($uid,0,8);
       $encryptor = new Encrypter($key,env('CIPHER'));
       return $encryptor->decrypt($content);

   }


}
