<?php
/**
 * Created by PhpStorm.
 * User: Isigar
 * Date: 7/3/2018
 * Time: 8:40 AM
 */

namespace Relisoft\GraphQL\Parser;


use Latte\Engine;

class Parser
{
    const UNIVERSAL_GETTER = "universalGetter.latte";
    const UNIVERSAL_SETTER = "universalSetter.latte";
    const UNIVERSAL_QUERY = "universalQuery.latte";

    private $type;
    private $body;

    public function __construct($type = self::UNIVERSAL_GETTER,$body = [])
    {
        $this->body = $body;
        switch ($type){
            case self::UNIVERSAL_GETTER:
                $this->setType(self::UNIVERSAL_GETTER);
                break;
            case self::UNIVERSAL_SETTER:
                $this->setType(self::UNIVERSAL_SETTER);
                break;
            case self::UNIVERSAL_QUERY:
                $this->setType(self::UNIVERSAL_QUERY);
                break;
            default:
                $this->setType(self::UNIVERSAL_GETTER);
                break;
        }
    }

    public function render(){
        $engine = new Engine();
        $params = ["body" => $this->body];
        $params["parser"] = $this;
        $string = $engine->renderToString(__DIR__."/".$this->getType(),$params);
        return $string;
    }

    public function uniqParamTypes($key, $val){
        switch ($key){
            case "language":
                return $val;
            case "id_customer":
                return $val;
            default:
                return '"'.$val.'"';
        }
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }


}