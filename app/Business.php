<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'business';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'name','reg_num','nalog_num','biz_profile','country_legal','city_legal','street_legal','zip_legal','country_actual','street_actual','zip_actual',
        'ben1_name','ben1_surname','ben2_name','ben2_surname','ben3_name','ben3_surname','dir_name','dir_surname','tel_prefix','tel_time','pep','us'
    ];
}
