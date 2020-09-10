<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Event;
use App\Location;
use App\Comment;
use App\EventVisibilityType;
use App\Http\Resources\EventResource;

use App\Utils;
use Illuminate\Support\Facades\DB;
use Log;


class EventController extends Controller
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
        // A null near_lat/near_lon will default to using the requestor's lat/lon
        $query_builder = Event::readable($request->user()->id, $request->near_lat, $request->near_lon);

        $columns_to_search = [ 'title', 'description', 'url', 'phone', 'event_email', 
            'twitter', 'instagram', 'facebook', 'image_url', 'video_url' ];
        Utils::whereWordSearch($query_builder, $request, $columns_to_search);
        // Word searches are table-OR'd: any match in any table, but must have a match.
        $columns_to_search = [ 'cross_street', 'address', 'address2', 'city', 'state', 'country', 'postal_code', 'cc' ];
        Utils::orWhereWordSearchBelongsToTable($query_builder, $request, $columns_to_search, 'locations', 'location_id');
        $columns_to_search = ['name'];
        Utils::orWhereWordSearchBelongsToTable($query_builder, $request, $columns_to_search, 'genders', 'gender_id');
        Utils::orWhereWordSearchBelongsToTable($query_builder, $request, $columns_to_search, 'age_groups', 'age_group_id');
        Utils::orWhereWordSearchBelongsToTable($query_builder, $request, $columns_to_search, 'activity_types', 'activity_type_id');
        Utils::orWhereWordSearchBelongsToTable($query_builder, $request, $columns_to_search, 'skill_levels', 'skill_level_id');
        Utils::orWhereWordSearchBelongsToTable($query_builder, $request, $columns_to_search, 'users', 'user_id');

        $columns_to_search = [ 'title', 'description', 'url', 'phone', 'event_email', 
            'twitter', 'instagram', 'facebook', 'image_url', 'video_url', 'organization_id', 'start_datetime', 'end_datetime',
            'post_status_id', 'organization_post_status_id', 'user_id', 'gender_id', 'age_group_id', 'activity_type_id',
            'skill_level_id'];
        Utils::whereOperatorSearch($query_builder, $request, $columns_to_search);
        // Operator searches are table-AND'd: must match across all tables where the search column is present.
        $columns_to_search = [ 'cross_street', 'address', 'address2', 'city', 'state', 'country', 'postal_code', 'cc', 'latitude', 'longitude' ];
        Utils::whereOperatorSearchBelongsToTable($query_builder, $request, $columns_to_search, 'locations', 'location_id');
        $columns_to_search = [ 'name' ];
        Utils::whereOperatorSearchBelongsToTable($query_builder, $request, $columns_to_search, 'users', 'user_id');
        
        // Also search the computed field (which is not a table column):
        if ($request->has('max_miles')) {
            $query_builder->maxMiles($request->max_miles, $request->near_lat, $request->near_lon);            
        }

        if ($request->has('future_only')) {
            $query_builder->futureOnly();            
        }

        if ($request->has('today_only')) {
            $query_builder->todayOnly();            
        }

        $collection = EventResource::collection(
			$query_builder->paginate(100)
        );

		return $collection;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $has_permission = Utils::hasCreatePermission($request);
        if (! $has_permission) {
          return response()->json(['error' => 'You do not have create permission.'], 403);
        }

        // Ignore any request input 'user_id', and force it to be the creator's id:
        // We have to do it here because it's a fillable[] value for easier INSERTs.
        $request->merge(['user_id' => $request->user()->id]);

        $location = new Location(); // This empty instance is just to call getFillable()
        $location = Location::create( $request->only($location->getFillable()) );
        $location->save();
        
        // Set the location_id, now that we know it:
        $request->merge(['location_id' => $location->id]);

        $event = new Event(); // This empty instance is just to call getFillable() 
        $event = Event::create( $request->only($event->getFillable()) );
        $event->save();

        // Save an uploaded image, and set its URL into image_url.
        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $image_path = "public/events/".$event->id."/";
            $image_url = Utils::saveImage($request, $image_path, $request->image_file->getClientOriginalName(), $request->file('image_file'));
            $event->image_url = $image_url;
            $event->save();
        }
        
        // Load with the new values:
        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->findOrFail($event->id);
        return new EventResource($event);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Event $event)
    {
        // Get all the joined foreign key fields, and check permissions:
//        $event = Event::readable($request)->with('users_going')->with('users_alerting')->with('users_with_profiles_going')->findOrFail($event->id);
//        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->findOrFail($event->id);
        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->with('comments')->findOrFail($event->id);
        return new EventResource($event);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Event $event)
    {
        $has_permission = Utils::hasUpdatePermission($request, $event);
        if (! $has_permission) {
          return response()->json(['error' => 'You do not have update permission.'], 403);
        }

        // Ignore any request input 'user_id', and force it to be the creator's id.
        // We have to do it here because it's a fillable[] value for easier INSERTs.
//        if ($request->has('user_id')) {
//            $request->merge(['user_id' => $event->user_id]);
//        }
//        $request->merge(['user_id' => $event->user_id]);
	$request->request->remove('user_id');
	$request->request->remove('location_id');

//        $request->merge(['location_id' => $event->location_id]);

	// Use the full event objection from the database, incl. location_id:
	$event = Event::findOrFail($event->id);
        $event->fill( $request->only($event->getFillable()) );
        $event->save();

//Log::debug(print_r($event, true));
//Log::debug(print_r($event, true));

        $location = Location::findOrFail($event->location_id);
	//Log::debug("XXXXXXXXXXXXXXXXXXXXXXX");
	//Log::debug(print_r($location, true));

        $location->fill( $request->only($location->getFillable()) );
        $location->save();

        // Save an uploaded image, and set its URL into image_url.
        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $image_path = "public/events/".$event->id."/";
            $image_url = Utils::saveImage($request, $image_path, $request->image_file->getClientOriginalName(), $request->file('image_file'));
            $event->image_url = $image_url;
            $event->save();
        }
        
        // Load with the new values:
