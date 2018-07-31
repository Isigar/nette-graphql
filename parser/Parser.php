<?php
/**
 * Created by PhpStorm.
 * User: Isigar
 * Date: 7/3/2018
 * Time: 8:40 AM
 */

namespace Relisoft\GraphQL\Parser;


use Latte\Engine;
use Relisoft\GraphQL\DI\GraphQLException;
use Tracy\Debugger;

class Parser
{
    const UNIVERSAL_GETTER = "universalGetter.latte";
    const UNIVERSAL_SETTER = "universalSetter.latte";
    const UNIVERSAL_QUERY = "universalQuery.latte";
    const UNIVERSAL_SETTER_SELECTION = "universalSetterSelection.latte";

    private $type;
    private $body;

    private $customUniqParams = [];

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
            case self::UNIVERSAL_SETTER_SELECTION:
                $this->setType(self::UNIVERSAL_SETTER_SELECTION);
                break;
            default:
                $this->setType(self::UNIVERSAL_GETTER);
                break;
        }
    }

    public function render(){
       try{
           $engine = new Engine();
           $params = ["body" => $this->body];
           $params["parser"] = $this;
           $string = $engine->renderToString(__DIR__."/".$this->getType(),$params);
           return $string;
       }catch (\Exception $e){
           throw new GraphQLException('Cant parse body!');
       }
    }

    public function uniqParamTypes($key, $val){
        if($val == null){
            return "null";
        }

        if(!empty($this->getCustomUniqParams())){
            foreach ($this->getCustomUniqParams() as $customerParam){
                if($key == $customerParam){
                    return $val;
                }else{
                    continue;
                }
            };

            switch ($key){
                case "language":
                    return $val;
                case "id_customer":
                    return $val;
                case "eventName":
                    return $val;
                case "type":
                    return $val;
                case "id_translation":
                    return $val;
                case "id":
                    return $val;
                default:
                    return '"'.$val.'"';
            }
        }else{
            switch ($key){
                case "language":
                    return $val;
                case "id_customer":
                    return $val;
                case "eventName":
                    return $val;
                case "type":
                    return $val;
                case "id_translation":
                    return $val;
                case "id":
                    return $val;
                default:
                    return '"'.$val.'"';
            }
        }
    }

    /**
     * @return array
     */
    public function getCustomUniqParams(): array
    {
        return $this->customUniqParams;
    }

    /**
     * @param array $customUniqParams
     */
    public function setCustomUniqParams(array $customUniqParams): void
    {
        $this->customUniqParams = $customUniqParams;
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