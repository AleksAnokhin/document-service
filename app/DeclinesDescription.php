<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeclinesDescription extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'declines_description';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id','decline_id','description','filename'
    ];
}
