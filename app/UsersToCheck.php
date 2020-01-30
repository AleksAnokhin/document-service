<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UsersToCheck extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_to_check';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
    ];

    /**
     *Get associated person entity
     */
    public function users()
    {
        return $this->belongsTo('App\Users','id','user_id');
    }


    public static function usersId()
    {
        $response = [];
        $models = self::all();
        if(empty($models)) return [];
        foreach($models as $model) {
            $response[] = $model->user_id;
        }

        return $response;
    }
}
