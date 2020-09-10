<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 
    	'cross_street',
    	'address',
    	'address2',
    	'city',
    	'state',
    	'country',
    	'postal_code',
    	'cc',
    	'latitude',
    	'longitude',
    ];

    public function venues()
    {
        return $this->belongsToMany('App\Venue');
    }

    public function events()
    {
        return $this->hasMany('App\Event');
    }

    public function organizations()
    {
        return $this->hasMany('App\Organization');
    }

}
