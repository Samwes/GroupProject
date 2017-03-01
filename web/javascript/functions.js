function addItemToPage(name, expiredate, category, userid, description, latitude, longitude, amount, weight, image = null) {
	// Adds a food item to card-column
	/*
	EXAMPLE:
	addItemToPage("Test", "11/2/11", "bananas", 12, "Lovely Test", 54.776817, -1.574359, 12, "250g", "res/sample.jpg");
	*/
	if (image != null) {
		$('#item-cards').append('' +
			'<div class="card">'+
				'<img class="card-img-top" src="' + image + '" alt="Card image cap">' +
				'<div class="card-block">' +
					'<h4 class="card-title">' + name + '</h4>' +
					'<p class="card-text"><strong>Description: </strong>' + description + '</p>' +
					'<p class="card-text"><strong>Expiry Date: </strong>' + expiredate + '</p>' +
					'<p class="card-text"><strong>Amount: </strong>' + amount + '</p>' +
					'<p class="card-text"><strong>Weight: </strong>' + weight + '</p>' +
				'</div>' +
			'</div>');
	} else {
		$('#item-cards').append('' +
			'<div class="card">'+
				'<img class="card-img-top" src="res/sample" alt="Card image cap">' +
				'<div class="card-block">' +
					'<h4 class="card-title">' + name + '</h4>' +
					'<p class="card-text"><strong>Description: </strong>' + description + '</p>' +
					'<p class="card-text"><strong>Expiry Date: </strong>' + expiredate + '</p>' +
					'<p class="card-text"><strong>Amount: </strong>' + amount + '</p>' +
					'<p class="card-text"><strong>Weight: </strong>' + weight + '</p>' +
				'</div>' +
			'</div>');
	}

	var marker = new google.maps.Marker({
		title: name,
		position: {lat: latitude, lng: longitude},
		map: map
	});

}
