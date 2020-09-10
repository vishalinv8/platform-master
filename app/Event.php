<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Event extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
    	'start_datetime',
    	'end_datetime',    	
    	'deleted_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 
    	'title',
    	'description',
    	'phone',
        'url',
        'phone',
        'event_email',
        'twitter',
        'instagram',
        'facebook',
    	'image_url',
    	'video_url',
    	'post_status_id',
    	'user_id',
    	'location_id',
    	'gender_id',
    	'age_group_id',
    	'activity_type_id',
    	'skill_level_id',
    	'start_datetime',
    	'end_datetime',
    	'desired_user_going_count',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function location()
    {
        return $this->belongsTo('App\Location');
    }

    public function gender()
    {
        return $this->belongsTo('App\Gender');
    }

    public function age_group()
    {
        return $this->belongsTo('App\AgeGroup');
    }

    public function activity_type()
    {
        return $this->belongsTo('App\ActivityType');
    }

    public function skill_level()
    {
        return $this->belongsTo('App\SkillLevel');
    }

    public function users_going()
    {
        $query_builder = $this->belongsToMany('App\User', 'event_user_going');
        $query_builder->summary()->with('user_profile');
        return $query_builder;
    }

    public function users_alerting()
    {
        $query_builder = $this->belongsToMany('App\User', 'event_user_alerting');
        $query_builder->summary();
        return $query_builder;
    }

    public function comments()
    {
        $query_builder = $this->belongsToMany('App\Comment', 'event_comment');
//        $query_builder->summary();
        return $query_builder;
    }

	// The owner organization, for enforcement of "organization_*" visibility:
    public function organization()
    {
        return $this->belongsTo('App\Organization');
    }

	// Other organizations, e.g. sponsoring, etc. Currently unused 2018-08-12
    public function organizations()
    {
        return $this->belongsToMany('App\Organization');
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

        $query_builder->leftJoin('locations', 'locations.id', '=','events.location_id')
            ->leftJoin('genders', 'genders.id', '=', 'events.gender_id')
            ->leftJoin('age_groups', 'age_groups.id', '=', 'events.age_group_id')
            ->leftJoin('activity_types', 'activity_types.id', '=', 'events.activity_type_id')
            ->leftJoin('skill_levels', 'skill_levels.id', '=', 'events.skill_level_id')
            ->leftJoin('post_statuses', 'post_statuses.id', '=', 'events.post_status_id')
            ->leftJoin('users', 'users.id', '=', 'events.user_id')
            ->leftJoin('organization_post_statuses', 'organization_post_statuses.id', '=', 'events.organization_post_status_id')
            ->select( DB::raw('
                    events.id,
                    events.title,
                    events.description,
                    events.phone,
                    events.event_email,
                    events.url,                    
                    events.twitter,
                    events.instagram,
                    events.facebook,
                    events.image_url,
                    events.video_url,

                    events.desired_user_going_count,
                    events.start_datetime,
                    events.end_datetime,
                    events.post_status_id,
                    events.organization_id,
                    events.organization_post_status_id,
                    events.user_id,
                    users.name AS user_name,
                    events.gender_id,
                    events.age_group_id,
                    events.activity_type_id,
                    events.skill_level_id,
                    events.created_at,
                    events.updated_at,
                    
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
                    
                    (SELECT COUNT(*) FROM event_user_going WHERE event_user_going.event_id = events.id) AS user_going_count, 
                    (SELECT COUNT(*) FROM event_user_alerting WHERE event_user_alerting.event_id = events.id) AS user_alerting_count, 

                    genders.name as gender_name, 
                    genders.display_name as gender_display_name, 
                    age_groups.name as age_group_name, 
                    age_groups.display_name as age_group_display_name, 
                    activity_types.name as activity_type_name, 
                    activity_types.display_name as activity_type_display_name, 
                    skill_levels.name as skill_level_name, 
                    skill_levels.display_name as skill_level_display_name, 
                    
                    post_statuses.name as post_status_name, 
                    post_statuses.display_name as post_status_display_name, 
                    organization_post_statuses.name as organization_post_status_name, 
                    organization_post_statuses.display_name as organization_post_status_display_name,

                    2 * 3961 * asin(sqrt(POWER((sin(radians((locations.latitude - '.$near_lat.') / 2))), 2) + cos(radians('.$near_lat.')) * cos(radians(locations.latitude)) * POWER((sin(radians((locations.longitude - '.$near_lon.') / 2))), 2))) as distance_miles
                ') );

        Utils::whereHasReadPermission($query_builder, $user_id);
        $query_builder->orderBy('start_datetime', 'asc');
    }

    public function scopeMaxMiles($query_builder, $max_miles, $near_lat=null, $near_lon=null)
    {        
        $table_name = "events";
        $table_fk = "id";
        $model_table = "events"; 
        $model_fk = "id";
        Utils::maxMiles($query_builder, $table_name, $table_fk, $model_table, $model_fk, $max_miles, $near_lat, $near_lon);
    }

    public function scopeFutureOnly($query_builder)
    {        
        $table_name = "events";
        $table_fk = "id";
        $model_table = "events"; 
        $model_fk = "id";
        Utils::futureOnly($query_builder, $table_name, $table_fk, $model_table, $model_fk);
    }

    public function scopetodayOnly($query_builder)
    {        
        $table_name = "events";
        $table_fk = "id";
        $model_table = "events"; 
        $model_fk = "id";
        Utils::todayOnly($query_builder, $table_name, $table_fk, $model_table, $model_fk);
    }
}
