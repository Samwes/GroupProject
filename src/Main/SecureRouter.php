<?php


namespace Main;

use Silex\Route;

class SecureRouter extends Route
{
    use Route\SecurityTrait;
}