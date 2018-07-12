<?php
/**
 * Created by PhpStorm.
 * User: Isigar
 * Date: 7/12/2018
 * Time: 11:30 AM
 */

namespace Relisoft\GraphQL\Request;


use Nette\Http\Session;

class Translations extends Request
{
    public function __construct($url, $authUrl = null, Session $session)
    {
        parent::__construct($url, $authUrl, $session);
    }
}