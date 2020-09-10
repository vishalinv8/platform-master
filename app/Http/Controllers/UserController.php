<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\Location;
use App\EventVisibilityType;
use App\Http\Resources\UserResource;

use App\Utils;
use Illuminate\Support\Facades\DB;

//use Laravolt\Avatar\Avatar;
use Laravolt\Avatar\Facade as Avatar;


class UserController extends Controller
{

	public function __construct()
	{
			$this->middleware('auth:api');
	}

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query_builder = User::summary()->with('user_profile');

        $columns_to_search = [ 'name', ];
        Utils::whereWordSearch($query_builder, $request, $columns_to_search);

        // Word searches are table-OR'd: any match in any table, but must have a match.
        $columns_to_search = [ 'description', 'image_url', 'twitter', 'instagram', 'facebook' ];
        Utils::orWhereWordSearchHasOneTable($query_builder, $request, $columns_to_search, 'user_profiles', 'user_id');

        $columns_to_search = [ 'cross_street', 'address', 'address2', 'city', 'state', 'country', 'postal_code', 'cc' ];
        Utils::orWhereWordSearchBelongsToTable($query_builder, $request, $columns_to_search, 'locations', 'location_id', 'user_profiles');

        // Operator searches are table-AND'd: must match across all tables where the search column is present.
        $columns_to_search = [ 'name', ];
        Utils::whereOperatorSearch($query_builder, $request, $columns_to_search);

        $columns_to_search = [ 'name', 'birth_date', 'user_id', 'gender_id', 'skill_level_id', 'age_group_id' ];
        Utils::whereOperatorSearchHasOneTable($query_builder, $request, $columns_to_search, 'user_profiles', 'user_id');

        // Also search the computed field (which is not a table column):
        if ($request->has('max_miles')) {
            $query_builder->maxMiles($request->max_miles, $request->near_lat, $request->near_lon);            
        }

        $collection = UserResource::collection(
			$query_builder->paginate(100)
        );

		return $collection;
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, User $user)
    {
        // Get all the joined foreign key fields, and check permissions:
        $user = User::summary()->with('user_profile')
            ->with('organizations_where_admin')
            ->with('organizations_where_poster')
            ->with('organizations_where_member')
            ->findOrFail($user->id);

        return new UserResource($user);
    }

    public function get_me(Request $request)
    {
        // Get all the joined foreign key fields, and check permissions:
        $user = User::summary()->with('user_profile')
            ->with('organizations_where_admin')
            ->with('organizations_where_poster')
            ->with('organizations_where_member')
            ->findOrFail($request->user()->id);

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        // Unlike events, posts, or orgs, user_profiles can only be updated by the owner:
        $has_permission = ($request->user()->id == $user->id) ? true : false;
        if (! $has_permission) {
          return response()->json(['error' => 'You do not have update permission.'], 403);
        }

        // Ignore any request input 'user_id', and force it to be the creator's id.
        // We have to do it here because it's a fillable[] value for easier INSERTs.
        if ($request->has('user_id')) {
            $request->merge(['user_id' => $user->id]);
        }
        

        $user->fill( $request->only($user->getFillable()) );
        
        if ($request->password) {
            # Process a password update into its hash value:
            $user->password = Hash::make($request->password);
        }

        // If the user uploaded a new avatar, save it to disk w/a unique filename
        // and update the URL:
        if ($request->hasFile('avatar_file')) {
            // Create a default avatar locally, and save the relative URL.
            $image      = $request->file('avatar_file');
            $avatar_path = Utils::newFileUUID("public/avatars/", ".".strtolower($image->getClientOriginalExtension()));
            Storage::put($avatar_path, $image);

            # Set the avatar URL. The storage driver uses a different path than
            # the Apache web server. See also: php artisan storage:link
            #
            # $avatar_path public/avatars/fbdb37e6-4040-44fe-aa1b-7d838742dcea.png
            # $avatar_url  https://10.0.3.232/storage/avatars/fbdb37e6-4040-44fe-aa1b-7d838742dcea.png
            # 
            # Strip the 'public/' prefix, and replace it with 'storage/'

            $protocol = "https://";
            if (! $request->isSecure()) {
                $protocol = "http://";  // Must be a development machine. Play nice.
            }
            $hostname = $_SERVER['SERVER_ADDR'];
            $port = $_SERVER['SERVER_PORT'];

            if ($port==443 || $port==80) {
                // Just let it default based on the protocol. It looks cleaner.
                $port = "";
            } else {
                $port = ":$port";
            }

            // Remove the incorrect public/ prefix from the generated filename.
            $avatar_url = $protocol. $request->server('SERVER_NAME') . $port . "/". "storage/" . str_replace("public/", '', $avatar_path);
            $user->avatar_url = $avatar_url;
        }
        
        $user->save();

        $user_profile = $user->user_profile;

        // Save an uploaded image, and set its URL into image_url.
        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $image_path = "public/users/".$user->id."/";
            $image_url = Utils::saveImage($request, $image_path, $request->image_file->getClientOriginalName(), $request->file('image_file'));
            $user_profile->image_url = $image_url;
        }

        $user_profile->fill( $request->only($user_profile->getFillable()) );
        $user_profile->save();

        $location = $user_profile->location;
        $location->fill( $request->only($location->getFillable()) );
        $location->save();
        
        
        // Get all the joined foreign key fields, and check permissions:
        $user = User::summary()->with('user_profile')
            ->with('organizations_where_admin')
            ->with('organizations_where_poster')
            ->with('organizations_where_member')
            ->findOrFail($user->id);

        return new UserResource($user);
    }

    public function friends(Request $request)
    {
        // The friends package includes the getFriends() function, but it does 
        // not use the profile or check its post_status_id permissions.
        //   $request->user()->getFriends($perPage = 1000)


        // Get all the joined foreign key fields, and check permissions:
        $query_builder = User::summary()->with('user_profile')
            ->with('organizations_where_admin')
            ->with('organizations_where_poster')
            ->with('organizations_where_member');

        Utils::whereIsFriendWithUserID($query_builder, $request->user()->id, 'users', $keyname='id');

        $collection = UserResource::collection(
			$query_builder->paginate(100)
        );

		return $collection;
    }

