<?php
header('Content-type: application/json');
header('Access-Control-Allow-Origin: *');
require_once "includes.php";

checkAuth();
$database = new MySQL_DataMapper(getPDO());

//HANDLE GET
if ($_SERVER["REQUEST_METHOD"] == "GET") {

    $toEncode = (array("error" => "failed"));

    //Get input variables
    $toGet = $_GET["userid"];
    $field = $_GET["field"];

    if (!is_numeric($toGet)) {
        die(json_encode(array("error" => "User ID incorrectly defined")));
    } elseif (!is_string($field)) {
        die(json_encode(array("error" => "Field incorrectly defined")));
    }

    if ($field === "email"){
        $toEncode = ($database->getEmailByID($toGet));
    } elseif ($field === "picture") {
        $toEncode = ($database->getPictureByID($toGet));
    }

    echo json_encode($toEncode);
}

//HANDLE POST
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {    // TODO : Check for UN already existing
    $username = $_POST["username"];
    $password = $_POST["password"];
    $picture = $_POST["picture"];
    $email = $_POST["email"];

    $imagedir = null;

    //Check Vars
    if (!is_string($username)) {
        die(json_encode(array("error" => "name incorrectly defined")));
    } elseif (!is_string($password)) {
        die(json_encode(array("error" => "expirey incorrectly defined")));
    } elseif (!is_string($email)) {
        die(json_encode(array("error" => "description incorrectly defined")));
    }

    $pass = hashPassword($password);
    $password = $pass['password'];
    $salt = $pass['salt'];

    if(isset($_POST["picture"])) {
        $target_dir = "images/user/";
        $GUID = GUIDv4();
        $imagedir = $target_dir . $GUID;
        $uploadOk = 1;
        $imageFileType = pathinfo(basename($_FILES["fileToUpload"]["name"]),PATHINFO_EXTENSION);

        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if ($check !== false) {
            if (DEBUG) echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
        } else {
            if (DEBUG) echo "File is not an image.";
            $uploadOk = 0;
        }

        if (file_exists($imagedir)) {
            if (DEBUG) echo "Sorry, file already exists.";
            $uploadOk = 0;
        }
        if ($_FILES["fileToUpload"]["size"] > 500000) {
            if (DEBUG) echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif"
        ) {
            if (DEBUG) echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }
        if ($uploadOk == 0) {
            if (DEBUG) echo "Sorry, your file was not uploaded.";
        } else {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $imagedir)) {
                if (DEBUG) echo "The file " . basename($_FILES["fileToUpload"]["name"]) . " has been uploaded.";
            } else {
                if (DEBUG) echo "Sorry, there was an error uploading your file.";
            }
        }
    }

    //TODO: Issue correct auth token?

    if ($database->addNewUser($username, $password, $imagedir, $email, $salt)) {
        $toEncode = array("success" => "topic added");
    } else {
        $toEncode = array("error" => "failed to add");
    }

    echo json_encode($toEncode);
}

//Otherwise??
else {
    echo "?";
};
?>