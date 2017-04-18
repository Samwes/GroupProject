function getItem(id) {
	if (typeof cardResults[id] === "undefined") {
	$.get("/food/html/" + id, function (html) {
		//$("#item-cards").append(html);
		let card = $($.parseHTML(html)).appendTo('#item-cards');
		cardResults[id] = card;

		let cardMarker = newMarker(Number(card.attr("data-latit")),Number(card.attr('data-longit')));
		cardMarker.id = id;
		//cardMarker.setClickable(false);
		cardMarker.addListener('mouseover', function() {
			highlightCard(this.id);
		});
		cardMarker.addListener('mouseout', resetCardHighlight);

		card[0].marker = cardMarker;
		card[0].id = id;
		card.click(function() {
			cardClick(this.id);
		})
	});
	} else {
		$("#item-cards").append(cardResults[id]);
		addExistingMarker(cardResults[id][0].marker);
	}
}

function cardClick(id){
	//Insert + display modal here

	//$.get("/food/html/" + id, function (html) {
	//	$('#cardModalDisplay').empty().append($.parseHTML(html));
	//	$('#cardModal').modal('show');
	//});
}

function highlightCard(id){
	$('#items-fade').removeClass('d-none');
	cardResults[id].addClass('fadeItem');
}

function resetCardHighlight(){
	$("#item-cards").find('.fadeItem').removeClass('fadeItem');
	$('#items-fade').addClass('d-none');
}

//todo move to twig (like map) or into index

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

function clearCurrentCards(){
	let storage = $('#card-storage');
	$("#item-cards").children().appendTo(storage);
	clearMarkers();
}

function refreshSearch() {
	clearCurrentCards();
	doSearch(0, 12);
}

function doSearch(inStart, inCount) {
	let search = GetURLParameter("search");
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
	let input = $('#pac-input');
	if (!latitude) {latitude = input.attr("data-latitude");}
	if (!longitude) {longitude = input.attr("data-longitude");}
	radius = (!radius) ? "300" : radius;
	if (!minQuantity) {minQuantity = "";}
	if (!maxQuantity) {maxQuantity = "";}
	if (!minWeight) {minWeight = "";}
	if (!maxWeight) {maxWeight = "";}

	let query = "/search/" + category + "/" + search + "/" + latitude + "/" + longitude +
							"/" + radius + "/" + minQuantity + "/" + maxQuantity + "/" + minWeight +
							"/" + maxWeight + "/" + sort + "/" + inStart + "/" + inCount;
	// Set Category and Search on page
	$("#categories-dropdown").val(category);

	$.getJSON(query, function (data) {
		// Data is list of relevant items
		$.each(data, function (index, array) {
			getItem(array.foodid);
		});
	});
}

function addMoreItems() {
	let search = GetURLParameter("search");
	let resultsSoFar = $("#item-cards").children().size();
	doSearch(resultsSoFar, 12);
}
