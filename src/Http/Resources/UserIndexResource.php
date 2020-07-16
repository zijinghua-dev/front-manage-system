<?php


namespace App\Http\Resources;


use Zijinghua\Zbasement\Http\Resources\BaseResource;

class UserIndexResource extends BaseResource
{
    /**
     * 将资源集合转换成数组
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return UserResource::collection($this->collection);
    }

    /**
     * 返回应该和资源一起返回的其他数据数组
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return $this->messageBody;
    }
}