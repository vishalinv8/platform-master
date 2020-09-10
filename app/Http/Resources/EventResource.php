<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Start by simply including all table columns:
        $result = parent::toArray($request);

        // Next, conditionally add any belongsToMany members, but only if loaded:
        $result = array_merge($result, [
            'users_going' => UserResource::collection( $this->whenLoaded('users_going') ),
            'users_alerting' => UserResource::collection($this->whenLoaded('users_alerting')),
            'users_with_profiles_going' => UserResource::collection($this->whenLoaded('users_with_profiles_going')),
            'comments' => UserResource::collection( $this->whenLoaded('comments') ),
        ]);
        return $result;
/*
        // An example of manually specifying every field name:
        return [
            'id' => $this->id,

            'title' => $this->title,
            'description' => $this->description,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
            'user_id' => $this->user_id,
            'location_id' => $this->location_id,
            'address' => $this->location->address,
            'gender_id' => $this->gender_id,
            'age_group_id' => $this->age_group_id,
            'activity_type_id' => $this->activity_type_id,
            'skill_level_id' => $this->skill_level_id,
            'event_visibility_type_id' => $this->event_visibility_type_id,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
*/
    }
}
