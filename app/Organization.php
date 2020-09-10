<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Organization extends Model
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
    	'name',
    	'description',
        'url',
    	'phone',
        'organization_email',
        'twitter',
        'instagram',
        'facebook',
    	'image_url',
    	'video_url',
    	'user_id',
    	'location_id',
    	'enrollment_policy_id'
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function location()
    {
        return $this->belongsTo('App\Location');
    }

    public function branch_locations()
    {
        return $this->belongsToMany('App\Location');
    }

    public function admins()
    {
        return $this->belongsToMany('App\User', 'organization_user_admins');
    }

    public function posters()
    {
        return $this->belongsToMany('App\User', 'organization_user_posters');
    }

    public function members()
    {
        return $this->belongsToMany('App\User', 'organization_user_members');
    }

    public function events()
    {
        return $this->hasMany('App\Event');
    }

    public function enrollment_policy()
    {
        return $this->belongsTo('App\EnrollmentPolicy');
    }

    public function scopeReadable($query_builder, $user_id, $near_lat=null, $near_lon=null)
    {
        // These are used with the Haversine distance formula, calculated on the SQL server:
        // http://daynebatten.com/2015/09/latitude-longitude-distance-sql/
        // 
        if ($near_lat == null || $near_lon == null) {
            // The default distance is relative to the requestor:
            $near_lat = request()->user()->user_profile->location->latitude;
            $near_lon = request()->user()->user_profile->location->longitude;
        }

        //
        // Security note:
        // The query builder fails with the distance SQL functions, when trying to 
        // use safe ? parameters in an array. We use raw SQL to get around that,
        // but cast the inputs to floats to mitigate SQL injection here.
        // 
        $near_lat = (float)$near_lat;
        $near_lon = (float)$near_lon;

        $query_builder->leftJoin('locations', 'locations.id', '=','organizations.location_id')
            ->select( DB::raw('
                    organizations.id,
                    organizations.name,
                    organizations.description,
                    organizations.phone,
                    organizations.organization_email,
                    organizations.url,                    
                    organizations.twitter,
                    organizations.instagram,
                    organizations.facebook,
                    organizations.image_url,
                    organizations.video_url,

                    organizations.user_id,
                    organizations.created_at,
                    organizations.updated_at,
                    
                    locations.cross_street,
                    locations.address,
                    locations.address2,
                    locations.city,
                    locations.state,
                    locations.country,
                    locations.postal_code,
                    locations.cc,
                    locations.latitude,
                    locations.longitude,

                    (SELECT COUNT(*) FROM organization_user_admins WHERE organization_user_admins.organization_id = organizations.id) AS organization_admin_count, 
                    (SELECT COUNT(*) FROM organization_user_posters WHERE organization_user_posters.organization_id = organizations.id) AS organization_poster_count, 
                    (SELECT COUNT(*) FROM organization_user_members WHERE organization_user_members.organization_id = organizations.id) AS organization_member_count,

                    2 * 3961 * asin(sqrt(POWER((sin(radians((locations.latitude - '.$near_lat.') / 2))), 2) + cos(radians('.$near_lat.')) * cos(radians(locations.latitude)) * POWER((sin(radians((locations.longitude - '.$near_lon.') / 2))), 2))) as distance_miles
                ') );

        $query_builder->orderBy('distance_miles', 'asc');

        // Any registered user can see all organizations
        //Utils::whereHasReadPermission($query_builder, $user_id);
    }

    public function scopeMaxMiles($query_builder, $max_miles, $near_lat=null, $near_lon=null)
    {        
        $table_name = "organizations";
        $table_fk = "id";
        $model_table = "organizations"; 
        $model_fk = "id";
        Utils::maxMiles($query_builder, $table_name, $table_fk, $model_table, $model_fk, $max_miles, $near_lat, $near_lon);
    }

}
