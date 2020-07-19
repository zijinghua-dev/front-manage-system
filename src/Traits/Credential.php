<?php


namespace Zijinghua\Zvoyager\Traits;


use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Arr;

Trait Credential
{
    protected $decryptedFlag=false;
    //解密结果有两个：null或值，null代表不正确
    protected function decrypt($encyptedId)
    {
        try {
            $decrypted = decrypt($encyptedId);
            return $decrypted;
        } catch (DecryptException $e) {
            //这里应该再包装异常，放进response格式
        }

    }

    protected function getCredentials($credentials): array
    {
        $filtedCredentials=[];
        foreach ($credentials as $field => $val) {
            if ($field == 'account') {
                $this->username = $field;
                $filtedCredentials= Arr::only($credentials, [$field, 'account']);
                break;
            }

            if (in_array($field, getConfigValue('zbasement.fields.auth.internal'))) {
                $this->username = $field;
                $filtedCredentials= Arr::only($credentials, [$field, 'password']);
                break;
            }

            if (in_array($field, getConfigValue('zbasement.fields.auth.external'))) {
                $this->username = $field;
                //测试代码，请删除
//                $id=encrypt($credentials[$field]);
                /////////////////
//                $decryptedId=$this->decrypt($credentials[$field]);
//                if(!isset($decryptedId)){
//                    return null;
//                }
                $filtedCredentials[$field]= $credentials[$field];
                break;
            }
        }

        return $filtedCredentials;
    }
}