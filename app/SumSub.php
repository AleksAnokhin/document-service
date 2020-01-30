<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SumSub extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'sum_sub_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'pending', 'reviewing','sum_sub_external_id','sum_sub_id', 'created','on_hold', 'prechecked','moderator_comment','client_comment',
        'reviewed_answer', 'reviewed_rejected_type'
    ];



}
