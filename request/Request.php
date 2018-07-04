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
    private $response;
    private $headers;
    private $auth;

    public function __construct($url)
    {
        $this->client = new Client($url);
    }

    public function auth(){
        $body = [
            "cmd" => "jwt",
        ];
        $this->call($body,[],Parser::UNIVERSAL_SETTER);
    }

    public function call($query, $variables = [], $parserType){
        $parser = new Parser($parserType,$query);
        $rendered = $parser->render();
        $response = $this->client->response($rendered,$variables,$this->headers());
        return $response;
    }

    public function headers(){
        return [
            'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhcHBLZXkiOiJpZCB2ZW5pYW0gcGVyc3BpY2lhdGlzIGV0IG5pc2kgYXV0IGNvcnBvcmlzIiwia2V5IjoiZGV2LXRlc3QiLCJpZF9jdXN0b21lciI6MSwiaWRfdXNlciI6MzcsImlhdCI6MTUyOTQxNDk2NX0.VfT4sy_Um13kxRKMYYGKKdz7ruiv7JXSD4hSi44Dn2k'
        ];
    }
}