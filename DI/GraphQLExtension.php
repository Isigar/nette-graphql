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
        'host' => 'http://tcluster.appelis-app.cz/',
        'port' => null,
        'url' => 'api/v1/translations/graphql',
    ];

    public function loadConfiguration()
    {

        $config = $this->validateConfig($this->defaults,$this->config);
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix("request"))
            ->setFactory(Request::class,[$this->makeUrl($config)]);
        parent::loadConfiguration();

    }

    protected function makeUrl($config){
        if(is_null($config['port'])){
            return $config['host']."/".$config["url"];
        }else{
            return $config['host'].":".$config['port']."/".$config["url"];
        }
    }
}