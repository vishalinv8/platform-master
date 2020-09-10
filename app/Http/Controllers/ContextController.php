<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\ActivityType;
use App\AgeGroup;
use App\Gender;
use App\SkillLevel;
use App\PostStatus;
use App\EnrollmentPolicy;
use App\OrganizationPostStatus;


class ContextController extends Controller
{
	public function __construct()
	{
			$this->middleware('auth:api');
	}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [
            'activity_types' => ActivityType::all(),
            'age_groups' => AgeGroup::all(),
            'genders' => Gender::all(),
            'skill_levels' => SkillLevel::all(),
            'post_statuses' => PostStatus::all(),
            'organization_post_statuses' => OrganizationPostStatus::all(),
            'enrollment_policies' => EnrollmentPolicy::all(),
        ];
        
        // Add the 'data' wrapper like the other JSON responses:
        $result = [
            'data' => $data,
        ];
        return $result;
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
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
