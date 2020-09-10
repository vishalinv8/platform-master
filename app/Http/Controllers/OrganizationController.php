<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Organization;
use App\Location;
use App\User;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;

use App\Utils;
use Illuminate\Support\Facades\DB;


class OrganizationController extends Controller
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
        // NOTE: All organizations are readable by default. This just gets table joins (location, etc.) in a single query.
        $query_builder = Organization::readable($request->user()->id, $request->near_lat, $request->near_lon);

        // Word searches are table-OR'd: any match in any table, but must have a match.
        $columns_to_search = [ 'name', 'description', 'url', 'phone', 'organization_email', 
            'twitter', 'instagram', 'facebook', 'image_url', 'video_url' ];
        Utils::whereWordSearch($query_builder, $request, $columns_to_search);
        $columns_to_search = [ 'cross_street', 'address', 'address2', 'city', 'state', 'country', 'postal_code', 'cc' ];
        Utils::orWhereWordSearchBelongsToTable($query_builder, $request, $columns_to_search, 'locations', 'location_id');
        $columns_to_search = [ 'name' ];
        Utils::orWhereWordSearchBelongsToTable($query_builder, $request, $columns_to_search, 'users', 'user_id');

        // Operator searches are table-AND'd: must match across all tables where the search column is present.
        $columns_to_search = ['name', 'description', 'url', 'phone', 'organization_email', 
            'twitter', 'instagram', 'facebook', 'image_url', 'video_url', 'user_id', 'enrollment_policy_id', ];
        Utils::whereOperatorSearch($query_builder, $request, $columns_to_search);
        $columns_to_search = [ 'cross_street', 'address', 'address2', 'city', 'state', 'country', 'postal_code', 'cc', 'latitude', 'longitude' ];
        Utils::whereOperatorSearchBelongsToTable($query_builder, $request, $columns_to_search, 'locations', 'location_id');

        // Also search the computed field (which is not a table column):
        if ($request->has('max_miles')) {
            $query_builder->maxMiles($request->max_miles, $request->near_lat, $request->near_lon);            
        }

        $collection = OrganizationResource::collection(
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
        //
        // Any authorized user can create an organization.
        //

        // Ignore any request input 'user_id', and force it to be the creator's id:
        if ($request->has('user_id')) {
            $request->merge(['user_id' => $request->user()->id]);
        }

        $location = new Location(); // This empty instance is just to call getFillable()
        $location = Location::create( $request->only($location->getFillable()) );
        $location->save();
        
        // Set the location_id, now that we know it:
        $request->merge(['location_id' => $location->id]);

        $organization = new Organization(); // This empty instance is just to call getFillable() 
        $organization = Organization::create( $request->only($organization->getFillable()) );
        $organization->save();

        // Save an uploaded image, and set its URL into image_url.
        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $image_path = "public/organizations/".$organization->id."/";
            $image_url = Utils::saveImage($request, $image_path, $request->image_file->getClientOriginalName(), $request->file('image_file'));
            $organization->image_url = $image_url;
            $organization->save();
        }

        // Get all the joined foreign key fields, and check permissions:
        $organization = Organization::readable($request)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Organization $organization)
    {
        // Get all the joined foreign key fields, and check permissions:
        $organization = Organization::readable($request)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Organization $organization)
    {
        // Owner/Creator, or an organization admin, can update the organization.
        // Except for the user_id field (ownership) -- only the current
        // owner/creator user_id can transfer ownership to another user_id...
        $has_permission = false;
        if ( $request->user()->id == $organization->user_id  || 
             $organization->admins()->find($request->user()->id) ) {
            $has_permission = true;
        }

        if (! $has_permission) {
          return response()->json(['error' => 'You do not have update permission.'], 403);
        }

        // If it's not the owner, then don't allow user_id updates. Essentially this
        // only allows the current owner to "give away" the organization to another user_id.
        // If it's not the owner, we force it to remain the same.
        // We have to do it here because it's a fillable[] value for easier INSERTs.
        if (! ($request->user()->id == $user->id) ) {
            // Ignore any request input 'user_id', and force it to be the creator's id.
            if ($request->has('user_id')) {
                $request->merge(['user_id' => $organization->user_id]);
            }
        }

        $organization->fill( $request->only($organization->getFillable()) );
        $organization->save();

        $location = $organization->location;
        $location->fill( $request->only($location->getFillable()) );
        $location->save();
        
        // Save an uploaded image, and set its URL into image_url.
        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $image_path = "public/organizations/".$organization->id."/";
            $image_url = Utils::saveImage($request, $image_path, $request->image_file->getClientOriginalName(), $request->file('image_file'));
            $organization->image_url = $image_url;
            $organization->save();
        }

        // Get all the joined foreign key fields (with a read permission check):
        $organization = Organization::readable($request->user()->id)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Organization $organization)
    {
        $has_permission = ($request->user()->id == $organization->user_id) ? true : false;
        if (! $has_permission) {
          return response()->json(['error' => 'You do not have delete permission.'], 403);
        }

        // Remove links to all admins, posters, and members.
        $organization->admins()->detach();
        $organization->posters()->detach();
        $organization->members()->detach();

        // Delete (not just detach()) any branch_locations and events:
        $organization->branch_locations()->delete();
        $organization->events()->delete();

        $location = $organization->location;
        // Delete the entry:
        $organization->delete();
        // Delete the primary location that it belongsTo:
        $location->delete();

        return response()->json(null, 204);
    }


    public function post_join(Request $request, Organization $organization, User $user)
    {
        // Self or admin can join a user, unless closed. (Then, only admin.)
        $has_permission = false;

        if ($organization->enrollment_policy->name == "closed") {
            // An admin can still join someone, without having to update the
            // enrollment status first.
            if ( $organization->admins()->find($request->user()->id) ) {
                $has_permission = true;
            }
        } else {
            // Enrollment is open. Self or admin can join a user.
            if ( $request->user()->id == $user->id  || 
                 $organization->admins()->find($request->user()->id) ) {
                $has_permission = true;
            }
        }

        if (! $has_permission) {
          return response()->json(['error' => 'You do not have permission.'], 403);
        }

        // Examine the enrollment_policy to see which role to assign
        if ($organization->enrollment_policy->name == "open_for_members") {
            if (! $organization->members->contains($user->id)) {
                $organization->members()->attach($user->id);
            }
        }
        else if ($organization->enrollment_policy->name == "open_for_posters") {
            if (! $organization->posters->contains($user->id)) {
                $organization->posters()->attach($user->id);
            }
        } else {
          return response()->json(['error' => 'Unrecognized enrollment_policy.name: ' . $organization->enrollment_policy->name], 403);
        }

        $organization = Organization::readable($request)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }

    public function post_leave(Request $request, Organization $organization, User $user)
    {
        // Self or admin can unjoin (leave) a user.
        $has_permission = false;
        if ( $request->user()->id == $user->id  || 
             $organization->admins()->find($request->user()->id) ) {
            $has_permission = true;
        }

        if (! $has_permission) {
          return response()->json(['error' => 'You do not have permission.'], 403);
        }

        // Examine the enrollment_policy to see which role to assign
        if ($organization->members()->find($user->id)) {
            $organization->members()->detach($user->id);
        }
        if ($organization->posters()->find($user->id)) {
            $organization->posters()->detach($user->id);
        }
        if ($organization->admins()->find($user->id)) {
            $organization->admins()->detach($user->id);
        }

        $organization = Organization::readable($request)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }

    public function get_members(Request $request, Organization $organization)
    {
        $query_builder = $organization->members()->summary();

        $collection = UserResource::collection(
			$query_builder->paginate(100)
        );

		return $collection;
    }

    public function post_members(Request $request, Organization $organization, User $user)
    {
        // Owner or admin can add a user.
        $has_permission = false;
        if ( $request->user()->id == $organization->user_id  || 
             $organization->admins()->find($request->user()->id) ) {
            $has_permission = true;
        }

        if (! $has_permission) {
          return response()->json(['error' => 'You do not have permission.'], 403);
        }

        if (! $organization->members->contains($user->id)) {
            $organization->members()->attach($user->id);
        }

        $organization = Organization::readable($request)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }

    public function delete_members(Request $request, Organization $organization, User $user)
    {
        // Owner or admin can remove a user.
        $has_permission = false;
        if ( $request->user()->id == $organization->user_id  || 
             $organization->admins()->find($request->user()->id) ) {
            $has_permission = true;
        }

        if (! $has_permission) {
          return response()->json(['error' => 'You do not have permission.'], 403);
        }

        $organization->members()->detach($user->id);

        $organization = Organization::readable($request)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }



    public function get_posters(Request $request, Organization $organization)
    {
        $query_builder = $organization->posters()->summary();

        $collection = UserResource::collection(
			$query_builder->paginate(100)
        );

		return $collection;
    }

    public function post_posters(Request $request, Organization $organization, User $user)
    {
        // Owner or admin can add a user.
        $has_permission = false;
        if ( $request->user()->id == $organization->user_id  || 
             $organization->admins()->find($request->user()->id) ) {
            $has_permission = true;
        }

        if (! $has_permission) {
          return response()->json(['error' => 'You do not have permission.'], 403);
        }

        if (! $organization->posters->contains($user->id)) {
            $organization->posters()->attach($user->id);
        }

        $organization = Organization::readable($request)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }

    public function delete_posters(Request $request, Organization $organization, User $user)
    {
        // Owner or admin can remove a user.
        $has_permission = false;
        if ( $request->user()->id == $organization->user_id  || 
             $organization->admins()->find($request->user()->id) ) {
            $has_permission = true;
        }

        if (! $has_permission) {
          return response()->json(['error' => 'You do not have permission.'], 403);
        }

        $organization->posters()->detach($user->id);

        $organization = Organization::readable($request)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }




    public function get_admins(Request $request, Organization $organization)
    {
        $query_builder = $organization->admins()->summary();

        $collection = UserResource::collection(
			$query_builder->paginate(100)
        );

		return $collection;
    }

    public function post_admins(Request $request, Organization $organization, User $user)
    {
        // Owner or admin can add a user.
        $has_permission = false;
        if ( $request->user()->id == $organization->user_id  || 
             $organization->admins()->find($request->user()->id) ) {
            $has_permission = true;
        }

        if (! $has_permission) {
          return response()->json(['error' => 'You do not have permission.'], 403);
        }

        if (! $organization->admins->contains($user->id)) {
            $organization->admins()->attach($user->id);
        }

        $organization = Organization::readable($request)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }

    public function delete_admins(Request $request, Organization $organization, User $user)
    {
        // Owner or admin can remove a user.
        $has_permission = false;
        if ( $request->user()->id == $organization->user_id  || 
             $organization->admins()->find($request->user()->id) ) {
            $has_permission = true;
        }

        if (! $has_permission) {
          return response()->json(['error' => 'You do not have permission.'], 403);
        }

        $organization->admins()->detach($user->id);

        $organization = Organization::readable($request)
            ->with('members')
            ->with('posters')
            ->with('admins')
            ->with('events')
            ->findOrFail($organization->id);
        return new OrganizationResource($organization);
    }



}

