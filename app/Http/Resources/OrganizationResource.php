<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
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
            'admins' => UserResource::collection( $this->whenLoaded('members') ),
            'posters' => UserResource::collection( $this->whenLoaded('posters') ),
            'members' => UserResource::collection( $this->whenLoaded('admins') ),
            'events' => UserResource::collection( $this->whenLoaded('events') ),
        ]);
        return $result;
    }
}
