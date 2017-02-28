<?php


namespace Handler;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Controller
{
    protected $db;

    public function __construct(DBDataMapper $db)
    {
        $this->db = $db;
    }

    //TODO: Request handling functions go here
    public function indexGet(Request $request , Application $app) {

        return null; //TODO: This is an error
    }

}