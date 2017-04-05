var components = {
    "packages": [
        {
            "name": "jquery",
            "main": "jquery-built.js"
        }
    ],
    "baseUrl": "js/"
};
if (typeof require !== "undefined" && require.config) {
    require.config(components);
} else {
    var require = components;
}
if (typeof exports !== "undefined" && typeof module !== "undefined") {
    module.exports = components;
}