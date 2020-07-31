<?php


namespace Zijinghua\Zvoyager\Http\Services;


use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Http\Contracts\ParameterServiceInterface;

class ParameterService extends BaseService implements ParameterServiceInterface
{
    public function hasId($request){
        $action=    $request['action'];
        $slug=$request['slug'];
        if(isset($request['uuid'])){
            $uuid=$request['uuid'];
            $messageResponse=$this->messageResponse($slug,$action.'.validation.success', ['field'=>'uuid','value'=>$uuid]);
            return $messageResponse;
        }else {
            if (!isset($request['id'])) {
                $messageResponse = $this->messageResponse($slug, $action . '.validation.validation.failed');
                return $messageResponse;
            }
            $messageResponse = $this->messageResponse($slug, $action . '.validation.success', ['field' => 'id', 'value' => $request['id']]);
            return $messageResponse;
        }
    }
}