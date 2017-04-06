function addItemToPage(name, expiredate, category, userid, description, latitude, longitude, amount, weight, image = null) {
	// Adds a food item to card-column
	/*
	 EXAMPLE:
	 addItemToPage("Test", "11/2/11", "bananas", 12, "Lovely Test", 54.776817, -1.574359, 12, "250g", "res/sample.jpg");
	 */
	if (image != null) {
		$('#item-cards').append('' +
								'<div class="card">' +
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
								'<div class="card">' +
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

	let marker = new google.maps.Marker({
		title   : name,
		position: {lat: latitude, lng: longitude},
		map     : map,
	});

}

//todo move to index? or move everything needed in here. Don't split between the two

function GetURLParameter(sParam) {
	let sPageURL = window.location.search.substring(1);
	let sURLletiables = sPageURL.split('&');
	for (let i = 0; i < sURLletiables.length; i++) {
		let sParameterName = sURLletiables[i].split('=');
		if (sParameterName[0] === sParam) {
			return sParameterName[1];
		}
	}
}

function refreshSearch() {
	let search = GetURLParameter("search");

	if (search) {
		resultsSoFar = 0;

		let category = GetURLParameter("category");
		let latitude = GetURLParameter("latitude");
		let longitude = GetURLParameter("longitude");
		let radius = GetURLParameter("radius");
		let minQuantity = GetURLParameter("minQuantity");
		let maxQuantity = GetURLParameter("maxQuantity");
		let minWeight = GetURLParameter("minWeight");
		let maxWeight = GetURLParameter("maxWeight");
		let sort = "amount-des";

		if ($("#radius-sort-icon").hasClass('fa')) {
			// Check if asc or desc
			sort = $("#radius-sort-icon").hasClass('fa-sort-asc') ? "radius-asc" : "radius-des";
		} else if ($("#quantity-sort-icon").hasClass('fa')) {
			sort = $("#quantity-sort-icon").hasClass('fa-sort-asc') ? "amount-asc" : "amount-des";
		} else if ($("#weight-sort-icon").hasClass('fa')) {
			sort = $("#weight-sort").hasClass('fa-sort-asc') ? "weight-asc" : "weight-des";
		}
		// If not exist then set to "" else replace "+" with " "
		search = search.replace("+", " ");
		category = (!category) ? "" : category.replace("+", " ");
		if (!latitude) {latitude = "";}
		if (!longitude) {longitude = "";}
		radius = (!radius) ? "" : $("#radius-popover").val("Radius: " + radius + "km ");
		if (!minQuantity) {minQuantity = "";}
		if (!maxQuantity) {maxQuantity = "";}
		if (!minWeight) {minWeight = "";}
		if (!maxWeight) {maxWeight = "";}

		let query = "/search/" + category + "/" + search + "/" + latitude + "/" + longitude + "/" + radius + "/" + minQuantity + "/" + maxQuantity + "/" + minWeight + "/" + maxWeight + "/" + sort;
		// Set Category and Search on page
		$("#main-search-input").val(search);
		$("#categories-dropdown").val(category);

		// Make sure columns are empty
		$("#item-cards").empty();
		resultsSoFar = 0;
		$.getJSON(query, function (data) {
			results = data;
			// Data is list of relevant items
			$.each(data, function (index, array) {
				$.get("/food/html/" + array["foodid"], function (html) {
					$("#item-cards").append(html);
				});
			});
		});
	} else {
		addMoreItems();
	}
}

function addMoreItems() {
	let search = GetURLParameter("search");
	if (search) {
		$("#loading-icon").removeClass("hidden-xs-up");
		let target = Math.max(resultsSoFar + 12, results.length);
		for (let i = resultsSoFar; i < target; i++) {
			if ((i < results.length) && !$("#loading-icon").hasClass("hidden-xs-up")) {
				$.get("/food/html/" + results[i]["foodid"], function (html) {
					$("#item-cards").append(html);
				});
				resultsSoFar += 1;
			}
		}
		$("#loading-icon").addClass("hidden-xs-up");
	} else {
		let query = /food/ + resultsSoFar + "/12";

		$.getJSON(query, function (data) {
			results = results.concat(data);
			// Data is list of relevant items
			$.each(data, function (index, array) {
				$.get("/food/html/" + array["foodid"], function (html) {
					$("#item-cards").append(html);
				});
			});
		});
	}
}
