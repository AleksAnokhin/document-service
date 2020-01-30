<?php

namespace App\heritage_models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $connection = 'heritage';


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';
}
