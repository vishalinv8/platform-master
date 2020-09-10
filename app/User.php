<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Hootlex\Friendships\Traits\Friendable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use SoftDeletes;
    use Friendable;

    /**
     * The attributes that should be mutated to dates.
     * Also did: php artisan make:migration users_add_deleted_at --table=users
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'birth_date'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'avatar_url'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'email', 'deleted_at'
    ];
    
    // https://blog.pusher.com/build-rest-api-laravel-api-resources/
    // use Tymon\JWTAuth\Contracts\JWTSubject; ...implements JWTSubject
    public function getJWTIdentifier()
    {
      return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
      return [];
    }


    // NOTE: See also readable($user_id), which applies permisions
    // and includes all of the profile info in the query result.
    public function user_profile()
    {
        return $this->hasOne('App\UserProfile')->readable(request()->user()->id, request()->near_lat, request()->near_lon);
    }

    public function organizations_where_admin()
    {
        return $this->belongsToMany('App\Organization', 'organization_user_admins');
    }

    public function organizations_where_poster()
    {
        return $this->belongsToMany('App\Organization', 'organization_user_posters');
    }

    public function organizations_where_member()
    {
        return $this->belongsToMany('App\Organization', 'organization_user_members');
    }

    // FIXME: This is unused code and can be deleted. It worked but didn't do what we want.
    public function scopeReadable($query_builder, $user_id)
    {
        // NOTE: whereHasReadPermission() needs these fields to work:
        // user_id, post_status_id
        //
        // The users table is unique in that it has users.id instead of user_id, and
        // a linked user_profiles.post_status_id (and the rest). This is so we can keep all
        // app-specific fields in the user_profile, rather than the users table.
        // So we alias users.id as user_id, and use user_profile (or NULL) for the rest of the fields:
        $query_builder->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->join('locations', 'locations.id', '=','user_profiles.location_id')
            ->join('post_statuses', 'post_statuses.id', '=','user_profiles.post_status_id')
            ->select( DB::raw('
                    users.id as id,
                    users.name as name, 
                    users.nickname as nickname,
                   
                    user_profiles.user_id,
                    user_profiles.description,
                    user_profiles.image_url,
                    user_profiles.uses_calendar,
                    user_profiles.twitter,
                    user_profiles.instagram,
                    user_profiles.facebook,
                    user_profiles.post_status_id as post_status_id,
                    post_statuses.name as post_status_name,
                    post_statuses.display_name as post_status_display_name,

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

                    (SELECT COUNT(*) FROM organization_user_admins WHERE organization_user_admins.user_id = user_profiles.user_id) AS organization_admin_count, 
                    (SELECT COUNT(*) FROM organization_user_posters WHERE organization_user_posters.user_id = user_profiles.user_id) AS organization_poster_count, 
                    (SELECT COUNT(*) FROM organization_user_members WHERE organization_user_members.user_id = user_profiles.user_id) AS organization_member_count,
                    (SELECT COUNT(*) FROM events WHERE events.user_id = user_profiles.user_id AND deleted_at = NULL) AS event_count
                ') );

        // Check the 'post_status_id', 'organzation_id', etc. from the user_profiles table:
        Utils::whereHasReadPermission($query_builder, $user_id, 'user_profiles');
    }

    public function scopeSummary($query_builder)
    {
        // NOTE: whereHasReadPermission() needs these fields to work:
        // user_id, post_status_id
        // 
        // The users table is unique in that it has users.id instead of user_id, and
        // a linked user_profiles.post_status_id (and the rest). This is so we can keep all
        // app-specific fields in the user_profile, rather than the users table.
        // So we alias users.id as user_id, and use user_profile (or NULL) for the rest of the fields:
        $query_builder->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->join('post_statuses', 'post_statuses.id', '=','user_profiles.post_status_id')
            ->select( DB::raw('
                    users.id as id,
                    users.name as name,
                    users.nickname as nickname,
                    users.avatar_url as avatar_url,
                    users.created_at as created_at,
                    user_profiles.post_status_id as post_status_id,
                    post_statuses.name as post_status_name,
                    post_statuses.display_name as post_status_display_name,
    
                    (SELECT COUNT(*) FROM organization_user_admins WHERE organization_user_admins.user_id = user_profiles.user_id) AS organization_admin_count, 
                    (SELECT COUNT(*) FROM organization_user_posters WHERE organization_user_posters.user_id = user_profiles.user_id) AS organization_poster_count, 
                    (SELECT COUNT(*) FROM organization_user_members WHERE organization_user_members.user_id = user_profiles.user_id) AS organization_member_count,
                    (SELECT COUNT(*) FROM events WHERE events.user_id = user_profiles.user_id AND deleted_at = NULL) AS event_count
               ') );
        $query_builder->orderBy('created_at', 'desc');
    }


    public function scopeMaxMiles($query_builder, $max_miles, $near_lat=null, $near_lon=null)
    {        
        $table_name = "user_profiles";
        $table_fk = "id";
        $model_table = "user_profiles"; 
        $model_fk = "id";
        Utils::maxMiles($query_builder, $table_name, $table_fk, $model_table, $model_fk, $max_miles, $near_lat, $near_lon);
    }

}