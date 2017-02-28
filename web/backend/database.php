<?php

function getPDO() {
    $servername = "mysql: host=127.0.0.1;dbname=gpmain;port=3306";
    $username = "gptest";
    $password = "123";

    // Create connection
    try {
        $conn = new PDO($servername, $username, $password,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_PERSISTENT => true));
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        return NULL;
    }

    return $conn;
}
