<?php

if(!function_exists('getLang')) {
    function getLang($full=false)
    {
        return $full === false ? ["ru", "en", "tr"] :[ "ru" => "russian", "en" => "english", "tr" => "turkish"];
    }
}

if(!function_exists('getUserRole')) {
    function getUserRole(int $type_id) {
        if($type_id === 1) {
            return 'operator';
        } elseif($type_id === 6) {
            return 'admin';
        } elseif($type_id === 21 || $type_id === 24) {
            return 'merchant';
        } elseif($type_id === 15 || $type_id === 16 || $type_id === 17) {
            return 'business';
        } elseif($type_id === 4 || $type_id === 8 || $type_id === 9) {
            return 'person';
        } else {

            //default role
           return 'person';
        }
    }
}

if(!function_exists('transformMimeType')) {
    function transformMimeType(string $filetype) {

        switch($filetype) {
            case 'jpg' :
                return 'image/jpeg';
                break;
            case 'jpeg' :
                return 'image/jpeg';
                break;
            case 'png' :
                return 'image/png';
                break;
            case 'pdf' :
                return 'application/pdf';
                break;
            default :
                return 'text/plain';
        }
    }
}

if(!function_exists('statusTransformer')) {
    function statusTransformer(string $filed)
    {
        switch ($filed) {
            case 'created' :
                return 2;
                break;

            case 'pending' :
                return 3;
                break;

            case 'on_hold' :
                return 8;
                break;
            case 'prechecked' :
                return 9;
                break;
            default:
                return 1;
        }
    }
}

