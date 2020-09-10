<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class UserProfile extends Model
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
        'user_id',
        'description',
        'image_url',
        'video_url',
        'uses_calendar',
        'twitter',
        'instagram',
        'facebook',
        'gender_id',
        'skill_level_id',
        'age_group_id',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function location()
    {
        return $this->belongsTo('App\Location');
    }

    public function activity_types()
    {
        return $this->belongsToMany('App\ActivityType');
    }

    // The UserProfile model has the same relationship to organizations as User,
    // except the key is 'user_id' instead of 'user':
    public function organizations_where_admin()
    {
        return $this->belongsToMany('App\Organization', 'organization_user_admins', 'user_id');
    }

    public function organizations_where_poster()
    {
        return $this->belongsToMany('App\Organization', 'organization_user_admins', 'user_id');
    }

    public function organizations_where_member()
    {
        return $this->belongsToMany('App\Organization', 'organization_user_members', 'user_id');
    }

    public function scopeReadable($query_builder, $user_id, $near_lat, $near_lon)
    {
        // These are used with the Haversine distance formula, calculated on the SQL server:
        // http://daynebatten.com/2015/09/latitude-longitude-distance-sql/
        // 
        
        if ($near_lat == null || $near_lon == null) {
            // NOTE: request()->user()->user_profile is undefined if we got here
            // by trying to look up request()->user()->user_profile. So we find
            // the profile manually in order to default to the user's home/profile lat/lon.

            // The default distance is relative to the requestor:
            $user_profile = UserProfile::where('user_id', '=', request()->user()->id)->with('location')->first();
            $near_lat = $user_profile->location->latitude;
            $near_lon = $user_profile->location->longitude;
        }

        //
        // Security note:
        // The query builder fails with the distance SQL functions, when trying to 
        // use safe ? parameters in an array. We use raw SQL to get around that,
        // but cast the inputs to floats to mitigate SQL injection here.
        // 
        $near_lat = (float)$near_lat;
        $near_lon = (float)$near_lon;


        // NOTE: whereHasReadPermission() needs these fields to work:
        // user_id, post_status_id, and optionally organization_id, organization_post_status_id
        //
        // The users table is unique in that it has users.id instead of user_id, and
        // a linked user_profiles.post_status_id (and the rest). This is so we can keep all
        // app-specific fields in the user_profile, rather than the users table.
        // So we alias users.id as user_id, and use user_profile (or NULL) for the rest of the fields:
        $query_builder->join('locations', 'locations.id', '=','user_profiles.location_id')
            ->join('post_statuses', 'post_statuses.id', '=','user_profiles.post_status_id')
            ->leftJoin('genders', 'genders.id', '=','user_profiles.gender_id')
            ->leftJoin('skill_levels', 'skill_levels.id', '=','user_profiles.skill_level_id')
            ->leftJoin('age_groups', 'age_groups.id', '=','user_profiles.age_group_id')
            ->select( DB::raw('
                    user_profiles.id,
                    user_profiles.user_id,
                    user_profiles.description,
                    user_profiles.image_url,
                    user_profiles.video_url,
                    user_profiles.uses_calendar,
                    user_profiles.twitter,
                    user_profiles.instagram,
                    user_profiles.facebook,

                    user_profiles.gender_id,
                    user_profiles.skill_level_id,
                    user_profiles.age_group_id,
                    user_profiles.location_id,
                    user_profiles.created_at,

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

                    genders.name as gender_name, 
                    genders.display_name as gender_display_name,
                    skill_levels.name as skill_level_name, 
                    skill_levels.display_name as skill_level_display_name,
                    age_groups.name as age_group_name, 
                    age_groups.display_name as age_group_display_name,

                    2 * 3961 * asin(sqrt(POWER((sin(radians((locations.latitude - '.$near_lat.') / 2))), 2) + cos(radians('.$near_lat.')) * cos(radians(locations.latitude)) * POWER((sin(radians((locations.longitude - '.$near_lon.') / 2))), 2))) as distance_miles
                ') );

        // Check the 'post_status_id', 'organzation_id', etc. from the user_profiles table:
        Utils::whereHasReadPermission($query_builder, $user_id, 'user_profiles');
    }
}
