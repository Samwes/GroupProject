function getItem(id) {
	$.get("/food/html/" + id, function (html) {
		//$("#item-cards").append(html);
		let card = $($.parseHTML(html)).appendTo('#item-cards');
		results.id = card;

		let cardMarker = newMarker(Number(card.attr("data-latit")),Number(card.attr('data-longit')));
		cardMarker.id = id;
		//Clickable = false
		card[0].marker = cardMarker;

	});
}

function highlightCard(id){
	//$("#item-cards").addClass('fadeContainer');
	$('#items-fade').removeClass('d-none');
	results.id.addClass('fadeItem');
}

function resetCardHighlight(){
	$("#item-cards").find('.fadeItem').removeClass('fadeItem');

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

	//future keep results?
	results = {};

	$("#item-cards").empty();

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
	search = (!search) ? "" : search.replace("+", " ");
	category = (!category) ? "" : category.replace("+", " ");
	if (!latitude) {latitude = "";}
	if (!longitude) {longitude = "";}
	radius = (!radius) ? "300" : radius;
	if (!minQuantity) {minQuantity = "";}
	if (!maxQuantity) {maxQuantity = "";}
	if (!minWeight) {minWeight = "";}
	if (!maxWeight) {maxWeight = "";}

	let query = "/search/" + category + "/" + search + "/" + latitude + "/" + longitude + "/" + radius + "/" + minQuantity + "/" + maxQuantity + "/" + minWeight + "/" + maxWeight + "/" + sort;
	// Set Category and Search on page
	$("#categories-dropdown").val(category);

	// Make sure columns are empty
	$("#item-cards").empty();
	$.getJSON(query, function (data) {
		// Data is list of relevant items
		$.each(data, function (index, array) {
			getItem(array.foodid);
		});
	});
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
