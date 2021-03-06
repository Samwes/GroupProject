<?php

namespace Database;

use PDO;

class DBDataMapper
{
	//Provides a data wrapper service for Database interactions

	/** @var PDO pdo */
	private $pdo;

	public function __construct(bool $debug = false, PDO $pdo = null) {
		if (null === $pdo) {
			//future Swap on debug to localhost
			$url = parse_url(getenv("CLEARDB_DATABASE_URL"));

			$servername = $url["host"];
			$username = $url["user"];
			$password = $url["pass"];
			$db = substr($url["path"], 1);
			$dsn = $url['scheme'].':dbname='.$db.';host='.$servername; // . '?' .$url['query'];

			// Create connection
			try {
				$attributes = $debug ? array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION) : null;
				$pdo = new PDO($dsn, $username, $password, $attributes);
			} catch (\PDOException $e) {
				die('Database Connection failed in create: '.$e->getMessage());
			}
		}

		$this->pdo = $pdo;
	}

	public function getUserByUsername(string $username) {
		$query = 'SELECT * FROM `usertable` WHERE `username` = :un';
		$result = false;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':un' => strtolower($username),
						   ));

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting user failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getUserByID(int $id) {
		$query = 'SELECT * FROM `usertable` WHERE `userid` = :id';
		$result = false;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':id' => strtolower($id),
						   ));

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting user by ID failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getFoodItemsByUserID(int $id) {
		$query = 'SELECT `expirydate`,`category`,`foodid`,`name`,`description`,`latit`,`longit`,`amount`,
                  `weight` ,`image`,`active`,`hidden`
                    FROM `itemtable`
                    WHERE `userid` = :id';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':id' => $id,
						   ));

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting auth token failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getFoodItemByID($id) {
		$query = 'SELECT `expirydate`,`category`,`userid`,`name`,`description`,`latit`,`longit`,`amount`,
                  `weight` ,`image`,`active`,`hidden`
                    FROM `itemtable`
                    WHERE `foodid` = :id';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':id' => $id,
						   ));

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting auth token failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		if (false !== $result) {
			return $result;
		}
		return false;
	}

	public function acceptRequest($requestid, $requester, $foodid) {
		$query = 'UPDATE `requesttable`
				SET `status` = 2
				WHERE `requestid` = :requestid;';
		$query .= 'UPDATE `requesttable`
				SET `status` = 1
				WHERE `foodid` = :foodid AND `requestid` != :requestid;';
		$query .= 'UPDATE `itemtable`
				SET `active` = 1
				WHERE `foodid` = :foodid;';

		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':requestid' => $requestid,
								 ':foodid' => $foodid
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Updating food item failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function rejectRequest($requestid, $requester) {
		$query = 'UPDATE `requesttable`
				SET `status` = 1
				WHERE `requestid` = :requestid;';

		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':requestid' => $requestid
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Updating food item failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function updateFoodItem($foodID, $name, $expirDate, $category, $userID, $desc, $lat, $long, $amount, $weight, $image) {
		if ($image === null) {
			$image = 'none.png';
		}

		$query = 'UPDATE `itemtable`
				SET `name`=:name,`expirydate`=:expir,`category`=:cat,`description`=:desc,`latit`=:lat,`longit`=:long,`amount`=:amount,`weight`=:weight,`image`=:image
				WHERE `foodid`=:fid AND `userid`=:uid';

		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':name'   => $name,
							   ':expir'  => $expirDate,
							   ':cat'    => $category,
							   ':uid'    => $userID,
							   ':fid'    => $foodID,
							   ':desc'   => $desc,
							   ':lat'    => $lat,
							   ':long'   => $long,
							   ':amount' => $amount,
							   ':weight' => $weight,
							   ':image'  => $image,
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Updating food item failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function removeFoodItem($foodID, $userID) {
		$query = 'UPDATE `itemtable`
				SET `active` = 1, `hidden` = 1
				WHERE `foodid`=:fid AND `userid`=:uid';
		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(':fid' => $foodID, ':uid' => $userID));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Updating food item failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function addNewFoodItem($name, $expirDate, $category, $userID, $desc, $lat, $long, $amount, $weight, $image) {
		//future change default image based on category
		if ($image === null) {
			$image = 'none.png';
		}
		$query = 'INSERT INTO `itemtable` (`name`, `expirydate`, `category`,`userid`,`description`,`latit`,`longit`,`amount`,`weight`,`image`)
        VALUES (:name, :expir, :cat, :uid, :desc, :lat, :long, :amount, :weight, :image)';

		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':name'   => $name,
							   ':expir'  => $expirDate,
							   ':cat'    => $category,
							   ':uid'    => $userID,
							   ':desc'   => $desc,
							   ':lat'    => $lat,
							   ':long'   => $long,
							   ':amount' => $amount,
							   ':weight' => $weight,
							   ':image'  => $image,
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Adding new food item failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	//Never call directly, simply inserts values. Use handler
	public function addNewUser($un, $pw, $email, $roles = 'ROLE_BASIC', $pic = 'profile_default.jpg') {
		$query = 'INSERT INTO `usertable` (`username`, `password`, `picture`, `email`, `roles`)
                  VALUES (:un, :pw, :pic, :email, :role)';
		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':un'    => $un,
							   ':pw'    => $pw,
							   ':pic'   => $pic,
							   ':email' => $email,
							   ':role'  => $roles,
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Adding new user failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function addNewUserMessage($message, $sender, $receiver, $requestid) {
		$query = 'INSERT INTO `messagetable` (`message`, `time`, `user1seen`, `user2seen`) VALUES (:msg, NOW(), 1, 1);';
		$query .= 'INSERT INTO `usermessagetable` (`messageid`, `sender`, `receiver`) VALUES (LAST_INSERT_ID(), :send, :rec);';
		$query .= 'INSERT INTO `requestmessagetable` (`messageid`, `sender`, `requestid`) VALUES (LAST_INSERT_ID(), :send, :req)';
		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':msg'  => $message,
							   ':send' => $sender,
							   ':rec'  => $receiver,
								 ':req'  => $requestid
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Adding new user failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function getUserMessagesByID($id) {
		$query = 'SELECT `messagetable`.`message`,`messagetable`.`time`, `usermessagetable`.`sender`, `usermessagetable`.`receiver`
                    FROM `messagetable`
                        INNER JOIN `usermessagetable`
                        ON `messagetable`.`messageid` = `usermessagetable`.`messageid`
                    WHERE (`sender` = :id) OR (`receiver` = :id)';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':id' => $id,
						   ));

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Get user messages failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getUserMessagesByRequestID($userID, $requestID) {
		$query = "SELECT `messagetable`.`message`, `messagetable`.`time`, `usermessagetable`.`sender`, `usermessagetable`.`receiver`
                    FROM `messagetable`, `requestmessagetable`, `usermessagetable`
                    WHERE `requestmessagetable`.`requestid` = :requestID
                    AND `requestmessagetable`.`messageid` = `messagetable`.`messageid`
                    AND `requestmessagetable`.`messageid` = `usermessagetable`.`messageid`
                    AND (`usermessagetable`.`sender` = :userID OR `usermessagetable`.`receiver` = :userID)";
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':userID'    => $userID,
							   ':requestID' => $requestID,
						   ));

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Get user messages failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getPasswordByID($id) {
		$query = 'SELECT `password`
                    FROM `usertable`
                    WHERE `userid` = :id';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':id' => $id,
						   ));

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting password failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getPictureByID($id) {
		$query = 'SELECT `picture`
                    FROM `usertable`
                    WHERE `userid` = :id';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':id' => $id,
						   ));

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting password failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getEmailByID($id) {
		$query = 'SELECT `email`
                    FROM `usertable`
                    WHERE `userid` = :id';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':id' => $id,
						   ));

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting email failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		if (false !== $result) {
			return $result['email'];
		}
		return false;
	}

	public function addNewRequest($requester, $foodid) {
		$query = 'INSERT INTO `requesttable` (`requester`, `foodid`) VALUES (:req, :food)';
		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':req'  => $requester,
							   ':food' => $foodid,
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Adding new request failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function addNewRequestMessage($message, $sender, $requestID) {
		$query = 'INSERT INTO messagetable (message, time) VALUES (:msg, NOW());';
		$query .= 'INSERT INTO requestmessagetable (messageid, sender, requestid) VALUES (LAST_INSERT_ID(), :send, :reqid)';
		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':msg'   => $message,
							   ':send'  => $sender,
							   ':reqid' => $requestID,
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Adding new request message failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	//Instate as boolean
	public function setRequestState($requestid, $instate) {
		$state = $instate ? 1 : 0;
		$query = 'UPDATE `requesttable` SET `status` = :state WHERE `requestid` = :reqid';
		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':reqid' => $requestid,
							   ':state' => $state,
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Set request state failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function getRequestsSentByUserID($id) {
		// TODO Sort by most recent time
		$query = "SELECT `itemtable`.`userid`, `requesttable`.`requestid`, `requesttable`.`foodid`, `requesttable`.`status`
                    FROM `itemtable`, `requesttable`
                    WHERE `requester` = :id
										AND `requesttable`.`foodid` = `itemtable`.`foodid`";
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':id' => $id,
						   ));

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			if (DEBUG) {
				echo 'Getting requests by ID failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getRequestsReceivedByUserID($id) {
		// TODO Sort by most recent time
		$query = 'SELECT `requesttable`.`requester`, `requesttable`.`requestid`, `requesttable`.`foodid`, `requesttable`.`status`
                    FROM `requesttable`, `itemtable`
                    WHERE `requesttable`.`foodid` = `itemtable`.`foodid`
                    AND `itemtable`.`userid` = :id';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':id' => $id,
						   ));

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting requests by ID failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getRequestStatus($requestid) {
		$query = 'SELECT `status`
								FROM `requesttable`
								WHERE `requestid` = :reqid';

	$result = null;
	try {
		$stmt = $this->pdo->prepare($query);

		$stmt->execute(array(
									   ':reqid' => $requestid,
								   ));

		$result = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch (\PDOException $e) {
		if (DEBUG) {
			echo 'Getting request state by ID failed: '.$e->getMessage();
		}
	}
	$stmt = null;
	return $result;
	}

	public function getRoles($userID) {
		$query = 'SELECT `roles`
                    FROM `usertable`
                    WHERE `userid` = :id';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':id' => $userID,
						   ));

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting roles failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		if (false !== $result) {
			return $result['roles'];
		}
		return false;
	}

	public function addToken($userID, $token): bool {
		$query = 'INSERT INTO tokentable (`userid`, `token`) VALUES (:un, :token)';
		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':un'    => $userID,
							   ':token' => $token,
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Adding new request message failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function verifyToken($token): bool {
		$query = 'SELECT `userid`
                    FROM `tokentable`
                    WHERE `token` = :token';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute(array(
							   ':token' => $token,
						   ));
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting requests by ID failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		if (false !== $result) {
			$userID = $result['userid'];
			if ($this->updateRoles($userID, 'ROLE_USER')) {
				$query = 'DELETE
                    FROM `tokentable`
                    WHERE `userid` = :uid';
				try {
					$stmt = $this->pdo->prepare($query);
					$stmt->execute(array(
									   ':uid' => $userID,
								   ));
				} catch (\PDOException $e) {
					if (DEBUG) {
						echo 'Getting requests by ID failed: '.$e->getMessage();
					}
				}
				$stmt = null;
			}

			return $userID;
		}

		return $result;
	}

	public function updateRoles($userID, $newRoles) {
		$query = 'UPDATE `usertable` SET `roles` = :roles WHERE `userid` = :uid';
		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':uid'   => $userID,
							   ':roles' => $newRoles,
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Update roles failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function updateFullName($userID, $newName) {
		$query = 'UPDATE `usertable` SET `fullname` = :nme WHERE `userid` = :uid';
		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':uid' => $userID,
							   ':nme' => $newName,
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Updating username failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function updatePass($userID, $newPass) {
		//future extra token check here?
		$query = 'UPDATE `usertable` SET `password` = :pass WHERE `userid` = :uid';
		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':uid'  => $userID,
							   ':pass' => $newPass,
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Updating password failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function getFoodBetween($start, $num) {
		$query = 'SELECT `foodid`
                        FROM `itemtable`
                    ORDER BY `foodid` DESC
                    LIMIT :num OFFSET :star;';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->bindValue(':num', (int) $num, PDO::PARAM_INT);
			$stmt->bindValue(':star', (int) $start, PDO::PARAM_INT);

			$stmt->execute();

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting food between X and Y failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function mainSearch($category, $search) {
		if ($category !== '') {
			$query = 'SELECT `foodid`
                        FROM `itemtable`
                        WHERE `category` = ? AND `name` LIKE ?';
			$params = array("$category", "%$search%");
		} else {
			$query = 'SELECT `foodid`
                        FROM `itemtable`
                        WHERE `name` LIKE ?';
			$params = array("%$search%");
		}

		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute($params);

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Search by category and search text failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function searchLocation($minLat, $maxLat, $minLong, $maxLong, $category, $search, $minAmount, $maxAmount, $minWeight, $maxWeight, $start, $count) {
		$query = 'SELECT `foodid`
					FROM `itemtable`
					WHERE `latit` <= :maxLat AND `latit` >= :minLat
					AND `longit` <= :maxLong AND `longit` >= :minLong
					AND `active` = 0 AND `hidden` = 0';
		$queryEnd = " ORDER BY `foodid` DESC LIMIT $count OFFSET $start";

		$categoryQuery = '`category` = :category';
		$nameQuery = '`name` LIKE :search';
		$quantityQuery = '`amount` <= :maxAmount AND `amount` >= :minAmount';
		$weightQuery = '`weight` <= :maxWeight AND `weight` >= :minWeight';


		$additionals = array();
		$params = array(
			':maxLat' => $maxLat,
			':minLat' => $minLat,
			':maxLong' => $maxLong,
			':minLong' => $minLong,
		);

		if ($search !== '') {
			$additionals[] = $nameQuery;
			$adaptedSearch = '%'.$search.'%';
			$params[':search'] = $adaptedSearch;
		}
		if ($category !== '') {
			$params[':category'] = $category;
			$additionals[] = $categoryQuery;
		}

		$params[':maxLat'] = $maxLat;
		$params[':minLat'] = $minLat;
		$params[':maxLong'] = $maxLong;
		$params[':minLong'] = $minLong;

		if ($minAmount !== '' && $maxAmount !== '') {
			$params[':minAmount'] = $minAmount;
			$params[':maxAmount'] = $maxAmount;
			$additionals[] = $quantityQuery;
		}
		if ($minWeight !== '' && $maxWeight !== '') {
			$params[':minWeight'] = $minWeight;
			$params[':maxWeight'] = $maxWeight;
			$additionals[] = $weightQuery;
		}

		for ($x = 0, $xMax = count($additionals); $x < $xMax; $x++) {
			$query .= ' AND '.$additionals;
		}

		$query .= $queryEnd;

		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute($params);

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Searching within location boundaries failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function searchExtra($category, $search, $latit, $longit, $radius, $minAmount, $maxAmount, $minWeight, $maxWeight, $sort, $start, $count) {
		$categoryQuery = '`category` = :category';
		$nameQuery = '`name` LIKE :search';
		$distanceQuery = 'acos(sin(:lat)*sin(radians(`latit`)) + cos(:lat)*cos(radians(`latit`))*cos(radians(`longit`)-:lon)) * :R < :radius';
		$quantityQuery = '`amount` <= :maxAmount AND `amount` >= :minAmount';
		$weightQuery = '`weight` <= :maxWeight AND `weight` >= :minWeight';

		$additionals = array('`active` = 0', '`hidden` = 0');
		$r = 6371;
		$params = array(':R' => $r);
		$subquery = '`itemtable`';

		if ($search !== '') {
			$additionals[] = $nameQuery;
			$adaptedSearch = '%'.$search.'%';
			$params[':search'] = $adaptedSearch;
		}
		if ($category !== '') {
			$params[':category'] = $category;
			$additionals[] = $categoryQuery;
		}
		if ($latit !== '' && $longit !== '' && $radius !== '') {
			$radius /= 1000;
			$params[':maxLat'] = $latit + rad2deg($radius / $r);
			$params[':minLat'] = $latit - rad2deg($radius / $r);
			$params[':maxLon'] = $longit + rad2deg(asin($radius / $r) / cos(deg2rad($latit)));
			$params[':minLon'] = $longit - rad2deg(asin($radius / $r) / cos(deg2rad($latit)));
			$params[':lat'] = deg2rad($latit);
			$params[':lon'] = deg2rad($longit);
			$params[':radius'] = $radius;
			$subquery = '(SELECT * from `itemtable` WHERE
						`latit` BETWEEN :minLat and :maxLat AND
						`longit` BETWEEN :minLon and :maxLon) as FirstPass';
			$additionals[] = $distanceQuery;
		}
		if ($minAmount !== '' && $maxAmount !== '') {
			$params[':minAmount'] = $minAmount;
			$params[':maxAmount'] = $maxAmount;
			$additionals[] = $quantityQuery;
		}
		if ($minWeight !== '' && $maxWeight !== '') {
			$params[':minWeight'] = $minWeight;
			$params[':maxWeight'] = $maxWeight;
			$additionals[] = $weightQuery;
		}

		if (($sort === 'radius-asc' || $sort === 'radius-des') && ($latit !== '' && $longit !== '')) {
			$queryEnd = ' ORDER BY acos(sin(:lat)*sin(radians(`latit`)) + cos(:lat)*cos(radians(`latit`))*cos(radians(`longit`)-:lon)) * :R';
		} elseif ($sort === 'amount-asc' || $sort === 'amount-des') {
			$queryEnd = ' ORDER BY `amount`';
		} elseif ($sort === 'weight-asc' || $sort === 'weight-des') {
			$queryEnd = ' ORDER BY `weight`';
		} else {
			$queryEnd = ' ORDER BY `amount`';
		}

		if (substr($sort, -3) === 'asc') {
			$queryEnd .= " ASC LIMIT $count OFFSET $start";
		} else {
			$queryEnd .= " DESC LIMIT $count OFFSET $start";
		}

		$query = 'SELECT `foodid` FROM '.$subquery;
		if (count($additionals) > 0) {
			$query .= ' WHERE '.$additionals[0];
			for ($x = 1, $xMax = count($additionals); $x < $xMax; $x++) {
				$query .= ' AND '.$additionals[$x];
			}
		}
		$query .= $queryEnd;

		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);
			$stmt->execute($params);

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Search by category and search text failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function setMessagesSeen($requestid, $userid) {

		$query = 'UPDATE `messagetable`, `requestmessagetable`, `requesttable`
				SET `messagetable`.`user1seen` = 0
				WHERE `messagetable`.`messageid` = `requestmessagetable`.`messageid` AND
					`requestmessagetable`.`requestid` = `requesttable`.`requestid` AND
    			`requesttable`.`requester` = :requester AND `requesttable`.`requestid` = :requestid;

				UPDATE `messagetable`, `requestmessagetable`, `requesttable`
				SET `messagetable`.`user2seen` = 0
				WHERE `messagetable`.`messageid` = `requestmessagetable`.`messageid` AND
					`requestmessagetable`.`requestid` = `requesttable`.`requestid` AND
    			`requesttable`.`requester` != :requester AND `requesttable`.`requestid` = :requestid;';

		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':requester'  => $userid,
							   ':requestid' => $requestid
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Adding new user failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function getUserFoodInfo($userid, $foodid) {
		// fix to get message corresponding to time
		$query = 'SELECT DISTINCT `usertable`.`username`, MAX(`messagetable`.`time`), `messagetable`.`message`, `itemtable`.`name`, `usertable`.`picture`
				FROM `usertable`, `messagetable`, `itemtable`, `requestmessagetable`, `requesttable`
				WHERE `usertable`.`userid` = :uid AND `itemtable`.`foodid` = :fid AND
				`messagetable`.`messageid` = `requestmessagetable`.`messageid` AND
				`requestmessagetable`.`requestid` = `requesttable`.`requestid` AND
				`requesttable`.`foodid` = :fid AND
				(`requesttable`.`requester` = :uid OR (`itemtable`.`foodid` = :fid AND `itemtable`.`userid` = :uid))';

		$result = false;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':uid' => $userid,
							   ':fid' => $foodid,
						   ));

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting user food info failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getNumberUnseenMessages($requestid) {
		$query = 'SELECT SUM(`user1seen`) as `user1seen`, SUM(`user2seen`) as `user2seen`
				FROM `messagetable`, `requestmessagetable`, `requesttable`
				WHERE `messagetable`.`messageid` = `requestmessagetable`.`messageid` AND
				`requestmessagetable`.`requestid` = `requesttable`.`requestid` AND
				`requesttable`.`requestid` = :requestid';

		$result = false;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':requestid' => $requestid
						   ));

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting number of unseen messages failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getGivenAwayFoods($userid) {
		$query = 'SELECT `itemtable`.`foodid`
								FROM `itemtable`
								WHERE `itemtable`.`userid` = :userid
								AND `itemtable`.`active` = 0';
		$result = null;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':userid' => $userid,
						   ));

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting requests by ID failed: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function getNumberNotifications($userid) {
		$query = 'SELECT `requestmessagetable`.`requestid`, `messagetable`.`user2seen`
								FROM `messagetable`, `requestmessagetable`, `requesttable`
								WHERE `messagetable`.`messageid` = `requestmessagetable`.`messageid`
									AND `requestmessagetable`.`sender` != :userid
									AND `requestmessagetable`.`requestid` = `requesttable`.`requestid`
    							AND `requesttable`.`requester` = :userid';

		 $asRequester = null;
		 try {
 			$stmt = $this->pdo->prepare($query);

 			$stmt->execute(array(
 							   ':userid' => $userid,
 						   ));

 			$asRequester = $stmt->fetchAll(PDO::FETCH_ASSOC);
 		} catch (\PDOException $e) {
 			if (DEBUG) {
 				echo 'Getting requests by ID failed: '.$e->getMessage();
 			}
 		}
 		$stmt = null;

		$asRequesterResults = array();

		foreach($asRequester as $value) {
			if(array_key_exists($value['requestid'], $asRequesterResults) && $value['user2seen'] == 1) {
				$asRequesterResults[$value['requestid']] += 1;
			} else {
				$asRequesterResults[$value['requestid']] = 1;
			}
		}

		$query = 'SELECT `requestmessagetable`.`requestid`, `messagetable`.`user1seen`
								FROM `messagetable`, `requestmessagetable`, `requesttable`, `itemtable`
								WHERE `messagetable`.`messageid` = `requestmessagetable`.`messageid`
									AND `requestmessagetable`.`sender` != :userid
									AND `requestmessagetable`.`requestid` = `requesttable`.`requestid`
    							AND `requesttable`.`foodid` = `itemtable`.`foodid`
    							AND `itemtable`.`userid` = :userid';

		$asSender = null;
		try {
		 $stmt = $this->pdo->prepare($query);

		 $stmt->execute(array(
								':userid' => $userid,
							));

		 $asSender = $stmt->fetchAll(PDO::FETCH_ASSOC);
	 } catch (\PDOException $e) {
		 if (DEBUG) {
			 echo 'Getting requests by ID failed: '.$e->getMessage();
		 }
	 }
	 $stmt = null;

	 $asSenderResults = array();

	 foreach($asSender as $value) {
		 if(array_key_exists($value['requestid'], $asSenderResults) && $value['user1seen'] == 1) {
			 $asSenderResults[$value['requestid']] += 1;
		 } else {
			 $asSenderResults[$value['requestid']] = 1;
		 }
	 }

		$notifications = sizeof($asReceiverResults) + sizeof($asSenderResults);

		return array('notifications' => $notifications);
	}

	public function reviewUser($otherID, $userID, $rating) {
		$query = 'INSERT INTO `reviewtable` (`userid`, `reviewerid`, `rating`) VALUES (:userid, :reviewerid, :rating)';

		$result = true;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':userid' => $otherID,
								 ':reviewerid' => $userID,
								 ':rating' => $rating
						   ));
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Review Insertion failed: '.$e->getMessage();
			}
			$result = false;
		}
		$stmt = null;
		return $result;
	}

	public function getUserRating($userID) {
		$query = "SELECT ROUND(AVG(`rating`)) AS 'rating'
								FROM `reviewtable`
								WHERE `userid` = :userid";

		$result = false;
		try {
			$stmt = $this->pdo->prepare($query);

			$stmt->execute(array(
							   ':userid' => $userID
						   ));

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			if (DEBUG) {
				echo 'Getting rating: '.$e->getMessage();
			}
		}
		$stmt = null;
		return $result;
	}

	public function addVerification($userid) {
		// Add verification code to database in relation to userid
    }
}
