<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'organizations_where_admin' => OrganizationResource::collection( $this->whenLoaded('organizations_where_admin') ),
            'organizations_where_poster' => OrganizationResource::collection($this->whenLoaded('organizations_where_poster')),
            'organizations_where_member' => OrganizationResource::collection($this->whenLoaded('organizations_where_member')),
        ]);
        return $result;
    }
}
