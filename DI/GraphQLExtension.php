<?php
/**
 * Created by PhpStorm.
 * User: Isigar
 * Date: 7/2/2018
 * Time: 10:40 AM
 */

namespace Relisoft\GraphQL\DI;


use Nette\DI\CompilerExtension;
use Relisoft\GraphQL\Request\Request;

class GraphQLExtension extends CompilerExtension
{
    public $defaults = [
        'host' => '',
        'port' => null,
        'url' => '',
        'auth' => '',
        'autoAuth' => true
    ];

    public function loadConfiguration()
    {
        $config = $this->validateConfig($this->defaults,$this->config);
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix("request"))
            ->setFactory(Request::class,[$this->makeUrl($config),$this->makeAuthUrl($config)])
            ->addSetup("setAutoAuth",[true]);
        parent::loadConfiguration();

    }

    protected function makeUrl($config){
        if(is_null($config['port'])){
            return $config['host']."/".$config["url"];
        }else{
            return $config['host'].":".$config['port']."/".$config["url"];
        }
    }

    protected function makeAuthUrl($config){
        if($config["auth"]){
            if(is_null($config['port'])){
                return $config['host']."/".$config["auth"];
            }else{
                return $config['host'].":".$config['port']."/".$config["auth"];
            }
        }else{
            return null;
        }
    }
}
