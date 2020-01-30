<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token','email_verified_at'
    ];

    /**
     *Get users declines
     */
    public function declines()
    {
        return $this->hasMany('App\DeclinesDescription','user_id','id');
    }

    /**
     *Get person uploads
     */
    public function persons_uploads()
    {
        return $this->hasMany('App\PersonUploads','user_id','id');
    }


    /**
     * Get persons data
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function person_data()
    {
        return $this->hasOne('App\PersonData', 'user_id','id');
    }

    /**
     * Get business data
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function business_data()
    {
        return $this->hasOne('App\BusinessData', 'user_id','id');
    }

    /**
     *Get business uploads
     */
    public function business_uploads()
    {
        return $this->hasMany('App\BusinessUploads','user_id','id');
    }

    /**
     * Get business entity
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function business_entity()
    {
        return $this->hasOne('App\Business','user_id','id');
    }

    /**
     *Get associated person entity
     */
    public function person_entity()
    {
        return $this->hasOne('App\Person','user_id','id');
    }

    public function sumsub_entity()
    {
        return $this->hasOne('App\SumSub', 'user_id','id');
    }
}
