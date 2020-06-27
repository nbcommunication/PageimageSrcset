/**
 * Pageimage Srcset Debug JS
 *
 * @copyright 2020 NB Communication Ltd
 * @license Mozilla Public License v2.0 http://mozilla.org/MPL/2.0/
 *
 */

window.addEventListener("resize", PageimageSrcsetDebounce(PageimageSrcsetDebug, 256));

/**
 * Debug
 *
 */
function PageimageSrcsetDebug() {

	setTimeout(function() {

		var srcsets = document.querySelectorAll("[srcset], [data-srcset]");
		if(srcsets.length) {

			var debug = {};
			var images = [];
			var nodes = [];

			var n = srcsets.length;
			for(i = 0; i < n; i++) {

				var element = srcsets[i];
				var isImg = element.nodeName == "IMG";
				var srcset = element.getAttribute(isImg && element.hasAttribute("srcset") ? "srcset" : element.dataset.srcset);
				var data = {
					src: (isImg ?
						element.currentSrc :
						element.style.backgroundImage.replace('url("', "").replace('")', "")
					),
					node: element.nodeName.toLowerCase(),
					id: element.id,
					alt: element.getAttribute("alt"),
					srcset: (srcset ? srcset.split(", ") : null),
					sizes: element.getAttribute((isImg && element.hasAttribute("sizes") ? "sizes" : element.dataset.sizes))
				};

				if(isImg) {
					images.push(data);
				} else {
					nodes.push(data);
				}
			}

			if(images.length) debug["images"] = images;
			if(nodes.length) debug["nodes"] = nodes;
			debug["screen"] = window.innerWidth + "px" + " × " + window.innerHeight + "px";

			console.log("PageimageSrcset Debug", debug);
		}
	}, 512)
}

/**
 * Debounce
 *
 * From https://davidwalsh.name/javascript-debounce-function
 *
 * @param {Function} func The function to limit.
 * @param {number} wait The time to wait between fires.
 * @param {boolean} [immediate] trigger the function on the leading edge, instead of the trailing.
 * @return {Function}
 *
 */
function PageimageSrcsetDebounce(func, wait, immediate) {
	var timeout;
	return function() {
		var context = this, args = arguments;
		var later = function() {
			timeout = null;
			if(!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if(callNow) func.apply(context, args);
	};
}
