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
use Relisoft\GraphQL\DI\GraphQLException;
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

    private $uniqParams = [];

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
            if($this->section->oldAppKey == $this->getAppKey()){
                $this->token = $this->section->token;
            }else{
                $this->token = null;
            }
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
        if(!empty($this->uniqParams)){

            $parser->setCustomUniqParams($this->uniqParams);
        }
        $rendered = $parser->render();
        try{
            $response = $this->auth->raw($rendered,[],$this->headers());
            $body = $response->getBody()->getContents();
        }catch (\Exception $e){
            throw new GraphQLException("Cant get data! Error: ".$e->getMessage());
        }

        $callTime = Debugger::timer("auth");
        if($error = $this->hasErros($body)){
            try{
                $this->onAuth($callTime,$error);

                if($error[0]["code"] == 401){
                    throw new GraphQLException("Server return code 401! Bad data provided!");
                }elseif($error[0]["code"] == 403){
                    throw new GraphQLException("Server return code 403! Missing authorization token!");
                }elseif($error[0]["code"] == 503){
                    throw new GraphQLException("Server return code 503! Authorization failed!");
                }
            }catch (GraphQLException $e){
                throw $e;
            } catch (\Exception $e){
                throw new GraphQLException("Cant call onAuth callback!");
            }
            return false;
        }else{
            if($res = $this->validResponse($body)){
                try{
                    $this->onAuth($callTime,$res);
                    $this->token = $res["jwt"]["body"];
                    $this->section->token = $res["jwt"]["body"];
                }catch (\Exception $e){
                    throw new GraphQLException("Can't assign token or call onAuth callback!");
                }
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
    public function call($query, $variables = [], $parserType = Parser::UNIVERSAL_GETTER){
        Debugger::timer("call");
        if($this->autoAuth){
            if($this->token){
                $parser = new Parser($parserType,$query);
                if(!empty($this->uniqParams)){
                    $parser->setCustomUniqParams($this->uniqParams);
                }
                $rendered = $parser->render();

                try{
                    $response = $this->client->raw($rendered,$variables,$this->headers());
                    $body = $response->getBody()->getContents();
                }catch (\Exception $e){
                    throw new GraphQLException("Cant get data! Error: ".$e->getMessage());
                }

                if($body[0]["code"] == 401){
                    throw new GraphQLException("Server return code 401! Bad data provided!");
                }elseif($body[0]["code"] == 403){
                    $this->auth();
                }elseif($body[0]["code"] == 503){
                    throw new GraphQLException("Server return code 503! Authorization failed!");
                }

                $callTime = Debugger::timer("call");

                if($error = $this->hasErros($body)){
                    try{
                        $this->onCall($callTime,$error);
                    }catch (\Exception $e){
                        throw new GraphQLException("Cant call onAuth callback!");
                    }
                    return $error;
                }else{
                    try{
                        $data = $this->validResponse($body);
                        if($data){
                            $this->onCall($callTime,$data);
                            return $data;
                        }else{
                            return false;
                        }
                    }catch (\Exception $e){
                        throw new GraphQLException("Cant get data or call onCall callback!");
                    }
                }
            }else{
                if($this->auth()){
                    $parser = new Parser($parserType,$query);
                    if(!empty($this->uniqParams)){
                        $parser->setCustomUniqParams($this->uniqParams);
                    }
                    $rendered = $parser->render();

                    try{
                        $response = $this->client->raw($rendered,$variables,$this->headers());
                        $body = $response->getBody()->getContents();
                    }catch (\Exception $e){
                        throw new GraphQLException("Cant get data or call onCall callback!");
                    }

                    $callTime = Debugger::timer("call");
                    if($error = $this->hasErros($body)){
                        try{
                            $this->onCall($callTime,$error);
                        }catch (\Exception $e){
                            throw new GraphQLException("Cant call onCall callback!");
                        }
                        return $error;
                    }else{
                        try{
                            $data = $this->validResponse($body);
                            if($data){
                                $this->onCall($callTime,$data);
                                return $data;
                            }else{
                                return false;
                            }
                        }catch (\Exception $e){
                            throw new GraphQLException("Cant get data or call onCall callback!");
                        }
                    }
                }else{
                    throw new \Exception("Authorization failed!");
                }
            }
        }else{
            $parser = new Parser($parserType,$query);
            if(!empty($this->uniqParams)){
                $parser->setCustomUniqParams($this->uniqParams);
            }
            $rendered = $parser->render();
            try{
                $response = $this->client->raw($rendered,$variables,$this->headers());
                $body = $response->getBody()->getContents();
            }catch (\Exception $e){
                throw new GraphQLException("Cant get data or call onCall callback!");
            }

            $callTime = Debugger::timer("call");

            if($error = $this->hasErros($body)){
                try{
                    $this->onCall($callTime,$error);
                }catch (\Exception $e){
                    throw new GraphQLException("Cant call onCall callback!");
                }
                return $error;
            }else{
                try{
                    $data = $this->validResponse($body);
                    if($data){
                        $this->onCall($callTime,$data);
                        return $data;
                    }else{
                        return false;
                    }
                }catch (\Exception $e){
                    throw new GraphQLException("Cant get data or call onCall callback!");
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

    public function extendUniqParams(array $params){
        $this->uniqParams = $params;
    }

    /**
     * @return array
     */
    public function getUniqParams(): array
    {
        return $this->uniqParams;
    }

    /**
     * @param array $uniqParams
     */
    public function setUniqParams(array $uniqParams): void
    {
        $this->uniqParams = $uniqParams;
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
        $this->section->oldAppKey = $this->appKey;
        $this->appKey = $appKey;
        $this->section->appKey = $appKey;
    }
}