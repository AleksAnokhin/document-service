<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PersonData extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'person_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id','type_passport','type_bank','kyc_input',
    ];
}
