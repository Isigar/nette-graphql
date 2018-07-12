<?php
/**
 * Created by PhpStorm.
 * User: Isigar
 * Date: 7/5/2018
 * Time: 9:45 AM
 */

namespace Relisoft\GraphQL\Tracy;


use Latte\Engine;
use Latte\Helpers;
use Nette\Http\Session;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;
use Tracy\Bar;
use Tracy\Dumper;
use Tracy\IBarPanel;

class TracyExtension implements IBarPanel
{

    private $calls = [];
    private $authCalls = [];
    private $totalTime;

    private $bar;

    public function __construct(Bar $bar,Session $session)
    {
        $this->session = $session;
        $bar->addPanel($this);
        $this->bar = $bar;
    }

    /**
     * Renders HTML code for custom tab.
     * @return string
     */
    function getTab()
    {
        return Html::el("span")
            ->addHtml(Html::el("img")
                ->setAttribute("width",15)
                ->setAttribute("src","data:image/png;base64,".base64_encode(FileSystem::read(__DIR__."/icon.png"))))
            ->addHtml(Html::el("")
                ->addText(" GraphQL"));
    }

    /**
     * Renders HTML code for custom panel.
     * @return string
     */
    function getPanel()
    {
       $engine = new Engine();
       $params = [
           "totalTime" => $this->totalTime,
           "callCount" => count(Arrays::mergeTree($this->getCalls(),$this->getAuthCalls())),
           "call" => $this->getCalls(),
           "auth" => $this->getAuthCalls(),
           "helper" => function($data){
                return Dumper::toHtml($data,['collapse' => true]);
           },
           "all" => Arrays::mergeTree($this->getCalls(),$this->getAuthCalls())
       ];
       return $engine->renderToString(__DIR__."/body.latte",$params);
    }

    public function onCall($time, $body){
        $this->addCall([
            "time" => $time,
            "body" => $body,
            "type" => "call"
        ]);
        $this->totalTime += $time;
    }

    public function onInit($appkey){

    }

    public function onAuthCall($time,$body){
        $this->addAuthCall([
            "time" => $time,
            "body" => $body,
            "type" => "auth"
        ]);
        $this->totalTime += $time;
    }

    /**
     * @return mixed
     */
    public function getCalls()
    {
        return $this->calls;
    }

    /**
     * @param mixed $calls
     */
    public function setCalls($calls)
    {
        $this->calls = $calls;
    }

    /**
     * @return mixed
     */
    public function getAuthCalls()
    {
        return $this->authCalls;
    }

    /**
     * @param mixed $authCalls
     */
    public function setAuthCalls($authCalls)
    {
        $this->authCalls = $authCalls;
    }

    /**
     * @return mixed
     */
    public function getTotalTime()
    {
        return $this->totalTime;
    }

    /**
     * @param mixed $totalTime
     */
    public function setTotalTime($totalTime)
    {
        $this->totalTime = $totalTime;
    }

    public function addCall(array $call){
        $this->calls[] = $call;
    }

    public function addAuthCall(array $call){
        $this->calls[] = $call;
    }
}

