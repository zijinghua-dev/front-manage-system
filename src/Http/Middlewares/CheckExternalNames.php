<?php


namespace Zijinghua\Zvoyager\Http\Middlewares;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use mysql_xdevapi\Exception;

class CheckExternalNames
{
    /**
     * 处理传入的请求
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $data = $request->all();
        foreach ($data as $key=>$value){
            if(in_array($key,getConfigValue('zbasement.fields.auth.external'))){
                try{
                    $data[$key] = decrypt( $data[$key] );
                }
                catch (DecryptException $e) {
                //这里应该再包装异常，放进response格式
                    throw new Exception('wechatid不正确');
            }

                $request->replace($data);
            }
        }
        return $next($request);
    }
}