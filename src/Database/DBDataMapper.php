<?php

namespace Database;

use PDO;

class DBDataMapper
{
    //Provides a data wrapper service for Database interactions

    /** @var PDO pdo */
    private $pdo;

    public function __construct(bool $debug = false, PDO $pdo = null)
    {
        if (null === $pdo) {
            //future Swap on debug to localhost
            $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

            $servername = $url["host"];
            $username = $url["user"];
            $password = $url["pass"];
            $db = substr($url["path"], 1);
            $dsn = $url['scheme'].':dbname=' .$db.';host='.$servername; // . '?' .$url['query'];

            // Create connection
            try {
                $attributes = $debug ? array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION) : null;
                $pdo = new PDO($dsn, $username, $password, $attributes);
            } catch (PDOException $e) {
                die ('Database Connection failed in create: ' . $e->getMessage());
            }
        }
        $this->pdo = $pdo;
    }

    public function getUserByUsername(string $username) {
        $query =  'SELECT * FROM `usertable` WHERE username = :un';
        $result = false;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':un' => strtolower($username)
            ));

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Getting user failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        return $result;
    }

    public function getFoodItemsByUserID(int $id)
    {
        $query = 'SELECT `expirydate`,`category`,`foodid`,`name`,`description`,`latit`,`longit`,`amount`,
                  `weight` ,`image`,`active`,`hidden`
                    FROM `itemtable`
                    WHERE `userid` = :id';
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':id' => $id
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Getting auth token failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        return $result;
    }

    public function getFoodItemByID($id)
    {
        $query = 'SELECT `expirydate`,`category`,`userid`,`name`,`description`,`latit`,`longit`,`amount`,
                  `weight` ,`image`,`active`,`hidden`
                    FROM `itemtable`
                    WHERE `foodid` = :id';
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':id' => $id
            ));

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Getting auth token failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        if (false !== $result){
            return $result;
        }
        return false;
    }

    public function addNewFoodItem($name, $expirDate, $category, $userID, $desc, $lat, $long, $amount, $weight, $image)
    {
        $query = 'INSERT INTO itemtable (name, expirydate, category,userid,description,latit,longit,amount,weight,image)
        VALUES (:name, :expir, :cat, :uid, :desc, :lat, :long, :amount, :weight, :image)';
        $result = true;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':name' => $name,
                ':expir' => $expirDate,
                ':cat' => $category,
                ':uid' => $userID,
                ':desc' => $desc,
                ':lat' => $lat,
                ':long' => $long,
                ':amount' => $amount,
                ':weight' => $weight,
                ':image' => $image
            ));
        } catch (PDOException $e) {
            if (DEBUG) echo 'Adding new user failed: ' . $e->getMessage();
            $result = false;
        }
        $stmt = NULL;
        return $result;
    }

    //Never call directly, simply inserts values. Use handler
    public function addNewUser($un,$pw,$pic,$email, $roles = 'ROLE_BASIC')
    {
        $query = 'INSERT INTO usertable (username, password, picture, email, roles)
                  VALUES (:un, :pw, :pic, :email, :role)';
        $result = true;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':un' => $un,
                ':pw' => $pw,
                ':pic' => $pic,
                ':email' => $email,
                ':role' => $roles
            ));
        } catch (PDOException $e) {
            if (DEBUG) echo 'Adding new user failed: ' . $e->getMessage();
            $result = false;
        }
        $stmt = NULL;
        return $result;
    }

    public function addNewUserMessage($message, $sender, $receiver)
    {
        $query = 'INSERT INTO messagetable (message, time) VALUES (:msg, NOW());';
        $query .= 'INSERT INTO usermessagetable (messageid, sender, receiver) VALUES (LAST_INSERT_ID(), :send, :rec)';
        $result = true;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':msg' => $message,
                ':send' => $sender,
                ':rec' => $receiver
            ));
        } catch (PDOException $e) {
            if (DEBUG) echo 'Adding new user failed: ' . $e->getMessage();
            $result = false;
        }
        $stmt = NULL;
        return $result;
    }

    public function getUserMessagesByID($id)
    {
        $query = 'SELECT `messagetable`.`message`,`messagetable`.`time`, `usermessagetable`.`sender`, `usermessagetable`.`receiver`
                    FROM `messagetable`
                        INNER JOIN `usermessagetable`
                        ON `messagetable`.`messageid` = `usermessagetable`.`messageid`
                    WHERE (`sender` = :id) OR (`receiver` = :id)';
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':id' => $id
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Get user messages failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        return $result;
    }

    public function getUserMessagesByRequestID($userID, $requestID)
    {
        $query = "SELECT `messagetable`.`message`, `messagetable`.`time`, `usermessagetable`.`sender`, `usermessagetable`.`receiver`
                    FROM `messagetable`, `requestmessagetable`, `usermessagetable`
                    WHERE `requestmessagetable`.`requestid` = ':requestID'
                    AND `requestmessagetable`.`messageid` = `messagetable`.`messageid`
                    AND `requestmessagetable`.`messageid` = `usermessagetable`.`messageid`
                    AND (`usermessagetable`.`sender` = ':userID' OR `usermessagetable`.`receiver` = ':userID')";
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':userID' => $userID,
                ':requestID' => $requestID
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Get user messages failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        return $result;
    }

    public function getPasswordByID($id)
    {
        $query = 'SELECT `password`
                    FROM `usertable`
                    WHERE `userid` = :id';
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':id' => $id
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Getting password failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        return $result;
    }

    public function getPictureByID($id)
    {
        $query = 'SELECT `picture`
                    FROM `usertable`
                    WHERE `userid` = :id';
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':id' => $id
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Getting password failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        return $result;
    }

    public function getEmailByID($id)
    {      $query = 'SELECT `email`
                    FROM `usertable`
                    WHERE `userid` = :id';
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':id' => $id
            ));

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Getting email failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        if (false !== $result){
            return $result['email'];
        }
        return false;

    }

    public function addNewRequest($requester, $foodid)
    {
        $query = 'INSERT INTO requesttable (requester, foodid) VALUES (:req, :food)';
        $result = true;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':req' => $requester,
                ':food' => $foodid
            ));
        } catch (PDOException $e) {
            if (DEBUG) echo 'Adding new request failed: ' . $e->getMessage();
            $result = false;
        }
        $stmt = NULL;
        return $result;
    }

    public function addNewRequestMessage($message, $sender, $requestID)
    {
        $query = 'INSERT INTO messagetable (message, time) VALUES (:msg, NOW());';
        $query .= 'INSERT INTO requestmessagetable (messageid, sender, requestid) VALUES (LAST_INSERT_ID(), :send, :reqid)';
        $result = true;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':msg' => $message,
                ':send' => $sender,
                ':reqid' => $requestID
            ));
        } catch (PDOException $e) {
            if (DEBUG) echo 'Adding new request message failed: ' . $e->getMessage();
            $result = false;
        }
        $stmt = NULL;
        return $result;
    }

    //Instate as boolean
    public function setRequestState($requestid, $instate)
    {
        $state = $instate ? 1 : 0;
        $query = 'UPDATE `requesttable` SET `accepted` = :state WHERE `requestid` = :reqid';
        $result = true;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':reqid' => $requestid,
                ':state' => $state
            ));
        } catch (PDOException $e) {
            if (DEBUG) echo 'Set request state failed: ' . $e->getMessage();
            $result = false;
        }
        $stmt = NULL;
        return $result;
    }

    public function getRequestsSentByUserID($id)
    {
        $query = "SELECT `requestid`, `foodid`, `accepted`
                    FROM `requesttable`
                    WHERE `requester` = :id";
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':id' => $id
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Getting requests by ID failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        return $result;
    }

    public function updateRoles($userID, $newRoles){
        $query = 'UPDATE `usertable` SET `roles` = :roles WHERE `userid` = :uid';
        $result = true;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':uid' => $userID,
                ':roles' => $newRoles
            ));
        } catch (PDOException $e) {
            if (DEBUG) echo 'Update roles failed: ' . $e->getMessage();
            $result = false;
        }
        $stmt = NULL;
        return $result;
    }

    public function addToken($userID, $token) :bool {
        $query = 'INSERT INTO tokentable (`userid`, `token`) VALUES (:un, :token)';
        $result = true;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':un' => $userID,
                ':token' => $token,
            ));
        } catch (PDOException $e) {
            if (DEBUG) echo 'Adding new request message failed: ' . $e->getMessage();
            $result = false;
        }
        $stmt = NULL;
        return $result;
    }

    public function verifyToken($token) : bool {
        $query = 'SELECT `userid`
                    FROM `tokentable`
                    WHERE `token` = :token';
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(array(
                ':token' => $token
            ));
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Getting requests by ID failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        if (false !== $result){
            $userID = $result['userid'];
            if ($this->updateRoles($userID,'ROLE_USER')) {
                $query = 'DELETE
                    FROM `tokentable`
                    WHERE `userid` = :uid';
                try {
                    $stmt = $this->pdo->prepare($query);
                    $stmt->execute(array(
                        ':uid' => $userID
                    ));
                } catch (PDOException $e) {
                    if (DEBUG) echo 'Getting requests by ID failed: ' . $e->getMessage();
                }
                $stmt = NULL;
            }

            return $userID;
        }

        return $result;
    }

    public function getRequestsReceivedByUserID($id)
    {
        $query = "SELECT `requesttable`.`requestid`, `requesttable`.`foodid`, `requesttable`.`accepted`
                    FROM `requesttable`, `itemtable`
                    WHERE `requesttable`.`foodid` = `itemtable`.`foodid`
                    AND `itemtable`.`userid` = :id";
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);

            $stmt->execute(array(
                ':id' => $id
            ));

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Getting requests by ID failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        return $result;
    }

    public function getFoodBetween($start, $num)
    {
        $query = 'SELECT `foodid`
                        FROM `itemtable`
                    ORDER BY `foodid` DESC
                    LIMIT :num OFFSET :star;';
        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':num', (int)$num, PDO::PARAM_INT);
            $stmt->bindValue(':star', (int)$start, PDO::PARAM_INT);

            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Getting food between X and Y failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        return $result;
    }

    public function mainSearch($category, $search)
    {
        if ($category != "") {
            $query = "SELECT `foodid`
                        FROM `itemtable`
                        WHERE `category` = ? AND `name` LIKE ?";
            $params = array("$category", "%$search%");
        } else {
            $query = "SELECT `foodid`
                        FROM `itemtable`
                        WHERE `name` LIKE ?";
            $params = array("%$search%");
        }

        $result = NULL;
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (DEBUG) echo 'Search by category and search text failed: ' . $e->getMessage();
        }
        $stmt = NULL;
        return $result;
    }

    public function searchExtra($category, $search, $latit, $longit, $radius, $minAmount, $maxAmount, $minWeight, $maxWeight, $sort) {
      $categoryQuery = "`category` = :category";
      $distanceQuery = "`latit` <= :latit + :radius AND `latit` >= :latit - :radius AND `longit` <= :longit + :radius AND `longit` >= :longit - :radius";
      $quantityQuery = "`amount` <= :maxAmount AND `amount` >= :minAmount";
      $weightQuery = "`weight` <= :maxWeight AND `weight` >= :minWeight";

      $query = "SELECT `foodid` FROM `itemtable` WHERE `name` LIKE :search";
      $adaptedSearch = '%' . $search . '%';
      $params = array(':search' => $adaptedSearch);

      if ($category != "") {
          $params[':category'] = $category;
          $query = $query . " AND " . $categoryQuery;
      }
      if ($latit != "" && $longit != "" && $radius != "") {
          $params[':latit'] = $latit;
          $params[':longit'] = $longit;
          $params[':radius'] = $radius;
          $query = $query . " AND " . $distanceQuery;
      }
      if ($minAmount != "" && $maxAmount != "") {
          $params[':minAmount'] = $minAmount;
          $params[':maxAmount'] = $maxAmount;
          $query = $query . " AND " . $quantityQuery;
      }
      if ($minWeight != "" && $maxWeight != "") {
          $params[':minWeight'] = $minWeight;
          $params[':maxWeight'] = $maxWeight;
          $query = $query . " AND " . $weightQuery;
      }

      if (($sort == 'radius-asc' || $sort == 'radius-des')&& ($latit != "" && $longit != "")) {
          $query = $query . " ORDER BY POWER(`latit` - :latit, 2) + POWER(`longit` - :longit, 2)";
      } else if ($sort == 'amount-asc' || $sort == 'amount-des') {
          $query = $query . " ORDER BY `amount`";
      } else if ($sort == 'weight-asc' || $sort == 'weight-des') {
          $query = $query . " ORDER BY `weight`";
      } else {
          $query = $query . " ORDER BY `amount`";
      }

      if(substr($sort, -3) == "asc") {
          $query = $query . " ASC LIMIT 120";
      } else {
          $query = $query . " DESC LIMIT 120";
      }

      $result = NULL;
      try {
          $stmt = $this->pdo->prepare($query);
          $stmt->execute($params);

          $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
          if (DEBUG) echo 'Search by category and search text failed: ' . $e->getMessage();
      }
      $stmt = NULL;
      return $result;

    }

}
