<?php

namespace Zijinghua\Zvoyager\App\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            'username' => $this->username,
            'unionid'     => $this->unionid,
            'email'      => $this->email,
            'type'     => $this->type,
            'mobile'   => $this->mobile,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
