<?php

function checkAuth(){
    $continue = true;
    if(!isset($_COOKIE["AuthToken"])) {
        $continue = false;
    }
    if(!isset($_COOKIE["ID"])) {
        $continue = false;
    }
    if($continue) {
        $authtoken = $_COOKIE["AuthToken"];
        $id = $_COOKIE["ID"];

        $datamapper = new DBDataMapper(getPDO());

        $array = $datamapper->getAuthTokenByID($id);

        foreach ($array as $item) {
            if (!$item["isExpired"]){
                if($item["authToken"] == $authtoken){
                    return true;
                }
            }
        }
        unset($item);
    }

    header('Location: '. LOGIN);

    return false;
}

function GUIDv4 ($trim = true)
{
    // Windows
    if (function_exists('com_create_guid') === true) {
        if ($trim === true)
            return trim(com_create_guid(), '{}');
        else
            return com_create_guid();
    }

    // OSX/Linux
    if (function_exists('openssl_random_pseudo_bytes') === true) {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Fallback (PHP 4.2+)
    mt_srand((double)microtime() * 10000);
    $charid = strtolower(md5(uniqid(rand(), true)));
    $hyphen = chr(45);                  // "-"
    $lbrace = $trim ? "" : chr(123);    // "{"
    $rbrace = $trim ? "" : chr(125);    // "}"
    $guidv4 = $lbrace.
        substr($charid,  0,  8).$hyphen.
        substr($charid,  8,  4).$hyphen.
        substr($charid, 12,  4).$hyphen.
        substr($charid, 16,  4).$hyphen.
        substr($charid, 20, 12).
        $rbrace;
    return $guidv4;
}

function hashPassword($inPassword) {
    $salt = random_bytes(22);
    $options = [
        'cost' => 9,
        'salt' => $salt
    ];

    return [
        'salt' => $salt,
        'password' => password_hash($inPassword, PASSWORD_BCRYPT, $options)
    ];
}