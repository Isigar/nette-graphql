<?php
/**
 * Created by PhpStorm.
 * User: Isigar
 * Date: 7/12/2018
 * Time: 12:44 PM
 */

namespace Relisoft\GraphQL\Request;


use Nette\Http\Session;

class MainRequest extends Request
{
    public function __construct($url, $authUrl = null, Session $session)
    {
        parent::__construct($url, $authUrl, $session);
    }
}