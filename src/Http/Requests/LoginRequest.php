<?php


namespace Zijinghua\Zvoyager\Http\Requests;


use Zijinghua\Zbasement\Http\Requests\BaseRequest;
use Zijinghua\Zvoyager\Traits\Credential;

class LoginRequest extends BaseRequest
{
    use Credential;
    protected $bread_action='login';

//    public function all($keys = null)
//    {
//        $data = parent::all($keys);
//        if(!$this->decryptedFlag){
//            foreach ($data as $key=>$value){
//                if(in_array($key,getConfigValue('zbasement.fields.auth.external'))){
//                    $data[$key] = $this->decrypt( $data[$key] );
//                    $this->decryptedFlag=true;
//                }
//            }
//        }
//
////
//        return $data;
//    }
}