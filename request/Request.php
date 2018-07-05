<?php
/**
 * Created by PhpStorm.
 * User: Isigar
 * Date: 7/2/2018
 * Time: 10:53 AM
 */

namespace Relisoft\GraphQL\Request;


use EUAutomation\GraphQL\Client;
use Nette\Http\Session;
use Nette\SmartObject;
use Nette\Utils\Json;
use Relisoft\GraphQL\Parser\Parser;
use Tracy\Debugger;

class Request
{
    use SmartObject;

    private $client;
    private $auth;
    private $token;
    private $autoAuth = false;
    private $session;
    private $section;

    /** @var string Key for auth */
    private $appKey;

    public $onCall;
    public $onAuth;

    public function __construct($url,$authUrl = null, Session $session)
    {
        $this->client = new Client($url);
        if($authUrl)
            $this->auth = new Client($authUrl);

        $this->session = $session;
        $this->section = $session->getSection(md5($this->getAppKey()));
        /**
         * Try use old token
         */
        if($this->section->token){
            $this->token = $this->section->token;
        }
    }

    /**
     * @throws \Exception
     */
    public function auth($parserType = Parser::UNIVERSAL_QUERY){
        Debugger::timer("auth");
        $body = [
            "cmd" => "jwt",
            "key" => "appKey",
            "params" => [
                "appKey" => $this->getAppKey(),
                "" => [
                    "body"
                ]
            ]
        ];
        $parser = new Parser($parserType,$body);
        $rendered = $parser->render();
        $response = $this->auth->raw($rendered,[],$this->headers());
        $body = $response->getBody()->getContents();


        $callTime = Debugger::timer("auth");
        if($error = $this->hasErros($body)){
            $this->onAuth($callTime,$error);
            return false;
        }else{
            if($res = $this->validResponse($body)){
                $this->onAuth($callTime,$res);
                $this->token = $res["jwt"]["body"];
                $this->section->token = $res["jwt"]["body"];
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
        Debugger::timer("call");
        if($this->autoAuth){
            if($this->token){
                $parser = new Parser($parserType,$query);
                $rendered = $parser->render();
                $response = $this->client->raw($rendered,$variables,$this->headers());
                $body = $response->getBody()->getContents();

                $callTime = Debugger::timer("call");

                if($error = $this->hasErros($body)){
                    $this->onCall($callTime,$error);
                    return $error;
                }else{
                    $data = $this->validResponse($body);
                    if($data){
                        $this->onCall($callTime,$data);
                        return $data;
                    }else{
                        return false;
                    }
                }
            }else{
                if($this->auth()){
                    $parser = new Parser($parserType,$query);
                    $rendered = $parser->render();
                    $response = $this->client->raw($rendered,$variables,$this->headers());
                    $body = $response->getBody()->getContents();
                    $callTime = Debugger::timer("call");
                    if($error = $this->hasErros($body)){
                        $this->onCall($callTime,$error);
                        return $error;
                    }else{
                        $data = $this->validResponse($body);
                        if($data){
                            $this->onCall($callTime,$data);
                            return $data;
                        }else{
                            return false;
                        }
                    }
                }else{
                    throw new \Exception("Authorization failed!");
                }
            }
        }else{
            $parser = new Parser($parserType,$query);
            $rendered = $parser->render();
            $response = $this->client->raw($rendered,$variables,$this->headers());
            $body = $response->getBody()->getContents();
            $callTime = Debugger::timer("call");
            if($error = $this->hasErros($body)){
                $this->onCall($callTime,$error);
                return $error;
            }else{
                $data = $this->validResponse($body);
                if($data){
                    $this->onCall($callTime,$data);
                    return $data;
                }else{
                    return false;
                }
            }
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

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @param Client $auth
     */
    public function setAuth($auth)
    {
        $this->auth = $auth;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getAppKey()
    {
        return $this->appKey;
    }

    /**
     * @param string $appKey
     */
    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;
    }
}