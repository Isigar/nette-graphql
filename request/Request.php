<?php
/**
 * Created by PhpStorm.
 * User: Isigar
 * Date: 7/2/2018
 * Time: 10:53 AM
 */

namespace Relisoft\GraphQL\Request;


use EUAutomation\GraphQL\Client;
use Nette\Utils\Json;
use Relisoft\GraphQL\Parser\Parser;
use Tracy\Debugger;

class Request
{
    private $client;
    private $auth;
    private $token;
    private $autoAuth = false;

    /** @var string Key for auth */
    private $appKey;

    public function __construct($url,$authUrl = null)
    {
        $this->client = new Client($url);
        if($authUrl)
            $this->auth = new Client($authUrl);
    }

    /**
     * @throws \Exception
     */
    public function auth($parserType = Parser::UNIVERSAL_QUERY){
        $body = [
            "cmd" => "jwt",
            "key" => "appKey",
            "params" => [
                "appKey" => "someString",
                "" => [
                    "body"
                ]
            ]
        ];
        $parser = new Parser($parserType,$body);
        $rendered = $parser->render();
        $response = $this->auth->raw($rendered,[],$this->headers());
        $body = $response->getBody()->getContents();

        if($this->hasErros($body)){
            return false;
        }else{
            if($res = $this->validResponse($body)){
                $this->token = $res["body"];
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * @param $query
     * @param array $variables
     * @param $parserType
     * @return \EUAutomation\GraphQL\Response
     * @throws \Exception
     */
    public function call($query, $variables = [], $parserType){
        if($this->autoAuth){
            if($this->token){
                $parser = new Parser($parserType,$query);
                $rendered = $parser->render();
                $response = $this->client->response($rendered,$variables,$this->headers());
                return $response;
            }else{
                if($this->auth()){
                    $parser = new Parser($parserType,$query);
                    $rendered = $parser->render();
                    $response = $this->client->response($rendered,$variables,$this->headers());
                    return $response;
                }else{
                    throw new \Exception("Authorization failed!");
                }
            }
        }else{
            $parser = new Parser($parserType,$query);
            $rendered = $parser->render();
            $response = $this->client->response($rendered,$variables,$this->headers());
            return $response;
        }
    }

    public function headers(){
        if($this->auth){
            if($this->token){
                return [
                    'Authorization' => 'Bearer '.$this->token
                ];
            }else{
                return [];
            }
        }else{
            return [];
        }
    }

    public function setAutoAuth($param){
        $this->autoAuth = $param;
    }


    public function validResponse($response){
        $array = Json::decode($response,Json::FORCE_ARRAY);
        if(in_array("data",array_keys($array))){
            if(is_null($array["data"])){
                return false;
            }else{
                return $array["data"];
            }
        }else{
            return false;
        }
    }

    /**
     * @param $response
     * @return bool
     * @throws \Nette\Utils\JsonException
     */
    public function hasErros($response){
        $array = Json::decode($response,Json::FORCE_ARRAY);
        if(in_array("errors",array_keys($array))){
            return $array["errors"];
        }else{
            return false;
        }
    }
}