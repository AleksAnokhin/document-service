<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessUploads extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business_uploads';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'status_id','name','type','size','path',
    ];
}
