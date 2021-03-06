/*
GET FUNCTIONS:
- getFoodItemsByUserID(userid)
- getFoodItemByID(foodid)

POST FUNCTIONS:
- addNewFoodItem(name, expiredate, category, userid, description, latitude, longitude, amount, weight, image = null)
- addNewUser(username, password, picture, email)
*/

// HTTP GET FUNCTIONS
//TODO: update Handlers to point to correct routes (repeat)
function getFoodItemsByUserID(userid) {
  // Wrapper function - gets food items submitted by userid
  $.getJSON("Handlers/food.php", {"userid": userid}, function(data) {
		return data;
	});
}

function getFoodItemByID(foodid) {
  // Wrapper function - gets food item with id foodid
  $.getJSON("Handlers/food.php", {"foodid": foodid}, function(data) {
		return data;
	});
}


// HTTP POST FUNCTIONS

function addNewFoodItem(name, expiredate, category, userid, description, latitude, longitude, amount, weight, image = null) {
  // Wrapper function - adds a new food item to database
  if (null === image) {
    $.post("Handlers/food.php", {"name": name, "expiredate": expiredate, "category": category, "userid": userid, "description": description, "latitude": latitude, "longitude": longitude, "amount": amount, "weight": weight}, function(data) {
      return data;
    });
  } else {
    $.post("Handlers/food.php", {"name": name, "expiredate": expiredate, "category": category, "userid": userid, "description": description, "latitude": latitude, "longitude": longitude, "amount": amount, "weight": weight, "image": image}, function(data) {
      return data;
    });
  }
}

/**
 * @deprecated Using security system instead
 */
function addNewUser(username, password, picture, email) {
  // Wrapper function - adds a new user to database
    //TODO: Image!
  $.post("Handlers/users.php", {"username": username, "password": password, "picture": picture, "email": email}, function(data) {
    return data;
  });
}
