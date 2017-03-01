/*
GET FUNCTIONS:
- getFoodItemsByUserID(userid)
- getFoodItemByID(foodid)

POST FUNCTIONS:
- addNewFoodItem(name, expiredate, category, userid, description, latitude, longitude, amount, weight, image = null)
- addNewUser(username, password, picture, email)
*/

// HTTP GET FUNCTIONS
//TODO: Move images from res/ to an image folder. Sort this out
//TODO: Find something todo with js files. Dunno where to put em yet
//TODO: Sort out css as well (using asset() in twig)

function getFoodItemsByUserID(userid) {
  // Wrapper function - gets food items submitted by userid
  $.getJSON("handlers/food.php", {"userid": userid}, function(data) {
		return data;
	});
}

function getFoodItemByID(foodid) {
  // Wrapper function - gets food item with id foodid
  $.getJSON("handlers/food.php", {"foodid": foodid}, function(data) {
		return data;
	});
}


// HTTP POST FUNCTIONS

function addNewFoodItem(name, expiredate, category, userid, description, latitude, longitude, amount, weight, image = null) {
  // Wrapper function - adds a new food item to database
  if (null === image) {
    $.post("handlers/food.php", {"name": name, "expiredate": expiredate, "category": category, "userid": userid, "description": description, "latitude": latitude, "longitude": longitude, "amount": amount, "weight": weight}, function(data) {
      return data;
    });
  } else {
    $.post("handlers/food.php", {"name": name, "expiredate": expiredate, "category": category, "userid": userid, "description": description, "latitude": latitude, "longitude": longitude, "amount": amount, "weight": weight, "image": image}, function(data) {
      return data;
    });
  }
}

function addNewUser(username, password, picture, email) {
  // Wrapper function - adds a new user to database
    //TODO: Image!
  $.post("handlers/users.php", {"username": username, "password": password, "picture": picture, "email": email}, function(data) {
    return data;
  });
}
