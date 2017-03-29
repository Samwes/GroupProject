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

function GetURLParameter(sParam) {
	var sPageURL = window.location.search.substring(1);
	var sURLVariables = sPageURL.split('&');
	for (var i = 0; i < sURLVariables.length; i++) {
		var sParameterName = sURLVariables[i].split('=');
		if (sParameterName[0] == sParam) {
			return sParameterName[1];
		}
	}
}

function refreshSearch() {
	resultsSoFar = 0;
	var search = GetURLParameter("search");
	var category = GetURLParameter("category");
	var latitude = GetURLParameter("latitude");
	var longitude = GetURLParameter("longitude");
	var radius = GetURLParameter("radius");
	var minQuantity = GetURLParameter("minQuantity");
	var maxQuantity = GetURLParameter("maxQuantity");
	var minWeight = GetURLParameter("minWeight");
	var maxWeight = GetURLParameter("maxWeight");
	var sort = "radius";

	if(search) {
		// If not exist then set to "" else replace "+" with " "
		search = search.replace("+", " ");
		(!category) ? category = "" : category = category.replace("+", " ");
		if(!latitude) latitude = "";
		if(!longitude) longitudee = "";
		(!radius) ? radius = "" : $("#radius-popover").val("Radius: " + radius + "km ");
		if(!minQuantity) minQuantity = "";
		if(!maxQuantity) maxQuantity = "";
		if(!minWeight) minWeight = "";
		if(!maxWeight) maxWeight = "";

		// Update Button Text
		if(minQuantity && maxQuantity) {
			$("#quantity-popover").val(minQuantity + " - " + maxQuantity + " ");
		} else if(minWeight) {
			$("#quantity-popover").val(minQuantity + "+ ");
		} else if(maxQuantity) {
			$("#quantity-popover").val("Up to" + maxQuantity + " ");
		} else {
			$("#quantity-popover").val("Quantity ");
		}

		if(minWeight && maxWeight) {
			$("#weight-popover").val(minWeight + "g - " + maxWeight + "g ");
		} else if(minWeight) {
			$("#weight-popover").val(minWeight + "g+ ");
		} else if(maxWeight) {
			$("#weight-popover").val("Up to" + maxWeight + "g ");
		} else {
			$("#weight-popover").val("Weight ");
		}

		var query = "/search/" + category + "/" + search + "/" + latitude + "/" + longitude + "/" + radius + "/" + minQuantity + "/" + maxQuantity + "/" + minWeight + "/" + maxWeight + "/" + sort;
		// Set Category and Search on page
		$("#main-search-input").val(search);
		$("#categories-dropdown").val(category);

		// Make sure columns are empty
		$("#item-cards").empty();
		resultsSoFar = 0;
		$.getJSON(query, function(data) {
			results = data;
			// Data is list of relevant items
			$.each(data, function(index, array) {
				$.get("/food/html/" + array["foodid"], function(html) {
					$("#item-cards").append(html);
				});
			});
		});
	}
}

function addMoreItems() {
	$("#loading-icon").removeClass("hidden-xs-up");
	for(var i = resultsSoFar; i < 12; i++) {
		if(i < results.length && !$("#loading-icon").hasClass("hidden-xs-up")) {
			$.get("/food/html/" + results[i]["foodid"], function(html) {
				$("#item-cards").append(html);
			});
			resultsSoFar += 1;
		}
	}
	$("#loading-icon").addClass("hidden-xs-up");
}