// derek
    public function friends_of_user(Request $request, User $user)
    {
        // The friends package includes the getFriends() function, but it does 
        // not use the profile or check its post_status_id permissions.
        //   $request->user()->getFriends($perPage = 1000)


        // Get all the joined foreign key fields, and check permissions:
        $query_builder = User::summary()->with('user_profile')
            ->with('organizations_where_admin')
            ->with('organizations_where_poster')
            ->with('organizations_where_member');

        Utils::whereIsFriendWithUserID($query_builder, $user->id, 'users', $keyname='id');

        $collection = UserResource::collection(
			$query_builder->paginate(100)
        );

		return $collection;
    }

    public function friends_of_friends(Request $request)
    {
    	$friends_of_friends_ids = $request->user()->getFriendsOfFriends($perPage = 100)->pluck('id');

        // Get all the joined foreign key fields, and check permissions:
        $query_builder = User::summary()->with('user_profile')
            ->with('organizations_where_admin')
            ->with('organizations_where_poster')
            ->with('organizations_where_member');

        $query_builder->whereIn('users.id', $friends_of_friends_ids);

        $collection = UserResource::collection(
			$query_builder->paginate(100)
        );

		return $collection;
    }

    public function mutual_friends(Request $request, User $other)
    {
    	$mutual_friends_ids = $request->user()->getMutualFriends($otherUser, $perPage = 100)->pluck('id');

        // Get all the joined foreign key fields, and check permissions:
        $query_builder = User::summary()->with('user_profile')
            ->with('organizations_where_admin')
            ->with('organizations_where_poster')
            ->with('organizations_where_member');

        $query_builder->whereIn('users.id', $mutual_friends_ids);

        $collection = UserResource::collection(
			$query_builder->paginate(100)
        );

		return $collection;
    }

    public function post_friend_request(Request $request, User $recipient)
    {
    	$friendship = $request->user()->befriend($recipient);
    	
    	// TEMPORARy
            $recipient->acceptFriendRequest($request->user());


    	if (! $friendship) {
          return response()->json(['error' => "Failed (perhaps it's a duplicate request?)"], 400);
    	}
    	
        return response()->json([
            'data' => $friendship,
        ]);
    }

    public function delete_friend_request(Request $request, User $recipient)
    {
    	$friendship = $request->user()->unfriend($recipient);
    	if (! $friendship) {
          return response()->json(['error' => 'General failure.'], 400);
    	}
    	
        return response()->json([
            'data' => $friendship,
        ]);
    }

    public function requests_pending(Request $request, User $recipient)
    {
        return response()->json([
            'data' => $request->user()->getFriendRequests(),
        ]);
    }

    public function requests_accept(Request $request, User $sender)
    {
        return response()->json([
            'data' => $request->user()->acceptFriendRequest($sender),
        ]);
    }

    public function requests_deny(Request $request, User $sender)
    {
        return response()->json([
            'data' => $request->user()->denyFriendRequest($sender),
        ]);
    }

    public function requests_denied(Request $request)
    {
        return response()->json([
            'data' => $request->user()->getDeniedFriendships(),
        ]);
    }

    public function block(Request $request, User $friend)
    {
        return response()->json([
            'data' => $request->user()->blockFriend($friend),
        ]);
    }

    public function blocked(Request $request)
    {
        return response()->json([
            'data' => $request->user()->getBlockedFriendships(),
        ]);
    }

    public function unblock(Request $request, User $friend)
    {
        return response()->json([
            'data' => $request->user()->unblockFriend($friend),
        ]);
    }


}
