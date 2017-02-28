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
    if (is_numeric($toGet)){
        $toEncode = ($database->getFoodItemsByUserID($toGet));
    } else {
        $toGet = $_GET["foodid"];
        if (is_numeric($toGet)){
            $toEncode = ($database->getFoodItemByID($toGet));
        }
    }

    echo json_encode($toEncode);
}

//HANDLE POST
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $expirDate = $_POST["expiredate"];
    $category = $_POST["category"];
    $userID = $_POST["userid"];
    $desc = $_POST["description"];
    $lat = $_POST["laitutde"];
    $long = $_POST["longitude"];
    $amount = $_POST["amount"];
    $weight = $_POST["weight"];
    $imagedir = null;

    //Check Vars
    if (!is_numeric($userID)){
        die(json_encode(array("error" => "userID incorrectly defined")));
    } elseif (!is_string($name)) {
        die(json_encode(array("error" => "name incorrectly defined")));
    } elseif (!is_string($expirDate)) {
        die(json_encode(array("error" => "expirey incorrectly defined")));
    } elseif (!is_string($category)) {
        die(json_encode(array("error" => "category incorrectly defined")));
    } elseif (!is_string($desc)) {
        die(json_encode(array("error" => "description incorrectly defined")));
    } elseif (!is_numeric($lat)) {
        die(json_encode(array("error" => "latitude incorrectly defined")));
    } elseif (!is_numeric($long)) {
        die(json_encode(array("error" => "longitude incorrectly defined")));
    } elseif (!is_numeric($amount)) {
        die(json_encode(array("error" => "amount incorrectly defined")));
    } elseif (!is_numeric($weight)) {
        die(json_encode(array("error" => "weight incorrectly defined")));
    }

    if(isset($_POST["image"])) {
        $target_dir = "images/food/";
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
			$imgdir = null;
        } else {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $imagedir)) {
                if (DEBUG) echo "The file " . basename($_FILES["fileToUpload"]["name"]) . " has been uploaded.";
            } else {
                if (DEBUG) echo "Sorry, there was an error uploading your file.";
            }
        }
    }

    if ($database->addNewFoodItem($name,$expirDate,$category,$userID,$desc,$lat,$long,$amount,$weight,$imagedir)) {
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