//        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->findOrFail($event->id);
        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->with('comments')->findOrFail($event->id);
        return new EventResource($event);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Event $event)
    {
        $has_permission = Utils::hasUpdatePermission($request, $event);
        if (! $has_permission) {
          return response()->json(['error' => 'You do not have update permission.'], 403);
        }

        $location = $event->location;
        $event->delete();
        $location->delete();

        return response()->json(null, 204);
    }

    public function post_going(Request $request, Event $event)
    {
        // Only self can say if going/notgoing.
        if (!$event->users_going()->find($request->user()->id)) {
            $event->users_going()->attach($request->user()->id);
        }

        // Get all the joined foreign key fields (with a read permission check):
//        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->findOrFail($event->id);
        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->with('comments')->findOrFail($event->id);
        return new EventResource($event);
    }

    public function post_notgoing(Request $request, Event $event)
    {
        // Only self can say if going/notgoing.
        if ($event->users_going()->find($request->user()->id)) {
            $event->users_going()->detach($request->user()->id);
        }

        // Get all the joined foreign key fields (with a read permission check):
//        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->findOrFail($event->id);
        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->with('comments')->findOrFail($event->id);
        return new EventResource($event);

    }

    public function post_alerting(Request $request, Event $event)
    {
        // Only self can say if going/notgoing.
        if (!$event->users_alerting()->find($request->user()->id)) {
            $event->users_alerting()->attach($request->user()->id);
        }

        // Get all the joined foreign key fields (with a read permission check):
//        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->findOrFail($event->id);
        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->with('comments')->findOrFail($event->id);
        return new EventResource($event);
    }

    public function post_notalerting(Request $request, Event $event)
    {
        // Only self can say if going/notgoing.
        if ($event->users_alerting()->find($request->user()->id)) {
            $event->users_alerting()->detach($request->user()->id);
        }

        // Get all the joined foreign key fields (with a read permission check):
//        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->findOrFail($event->id);
        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->with('comments')->findOrFail($event->id);
        return new EventResource($event);
    }

    public function post_comment(Request $request, Event $event)
    {
	// If this user can see the Event (it's readable()), they have
	// permission to comment on it.
        $event = Event::readable($request->user()->id)->findOrFail($event->id);
	if ($event == NULL) {
        	return response()->json(['error' => 'You do not have comment permission.'], 403);
	}

        // Ignore any request input 'user_id', and force it to be the creator's id:
        // We have to do it here because it's a fillable[] value for easier INSERTs.
        $request->merge(['user_id' => $request->user()->id]);

	// Add the posted comment:
        $comment = new Comment(); // This empty instance is just to call getFillable()
        $comment = Comment::create( $request->only($comment->getFillable()) );
        $comment->save();
        $event->comments()->attach($comment->id);

        $event = Event::readable($request->user()->id)->with('users_going')->with('users_alerting')->with('comments')->findOrFail($event->id);
        return new EventResource($event);
    }

}

