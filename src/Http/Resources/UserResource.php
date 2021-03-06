<?php


namespace Zijinghua\Zvoyager\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;
use Zijinghua\Zbasement\Http\Resources\BaseResource;

class UserResource extends JsonResource
{
    protected $hiddenFields=['id','password'];
    /**
     * 将资源集合转换成数组
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'=>@$this->resource['id'],
            'username'=>@$this->resource['username'],
            'email'=>@$this->resource['email'],
            'mobile'=>@$this->resource['mobile'],
            'avatar'=>@$this->resource['avatar'],
            'nickname'=>@$this->resource['nickname'],
            'created_at'=>@$this->resource['created_at'],
            'updated_at'=>@$this->resource['updated_at'],
        ];
    }

    /**
     * 返回应该和资源一起返回的其他数据数组
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
//    public function with($request)
//    {
//        return $this->messageBody;
//    }

}
