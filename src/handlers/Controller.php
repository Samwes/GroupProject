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

    public function foodItemsGet(Request $request , Application $app, $userID) {
        $toEncode = $this->db->getFoodItemsByUserID($userID);
        if ($toEncode === null)
            $toEncode = (array("error" => "failed"));

        return new JsonResponse($toEncode);
    }

    public function foodItemGet(Request $request , Application $app, $foodID)
    {
        $toEncode = $this->db->getFoodItemByID($foodID);
        if ($toEncode === null)
            $toEncode = (array("error" => "failed"));

        return new JsonResponse($toEncode);

    }

}