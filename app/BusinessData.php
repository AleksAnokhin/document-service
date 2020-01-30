<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessData extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type_passport','user_id','document_set'
    ];
}
