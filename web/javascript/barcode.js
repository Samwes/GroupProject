// API - https://world.openfoodfacts.org/api/v0/product/[BARCODE].json (https://world.openfoodfacts.org/product/5054402633149/potato-lattices-tesco)
// DOWNLOADABLE - http://www.product-open-data.com/
// API - http://api.upcdatabase.org/json/APIKEY/CODE APIKEY=4d9ecc9c6770cc49ff68cc386be0f7be

// USE BARCODE TO GET NAME THEN SPOONACULAR TO GET PRODUCT INFORMATION?
// FOOD NAME TO CATEGORY - https://spoonacular.com/food-api

function getDataOpenFoodFacts(barcode, callback) {
	let toReturn = {};
	$.getJSON("https://world.openfoodfacts.org/api/v0/product/"+barcode+".json", function(data) {
		//console.log(data);
		if (data.status_verbose === "product found") {
			toReturn.name = data.product.product_name_en;
			toReturn.image = data.product.image_url;
			toReturn.categoriesHierarchy = data.product.categories_hierarchy; // array
			toReturn.description = data.product.generic_name_en;
			toReturn.weight = data.product.quantity;
		}
		callback(toReturn);
	});
}

getDataOpenFoodFacts("5054402633149", function(data) {
	console.log(data);
});
getDataOpenFoodFacts("5000328741314", function(data) {
	console.log(data);
});
