<?php


namespace Zijinghua\Zvoyager\Http\Models;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Zijinghua\Zbasement\Http\Models\BaseModel;

class ResfulModel extends BaseModel
{
    protected $client;

    protected function createRestfulClient(){
        if(!$this->client){
            $this->client = new Client(['headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ]]);
//            application/x-www-form-urlencoded
        }
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->createRestfulClient();
    }

    public function connect($action,$url,$parameters){

        $parameters=['body' => json_encode($parameters)];


        $response = $this->client->$action($url, $parameters);
        $content=$response->getBody();
        $json=json_decode($content);
        if(isset($json->status)&&$json->status){
            return $json->data;
        }
    }

}