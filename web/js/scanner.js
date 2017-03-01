$(function() {
    var App = {
        init: function() {
            App.attachListeners();
        },
        attachListeners: function() {
            var self = this;

            $("input[type=file]").on("change", function(e) {
                if (e.target.files && e.target.files.length) {
                    // Set Default Settings
                    self.setState(self._convertNameToState("decoder_readers"), "ean"); // Set barcode type to EAN
      						  self.setState(self._convertNameToState("input-stream_size"), "800"); // Set resolution (long side) to 800px
      						  self.setState(self._convertNameToState("locator_patch-size"), "large"); // Set patch size to large
      						  self.setState(self._convertNameToState("locator_half-sample"), "false"); // Set half sample to false (boolean instead?)
      						  self.setState(self._convertNameToState("input-stream_single-channel"), "false"); // Set single channel to false (boolean instead?)
      						  self.setState(self._convertNameToState("numOfWorkers"), "1"); // Set number of workser to be 1 (Integer instead?)
                    App.decode(URL.createObjectURL(e.target.files[0]));
                }
            });

						/* // Reruns photo taken
            $(".controls button").on("click", function(e) {
                var input = document.querySelector(".controls input[type=file]");
                if (input.files && input.files.length) {
                    App.decode(URL.createObjectURL(input.files[0]));
                }
            });
						*/

						/* // Changes settings from settings group
            $(".controls .reader-config-group").on("change", "input, select", function(e) {
                e.preventDefault();
                var $target = $(e.target),
                    value = $target.attr("type") === "checkbox" ? $target.prop("checked") : $target.val(),
                    name = $target.attr("name"),
                    state = self._convertNameToState(name);

                console.log("Value of "+ state + " changed to " + value);
                self.setState(state, value);
            });
						*/

						// Set settings
						// self.setState(self._convertNameToState(name), value);

            /*if (!settingsSet) {
              self.setState(self._convertNameToState("decoder_readers"), "ean"); // Set barcode type to EAN
  						self.setState(self._convertNameToState("input-stream_size"), "800"); // Set resolution (long side) to 800px
  						self.setState(self._convertNameToState("locator_patch-size"), "large"); // Set patch size to large
  						self.setState(self._convertNameToState("locator_half-sample"), "false"); // Set half sample to false (boolean instead?)
  						self.setState(self._convertNameToState("input-stream_single-channel"), "false"); // Set single channel to false (boolean instead?)
  						self.setState(self._convertNameToState("numOfWorkers"), "1"); // Set number of workser to be 1 (Integer instead?)
              settingsSet = true;
            }*/

            /*$(document).ready(function() {
              self.setState(self._convertNameToState("decoder_readers"), "ean"); // Set barcode type to EAN
  						self.setState(self._convertNameToState("input-stream_size"), "800"); // Set resolution (long side) to 800px
  						self.setState(self._convertNameToState("locator_patch-size"), "large"); // Set patch size to large
  						self.setState(self._convertNameToState("locator_half-sample"), "false"); // Set half sample to false (boolean instead?)
  						self.setState(self._convertNameToState("input-stream_single-channel"), "false"); // Set single channel to false (boolean instead?)
  						self.setState(self._convertNameToState("numOfWorkers"), "1"); // Set number of workser to be 1 (Integer instead?)
            })*/

        },
        _accessByPath: function(obj, path, val) {
            var parts = path.split('.'),
                depth = parts.length,
                setter = (typeof val !== "undefined") ? true : false;

            return parts.reduce(function(o, key, i) {
                if (setter && (i + 1) === depth) {
                    o[key] = val;
                }
                return key in o ? o[key] : {};
            }, obj);
        },
        _convertNameToState: function(name) {
            return name.replace("_", ".").split("-").reduce(function(result, value) {
                return result + value.charAt(0).toUpperCase() + value.substring(1);
            });
        },
        detachListeners: function() {
						// Stop listening for events
            $("input[type=file]").off("change");
            /*$(".controls .reader-config-group").off("change", "input, select");
            $(".controls button").off("click");*/
        },
        decode: function(src) {
            var self = this,
                config = $.extend({}, self.state, {src: src});

            Quagga.decodeSingle(config, function(result) {});
        },
        setState: function(path, value) {
            var self = this;

            if (typeof self._accessByPath(self.inputMapper, path) === "function") {
                value = self._accessByPath(self.inputMapper, path)(value);
            }

            self._accessByPath(self.state, path, value);

            console.log(JSON.stringify(self.state));
            App.detachListeners();
            App.init();
        },
        inputMapper: {
            inputStream: {
                size: function(value){
                    return parseInt(value);
                }
            },
            numOfWorkers: function(value) {
                return parseInt(value);
            },
            decoder: {
                readers: function(value) {
                    if (value === 'ean_extended') {
                        return [{
                            format: "ean_reader",
                            config: {
                                supplements: [
                                    'ean_5_reader', 'ean_2_reader'
                                ]
                            }
                        }];
                    }
                    return [{
                        format: value + "_reader",
                        config: {}
                    }];
                }
            }
        },
        state: {
            inputStream: {
                size: 800,
                singleChannel: false
            },
            locator: {
                patchSize: "medium",
                halfSample: true
            },
            decoder: {
                readers: [{
                    format: "code_128_reader",
                    config: {}
                }]
            },
            locate: true,
            src: null
        }
    };

    App.init();

    function calculateRectFromArea(canvas, area) {
        var canvasWidth = canvas.width,
            canvasHeight = canvas.height,
            top = parseInt(area.top)/100,
            right = parseInt(area.right)/100,
            bottom = parseInt(area.bottom)/100,
            left = parseInt(area.left)/100;

        top *= canvasHeight;
        right = canvasWidth - canvasWidth*right;
        bottom = canvasHeight - canvasHeight*bottom;
        left *= canvasWidth;

        return {
            x: left,
            y: top,
            width: right - left,
            height: bottom - top
        };
    }

    Quagga.onProcessed(function(result) {
        /* CHANGE THIS TO DO STUFF WHEN IMAGE HAS BEEN PROCESSED */
				// Draws Processed Image
        /*var drawingCtx = Quagga.canvas.ctx.overlay,
            drawingCanvas = Quagga.canvas.dom.overlay,
            area;

        if (result) {
            if (result.boxes) {
                drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
                result.boxes.filter(function (box) {
                    return box !== result.box;
                }).forEach(function (box) {
                    Quagga.ImageDebug.drawPath(box, {x: 0, y: 1}, drawingCtx, {color: "green", lineWidth: 2});
                });
            }

            if (result.box) {
                Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "#00F", lineWidth: 2});
            }

            if (result.codeResult && result.codeResult.code) {
                Quagga.ImageDebug.drawPath(result.line, {x: 'x', y: 'y'}, drawingCtx, {color: 'red', lineWidth: 3});
            }

            if (App.state.inputStream.area) {
                area = calculateRectFromArea(drawingCanvas, App.state.inputStream.area);
                drawingCtx.strokeStyle = "#0F0";
                drawingCtx.strokeRect(area.x, area.y, area.width, area.height);
            }

        }*/

        $("#code").text("Unable To Detect Barcode In This Image");

    });

    Quagga.onDetected(function(result) {
        /* CHANGE THIS TO DO STUFF WHEN CODE HAS BEEN FOUND */


				// Draws image if detected
        /*var code = result.codeResult.code,
            $node,
            canvas = Quagga.canvas.dom.image;

        $node = $('<li><div class="thumbnail"><div class="imgWrapper"><img /></div><div class="caption"><h4 class="code"></h4></div></div></li>');
        $node.find("img").attr("src", canvas.toDataURL());
        $node.find("h4.code").html(code);
        $("#result_strip ul.thumbnails").prepend($node);*/

        var code = result.codeResult.code;
				$("#code").text(code);

        getDataOpenFoodFacts(code, function(data) {
          console.log(data);
        	$("#name").text("Name: " + data["name"]);
          $("#description").text("Description: " + data["description"]);
          $("#category").text("Category: " + data["categoriesHierarchy"][0].substring(3));
          $("#weight").text("Weight: " + data["weight"]);
          $("#image").attr("src", data["image"]);
        });
    });
});
