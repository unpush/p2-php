/**
 * LIMELIGHT: it will move you to love, laughter and tears.
 */

// {{{ Limelight

/**
 * The Limelight object.
 *
 * @constructor
 * @param {Object} options
 */
var Limelight = function(options) {
	this.init(options);
	this.init = null;
};

// }}}
// {{{ constants

/**
 * The directions.
 *
 * @type {Object}
 */
Limelight.DIRECTION = {
	/**#@+ @type {Number} */
	NONE: 0,
	FORWARD: 1,
	BACKWARD: -1,
	UPWARD: -2,
	DOWNWARD: 2
	/**#@-*/
};

/**
 * The origins.
 *
 * @type {Object}
 */
Limelight.ORIGIN = {
	/**#@+ @type {Number} */
	TOP: -2,
	MIDDLE: 0,
	BOTTOM: 2,
	LEFT: -1,
	CENTER: 0,
	RIGHT: 1
	/**#@-*/
};

// }}}
// {{{ Exception

/**
 * The exceptions object.
 *
 * @constructor
 * @param {String} type
 * @param {String} message
 */
Limelight.Exception = function(type, message) {
	this.type = type;
	this.message = message;
};

// }}}
// {{{ Exception.toString()

/**
 * Returns the string representation of the exception.
 *
 * @param void
 * @return {String}
 */
Limelight.Exception.prototype.toString = function() {
	return 'Limelight Exception (' + this.type + ') ' + this.message;
};

// }}}
// {{{ Point

/**
 * The object to represent a point.
 *
 * @constructor
 * @param {Number} x
 * @param {Number} y
 */
Limelight.Point = function(x, y) {
	this.x = x;
	this.y = y;
};

// }}}
// {{{ Point.clone()

/**
 * Clones the point object.
 *
 * @param void
 * @return {Limelight.Point}
 */
Limelight.Point.prototype.clone = function() {
	return new Limelight.Point(this.x, this.y);
};

// }}}
// {{{ Point.toString()

/**
 * Returns the string representation of the point.
 *
 * @param void
 * @return {String}
 */
Limelight.Point.prototype.toString = function() {
	return '[' + this.x.toString() + ',' + this.y.toString() + ']';
};

// }}}
// {{{ Rect

/**
 * The object to represent a rectangle.
 *
 * @constructor
 * @param {Limelight.Point|Number[]} topLeft
 * @param {Limelight.Point|Number[]} bottomRight
 */
Limelight.Rect = function(topLeft, bottomRight) {
	if (typeof topLeft.push == 'function') {
		this.topLeft = new Limelight.Point(topLeft[0], topLeft[1]);
	} else {
		this.topLeft = topLeft.clone();
	}
	if (typeof bottomRight.push == 'function') {
		this.bottomRight = new Limelight.Point(bottomRight[0], bottomRight[1]);
	} else {
		this.bottomRight = bottomRight.clone();
	}
};

// }}}
// {{{ Rect.clone()

/**
 * Clones the rectangle object.
 *
 * @param void
 * @return {Limelight.Rect}
 */
Limelight.Rect.prototype.clone = function() {
	return new Limelight.Rect(this.topLeft, this.bottomRight);
};

// }}}
// {{{ Rect.toString()

/**
 * Returns the string representation of the rectangle.
 *
 * @param void
 * @return {String}
 */
Limelight.Rect.prototype.toString = function() {
	return '[' + this.topLeft.toString() + ',' + this.bottomRight.toString() + ']';
};

// }}}
// {{{ Rect.contains()

/**
 * Determines whether contains a given point.
 *
 * @param {Limelight.Point|Number[]} point
 * @return {Boolean}
 */
Limelight.Rect.prototype.contains = function(point) {
	var x, y;

	if (typeof point.push == 'function') {
		x = point[0];
		y = point[1];
	} else {
		x = point.x;
		y = point.y;
	}

	if (x < this.topLeft.x || y < this.topLeft.y) {
		return false;
	} else if (x > this.bottomRight.x || y > this.bottomRight.y) {
		return false;
	} else {
		return true;
	}
};

// }}}
// {{{ Rect.moveTo()

/**
 * Moves the rectangle.
 *
 * @param {Limelight.Point|Number[]} point
 * @param {Number} xOrigin (optional)
 * @param {Number} yOrigin (optional)
 * @return {Limelight.Rect}
 */
Limelight.Rect.prototype.moveTo = function(point, xOrigin, yOrigin) {
	var x, y, width, height;

	if (typeof point.push == 'function') {
		x = point[0];
		y = point[1];
	} else {
		x = point.x;
		y = point.y;
	}

	width = this.bottomRight.x - this.topLeft.x;
	height = this.bottomRight.y - this.topLeft.y;

	if (xOrigin === Limelight.ORIGIN.RIGHT) {
		this.topLeft.x = x - width;
	} else if (xOrigin === Limelight.ORIGIN.CENTER) {
		this.topLeft.x = x - Math.floor(width / 2);
	} else {
		this.topLeft.x = x;
	}
	this.bottomRight.x = this.topLeft.x + width;

	if (yOrigin ===  Limelight.ORIGIN.BOTTOM) {
		this.topLeft.y = y - height;
	} else if (yOrigin === Limelight.ORIGIN.MIDDLE) {
		this.topLeft.y = y - Math.floor(height / 2);
	} else {
		this.topLeft.y = y;
	}
	this.bottomRight.y = this.topLeft.y + height;

	return this;
};

// }}}
// {{{ Rect.resizeTo()

/**
 * Resizes the rectangle.
 *
 * @param {Number} width
 * @param {Number} height
 * @param {Number} xOrigin (optional)
 * @param {Number} yOrigin (optional)
 * @return {Limelight.Rect}
 */
Limelight.Rect.prototype.resizeTo = function(width, height, xOrigin, yOrigin) {
	if (xOrigin === Limelight.ORIGIN.RIGHT) {
		this.topLeft.x = this.bottomRight.x - width;
	} else if (xOrigin === Limelight.ORIGIN.CENTER) {
		this.topLeft.x = this.bottomRight.x - Math.floor(width / 2);
	}
	this.bottomRight.x = this.topLeft.x + width;

	if (yOrigin === Limelight.ORIGIN.BOTTOM) {
		this.topLeft.y = this.bottomRight.y - height;
	} else if (yOrigin === Limelight.ORIGIN.MIDDLE) {
		this.topLeft.y = this.bottomRight.y - Math.floor(height / 2);
	}
	this.bottomRight.y = this.topLeft.y + height;

	return this;
};

// }}}
// {{{ Button

/**
 * The button object.
 *
 * @constructor
 * @param {String} label
 */
Limelight.Button = function(label) {
	this.element = document.createElement('span');
	this.element.className = 'limelight-button';
	this.label = this.element.appendChild(document.createTextNode(label));
};

// }}}
// {{{ Button.isTargetOf()

/**
 * Determines whether the target of a given event is the receiver.
 *
 * @param {Event} event
 * @return {Boolean}
 */
Limelight.Button.prototype.isTargetOf = function(event) {
	if (event.target.isSameNode(this.element) || event.target.isSameNode(this.label)) {
		return true;
	} else {
		return false;
	}
};

// }}}
// {{{ Button.setLabel()

/**
 * Sets the button label.
 *
 * @param {String} label
 * @return void
 */
Limelight.Button.prototype.setLabel = function(label) {
	if (this.label.nodeValue != label) {
		this.label.nodeValue = label;
	}
};

// }}}
// {{{ Button.attachEvent()

/**
 * Attaches the event handler.
 *
 * @param {String} eventName
 * @param {Function(Event)} callback
 * @return {Function(Event)}
 */
Limelight.Button.prototype.attachEvent = function(eventName, callback) {
	this.element.addEventListener(eventName, callback, false);
	return callback;
};

// }}}
// {{{ Button.detachEvent()

/**
 * Detaches the event handler.
 *
 * @param {String} eventName
 * @param {Function(Event)} callback
 * @return {Function(Event)}
 */
Limelight.Button.prototype.detachEvent = function(eventName, callback) {
	this.element.removeEventListener(eventName, callback, false);
	return callback;
};

// }}}
// {{{ Toolbar

/**
 * The toolbar object.
 *
 * @constructor
 * @param void
 */
Limelight.Toolbar = function() {
	var element = document.createElement('table');
	element.className = 'limelight-toolbar';
	element.setAttribute('cellspacing', '0');

	var row = element.appendChild(document.createElement('tbody'))
	                 .appendChild(document.createElement('tr'));

	var closeButton = new Limelight.Button('close');
	row.appendChild(document.createElement('td'))
	   .appendChild(closeButton.element);

	var fitButton = new Limelight.Button('fit');
	row.appendChild(document.createElement('td'))
	   .appendChild(fitButton.element);

	var fillButton = new Limelight.Button('fill');
	row.appendChild(document.createElement('td'))
	   .appendChild(fillButton.element);

	var origButton = new Limelight.Button('1:1');
	row.appendChild(document.createElement('td'))
	   .appendChild(origButton.element);

	this.element = element;
	this.closeButton = closeButton;
	this.fitButton = fitButton;
	this.fillButton = fillButton;
	this.origButton = origButton;
};

// }}}
// {{{ Toolbar.show()

/**
 * Shows the toolbar.
 *
 * @param void
 * @return void
 */
Limelight.Toolbar.prototype.show = function() {
	this.element.style.display = 'table';
};

// }}}
// {{{ Toolbar.hide()

/**
 * Hides the toolbar.
 *
 * @param void
 * @return void
 */
Limelight.Toolbar.prototype.hide = function() {
	this.element.style.display = 'none';
};

// }}}
// {{{ Toolbar.peekaboo()

/**
 * Toggles the toolbar visibility.
 *
 * @param void
 * @return {Boolean}
 */
Limelight.Toolbar.prototype.peekaboo = function() {
	if (this.element.style.display == 'none') {
		this.show();
		return true;
	} else {
		this.hide();
		return false;
	}
};

// }}}
// {{{ Slide

/**
 * The slide object.
 *
 * @constructor
 * @param void
 */
Limelight.Slide = function() {
	/**
	 * The image array.
	 *
	 * @type {Object[]}
	 */
	this.images = [];

	/**
	 * The length of the image array.
	 *
	 * @type {Number}
	 */
	this.length = 0;

	/**
	 * The current position of the image array.
	 *
	 * @type {Number}
	 */
	this.cursor = 0;

	/**
	 * The callback function which is called when trying to
	 * show the previous image at the top of the slide.
	 *
	 * @type {Function(Limelight, Limelight.Slide)}
	 */
	this.onNoPrev = null;

	/**
	 * The callback function which is called when trying to
	 * show the next image at the end of the slide.
	 *
	 * @type {Function(Limelight, Limelight.Slide)}
	 */
	this.onNoNext = null;
};

// }}}
// {{{ Slide.clone()

/**
 * Clones the slide object.
 *
 * @param void
 * @return {Limelight.Slide}
 */
Limelight.Slide.prototype.clone = function() {
	var slide, image, cursor;

	slide = new Limelight.Slide();

	cursor = this.cursor;
	image = this.reset();
	while (image) {
		slide.addImage(image.uri, image.title);
		image = this.next();
	}
	this.cursor = cursor;

	slide.onNoPrev = this.onNoPrev;
	slide.onNoNext = this.onNoNext;

	return slide;
};

// }}}
// {{{ Slide.addImage()

/**
 * Appends the image to the slide.
 *
 * @param {String} uri
 * @param {String} title
 * @return {Number}
 */
Limelight.Slide.prototype.addImage = function(uri, title) {
	this.images.push({
		'uri': uri,
		'title': (typeof title == 'string' && title.length) ? title : null
	});
	return this.length = this.images.length;
};

// }}}
// {{{ Slide.setCursor()

/**
 * Sets the cursor position and gets the image at the cursor position.
 *
 * @param {Number} position
 * @param {Boolean} isRelative
 * @return {Object|null}
 */
Limelight.Slide.prototype.setCursor = function(position, isRelative) {
	if (isRelative) {
		position = this.cursor + position;
	} else if (position < 0) {
		position = this.length + position;
	}

	if (position >= 0 && position < this.length) {
		this.cursor = position;
		return this.images[position];
	} else {
		return null;
	}
};

// }}}
// {{{ Slide.current()

/**
 * Gets the image at the current cursor position.
 *
 * @param void
 * @return {Object|null}
 */
Limelight.Slide.prototype.current = function() {
	return this.setCursor(0, true);
};

// }}}
// {{{ Slide.next()

/**
 * Increments the cursor and gets the next image.
 *
 * @param void
 * @return {Object|null}
 */
Limelight.Slide.prototype.next = function() {
	return this.setCursor(1, true);
};

// }}}
// {{{ Slide.prev()

/**
 * Decrements the cursor and gets the previous image.
 *
 * @param void
 * @return {Object|null}
 */
Limelight.Slide.prototype.prev = function() {
	return this.setCursor(-1, true);
};

// }}}
// {{{ Slide.reset()

/**
 * Resets the cursor position and gets the first image.
 *
 * @param void
 * @return {Object|null}
 */
Limelight.Slide.prototype.reset = function() {
	return this.setCursor(0);
};

// }}}
// {{{ Slide.end()

/**
 * Sets the cursor position to the end and gets the last image.
 *
 * @param void
 * @return {Object|null}
 */
Limelight.Slide.prototype.end = function() {
	return this.setCursor(-1);
};

// }}}
/**#@+ @static */
// {{{ util

/**
 * The container of the miscellaneous utility functions.
 *
 * @type {Object}
 */
Limelight.util = {};

// }}}
// {{{ util.ucFirst()

/**
 * Converts the first character to upper case.
 *
 * @param {String} str
 * @return {String}
 */
Limelight.util.ucFirst = function(str) {
	return str.charAt(0).toUpperCase() + str.substring(1);
};

// }}}
// {{{ util.lcFirst()

/**
 * Converts the first character to lower case.
 *
 * @param {String} str
 * @return {String}
 */
Limelight.util.lcFirst = function(str) {
	return str.charAt(0).toLowerCase() + str.substring(1);
};

// }}}
// {{{ util.camelize()

/**
 * Converts the string to CamelCase.
 *
 * @param {String} str
 * @param {Boolean} isLowerCamelCase
 * @return {String}
 */
Limelight.util.camelize = function(str, isLowerCamelCase) {
	var i, l, part, result;

	part = Limelight.util.hyphenate(str).split('-');
	l = part.length;
	result = [];

	if (l) {
		i = 0;
		if (isLowerCamelCase) {
			result.push(part[i++]);
		}
		while (i < l) {
			result.push(Limelight.util.ucFirst(part[i++]));
		}
	}

	return result.join('');
};

// }}}
// {{{ util.hyphenate()

/**
 * Hyphenates the string.
 *
 * @param {String} str
 * @return {String}
 */
Limelight.util.hyphenate = function(str) {
	return str.replace(/[A-Z]+/g, '-$0').toLowerCase()
	          .replace(/^[-\s]+|[-\s]+$/g, '').replace(/[-\s]+/g, '-');
};

// }}}
// {{{ util.trim()

/**
 * Trims whitespaces from the string.
 *
 * @param {String} str
 * @return {String}
 */
Limelight.util.trim = function(str) {
	return str.replace(/^\s+|\s+$/g, '');
};

// }}}
// {{{ util.normalizeSpace()

/**
 * Normalizes whitespaces in the string.
 *
 * @param {String} str
 * @return {String}
 */
Limelight.util.normalizeSpace = function(str) {
	return str.replace(/\s+/g, ' ').replace(/^ | $/g, '');
};

// }}}
// {{{ ui

/**
 * The container of the user interface utility functions.
 *
 * @type {Object}
 */
Limelight.ui = {};

// }}}
// {{{ ui.isPortrait()

/**
 * Determines whether the device is portrait.
 *
 * @param void
 * @return {Boolean}
 */
Limelight.ui.isPortrait = function() {
	if (typeof window.orientation != 'number' || window.orientation % 180 == 0) {
		return true;
	} else {
		return false;
	}
};

// }}}
// {{{ ui.getViewportSize()

/**
 * Gets the size of the viewport.
 * For non-iPhone devices, override this method.
 *
 * @param {Boolean} isPortrait (optional)
 * @return {Number[]}
 */
Limelight.ui.getViewportSize = function(isPortrait) {
	if (typeof isPortrait == 'undefined') {
		isPortrait = Limelight.ui.isPortrait();
	}

	if (isPortrait) {
		return [320, 480];
	} else {
		return [480, 320];
	}
};

// }}}
// {{{ ui.getMargins()

/**
 * Gets the total width and height of the browser's user interface elements.
 * For non-Safari browsers, override this method.
 *
 * @param {Boolean} isPortrait (optional)
 * @return {Number[]}
 */
Limelight.ui.getMargins = function(isPortrait) {
	if (typeof isPortrait == 'undefined') {
		isPortrait = Limelight.ui.isPortrait();
	}

	if (isPortrait) {
		return [0, 20 + 44];
	} else {
		return [0, 20 + 32];
	}
};

// }}}
// {{{ dom

/**
 * The container of the DOM utility functions.
 *
 * @type {Object}
 */
Limelight.dom = {};

// }}}
// {{{ dom.getComputedStyle()

/**
 * The wrapper of document.defaultView.getComputedStyle().
 *
 * @param {Element} element
 * @return {CSSStyleDeclaration}
 */
Limelight.dom.getComputedStyle = function(element) {
	return document.defaultView.getComputedStyle(element, '');
};

// }}}
// {{{ dom.setAnimation()

/**
 * Sets the animation name and the other animation properties for the given element.
 *
 * @param {Element} element
 * @param {String} animationName
 * @param {Object} options
 * @return {Element}
 */
Limelight.dom.setAnimation = function(element, animationName, options) {
	var name, property;

	if (options) {
		for (name in options) {
			switch (name) {
				case 'duration':
				case 'timingFunction':
				case 'iterationCount':
				case 'direction':
				case 'playState':
				case 'delay':
					property = 'webkitAnimation' + Limelight.util.ucFirst(name);
					element.style[property] = options[name];
					break;
			}
		}
	}

	element.style.webkitAnimationName = animationName;

	return element;
};

// }}}
// {{{ dom.setAttributes()

/**
 * Sets the attributes for the given element.
 *
 * @param {Element} element
 * @param {Object} attributes
 * @return {Element}
 */
Limelight.dom.setAttributes = function(element, attributes) {
	for (var name in attributes) {
		element.setAttribute(name, attributes[name]);
	}
	return element;
};

// }}}
// {{{ dom.setStyles()

/**
 * Sets the styles for the given element.
 *
 * @param {Element} element
 * @param {Object} styles
 * @return void
 */
Limelight.dom.setStyles = function(element, styles) {
	for (var name in styles) {
		element.style[name] = styles[name];
	}
	return element;
};

// }}}
// {{{ dom.stopEvent()

/**
 * Prevents the default event and stops the event propagation.
 *
 * @param {Event} event
 * @return void
 */
Limelight.dom.stopEvent = function(event) {
	event.preventDefault();
	event.stopPropagation();
};

// }}}
/**#@-*/
// {{{ prototype (properties)

Limelight.prototype = {
	/**#@+
	 * Flags.
	 *
	 * @type {Boolean}
	 */
	active: false,
	loaded: false,
	locked: false,
	savable: false,
	/**#@-*/

	/**
	 * The Limelight box.
	 *
	 * @type {Element}
	 */
	box: null,

	/**
	 * The image transformation state indicator.
	 *
	 * @type {Object}
	 */
	indicator: null,

	/**
	 * The image title container.
	 *
	 * @type {Object}
	 */
	titlebar: null,

	/**
	 * The active image.
	 *
	 * @type {Element}
	 */
	image: null,

	/**
	 * The loading image.
	 *
	 * @type {Element}
	 */
	loading: null,

	/**
	 * The toolbar.
	 *
	 * @type {Limelight.Toolbar}
	 */
	toolbar: null,

	/**
	 * The button which was last clicked.
	 *
	 * @type {Limelight.Button}
	 */
	lastClicked: null,

	/**
	 * Transformation callback.
	 *
	 * @type {Function(mixed, ...)}
	 */
	transformFunc: null,

	/**
	 * The movable area.
	 *
	 * @type {Limelight.Rect}
	 */
	movableArea: null,

	/**
	 * The click detection rectangle.
	 *
	 * @type {Limelight.Rect}
	 */
	clickRect: null,

	/**#@+
	 * Parameters.
	 *
	 * @type {Number}
	 */
	boxX: 0,
	boxY: 0,
	boxWidth: 0,
	boxHeight: 0,
	startTime: 0,
	startX: 0,
	startY: 0,
	endTime: 0,
	endX: 0,
	endY: 0,
	initialTranslateX: 0,
	initialTranslateY: 0,
	translateX: 0,
	translateY: 0,
	maxTranslateX: 0,
	maxTranslateY: 0,
	minTranslateX: 0,
	minTranslateY: 0,
	initialScale: 0,
	fitScale: 0,
	fillScale: 0,
	fitToWidthScale: 0,
	fitToHeightScale: 0,
	scale: 0,
	rotation: 0,
	diagonalLength: 0,
	diagonalAngle1: 0,
	diagonalAngle2: 0,
	doubleTapDuration: 300,
	/**#@-*/

	/**
	 * Event handlers.
	 *
	 * @type {Object}
	 */
	handlers: null,

	/**
	 * The default slide object.
	 *
	 * @type {Limelight.Slide}
	 */
	defaultSlide: null,

	/**
	 * The active slide object.
	 *
	 * @type {Limelight.Slide}
	 */
	slide: null,

	/**
	 * The width of the sliding detection area.
	 *
	 * @type {Number}
	 */
	slideBorder: 120,

	/**
	 * The slideshow handlers.
	 *
	 * @type {Object}
	 */
	slideshow: null,

	/**
	 * The timeout ID.
	 *
	 * @type {Number}
	 */
	timeoutId: -1
};

// }}}
// {{{ init()

/**
 * Initilalizes the Limelight object.
 * Creates new Limelight elements and add them to the document body.
 *
 * @param {Object} options
 * @return void
 */
Limelight.prototype.init = function(options) {
	var bool, num, name;
	var flags = {
		'iPhone': navigator.userAgent.search(/iP(hone|od)/) != -1,
		'title': false,
		'indicator': false,
		'standalone': false
	};

	if (options) {
		for (name in options) {
			bool = (options[name]) ? true : false;
			switch (name) {
				case 'title':
				case 'indicator':
				case 'standalone':
					flags[name] = bool;
					break;
				case 'savable':
					this[name] = bool;
					break;
				case 'doubleTap':
					num = options[name];
					if (typeof num == 'number') {
						num = Math.floor(num);
					} else {
						num = parseInt(num, 10);
					}
					if (isFinite(num) && num >= 0) {
						this.doubleTapDuration = num;
					}
					break;
			}
		}
	}

	this.initEventHandlers();
	this.initEventHandlers = null;

	this.transformFunc = this.transform;

	if (!flags.standalone) {
		this.defaultSlide = new Limelight.Slide();
	}

	this.clickRect = new Limelight.Rect([-5, -5], [5, 5]);

	var box = document.createElement('div');
	box.className = 'limelight-box';
	this.box = document.body.appendChild(box);

	var loading = document.createElement('img');
	loading.className = 'limelight-loading';
	Limelight.dom.setAttributes(loading, {
		'src': 'img/limelight-loading.gif',
		'width': '32',
		'height': '32',
		'title': 'loading',
		'alt': ''
	});
	this.loading = box.appendChild(loading);

	this.toolbar = new Limelight.Toolbar();
	box.appendChild(this.toolbar.element);
	if (!flags.iPhone) {
		this.initButtonEventHandlers();
	}
	this.initButtonEventHandlers = null;

	if (flags.title) {
		this.initTitlebar();
	}
	this.initTitlebar = null; 

	if (flags.indicator) {
		this.initIndicator();
	}
	this.initIndicator = null;
};

// }}}
// {{{ initEventHandlers()

/**
 * Initilalizes the event handlers.
 *
 * @param void
 * @return void
 */
Limelight.prototype.initEventHandlers = function() {
	var self = this;

	this.handlers = {
		'orientationchange':  function() { self.onOrientationChange(); },
		'touchstart':    function(event) { self.onTouchStart(event); },
		'touchmove':     function(event) { self.onTouchMove(event); },
		'touchend':      function(event) { self.onTouchEnd(event); },
		'gesturestart':  function(event) { self.onGestureStart(event); },
		'gesturechange': function(event) { self.onGestureChange(event); },
		'gestureend':    function(event) { self.onGestureEnd(event); },
		'imageload': function() {
			self.toggleImageLoading(false);
			self.resetTransformation();
			this.removeEventListener('load', self.handlers.imageload, false);
		}
	};
};

// }}}
// {{{ initButtonEventHandlers()

/**
 * Initilalizes the event handlers for the buttons.
 *
 * @param void
 * @return void
 */
Limelight.prototype.initButtonEventHandlers = function() {
	var self = this;

	this.toolbar.closeButton.attachEvent('click', function(event) {
		Limelight.dom.stopEvent(event);
		self.deactivate();
		return false;
	});

	this.toolbar.fitButton.attachEvent('click', function(event) {
		Limelight.dom.stopEvent(event);
		self.scaleTo(self.fitScale);
		self.setLastClicked(self.toolbar.fitButton);
		return false;
	});

	this.toolbar.fillButton.attachEvent('click', function(event) {
		Limelight.dom.stopEvent(event);
		self.scaleTo(self.fillScale);
		self.setLastClicked(self.toolbar.fillButton);
		return false;
	});

	this.toolbar.origButton.attachEvent('click', function(event) {
		Limelight.dom.stopEvent(event);
		self.scaleTo(1);
		self.setLastClicked(self.toolbar.origButton);
		return false;
	});
};

// }}}
// {{{ initIndicator()

/**
 * Initilalizes the indicator.
 *
 * @param void
 * @return void
 */
Limelight.prototype.initIndicator = function() {
	var indicator, xText, yText, wText, hText, sText, rText;

	/*
	<div class="limelight-indicator">
		W:<span>-</span> H:<span>-</span> (<span>-</span>%)<br />
		X:<span>-</span> Y:<span>-</span> R:<span>-</span>&#xb0;
	</div>
	*/

	indicator = document.createElement('div');
	indicator.className = 'limelight-indicator';
	indicator.addEventListener('webkitTransitionEnd', function() {
		if (this.style.opacity == 0) {
			this.style.display = 'none';
		}
	}, false)

	indicator.appendChild(document.createTextNode('W:'));
	wText = indicator.appendChild(document.createElement('span'))
	                      .appendChild(document.createTextNode('-'));
	indicator.appendChild(document.createTextNode(' H:'));
	hText = indicator.appendChild(document.createElement('span'))
	                 .appendChild(document.createTextNode('-'));
	indicator.appendChild(document.createTextNode(' ('));
	sText = indicator.appendChild(document.createElement('span'))
	                 .appendChild(document.createTextNode('-'));
	indicator.appendChild(document.createTextNode('%)'));

	indicator.appendChild(document.createElement('br'));

	indicator.appendChild(document.createTextNode('X:'));
	xText = indicator.appendChild(document.createElement('span'))
	                      .appendChild(document.createTextNode('-'));
	indicator.appendChild(document.createTextNode(' Y:'));
	yText = indicator.appendChild(document.createElement('span'))
	                 .appendChild(document.createTextNode('-'));
	indicator.appendChild(document.createTextNode(' R:'));
	rText = indicator.appendChild(document.createElement('span'))
	                 .appendChild(document.createTextNode('-'));
	indicator.appendChild(document.createTextNode('\xb0')); // degree sign

	this.indicator = {
		'element': indicator,
		'texts': {
			'x': xText,
			'y': yText,
			'w': wText,
			'h': hText,
			's': sText,
			'r': rText
		},
		'show': function() {
			indicator.style.display = 'block';
			indicator.style.opacity = '1';
		},
		'hide': function() {
			indicator.style.opacity = '0';
		},
		'update': function(x, y, width, height, scale, rotation) {
			xText.nodeValue = x;
			yText.nodeValue = y;
			wText.nodeValue = width;
			hText.nodeValue = height;
			sText.nodeValue = scale;
			rText.nodeValue = rotation;
		}
	};

	this.box.appendChild(indicator);
};

// }}}
// {{{ initTitlebar()

/**
 * Initilalizes the titlebar.
 *
 * @param void
 * @return void
 */
Limelight.prototype.initTitlebar = function() {
	var titlebar, text;

	/*
	<div class="limelight-title"><p>-</p></div>
	*/

	titlebar = document.createElement('div');
	titlebar.className = 'limelight-title';
	titlebar.addEventListener('webkitAnimationEnd', function() {
		this.style.display = 'none';
		this.style.webkitAnimationName = 'none';
	}, false);

	text = titlebar.appendChild(document.createElement('p'))
	               .appendChild(document.createTextNode('-')),

	this.titlebar = {
		'element': titlebar,
		'text': text,
		'show': function() {
			titlebar.style.display = 'block';
			titlebar.style.opacity = 1;
			titlebar.style.top = '0';
		},
		'hide': function() {
			titlebar.style.display = 'none';
			titlebar.style.opacity = 0;
		},
		'update': function(title) {
			text.nodeValue = title;
		},
		'pop': function() {
			var hpx, hnum;
			titlebar.style.display = 'block';
			titlebar.style.webkitAnimationName = '"limelight-pop"';
			/*
			hpx = Limelight.dom.getComputedStyle(titlebar).height;
			titlebar.style.top = '-' + hpx;
			hnum = parseInt(hpx);
			if (hnum < 32) {
				titlebar.style.webkitAnimationName = '"limelight-pop1"';
			} else if (hnum < 53) {
				titlebar.style.webkitAnimationName = '"limelight-pop2"';
			} else if (hnum < 74) {
				titlebar.style.webkitAnimationName = '"limelight-pop3"';
			} else if (hnum < 95) {
				titlebar.style.webkitAnimationName = '"limelight-pop4"';
			} else {
				titlebar.style.webkitAnimationName = '"limelight-pop5"';
			}
			*/
		}
	};

	this.box.appendChild(titlebar);
};

// }}}
// {{{ calcScale()

/**
 * Calculates a scale factor.
 *
 * @param {Number} scale
 * @return {Number}
 */
Limelight.prototype.calcScale = function(scale) {
	return this.scale * scale;
};

// }}}
// {{{ calcRotation()

/**
 * Calculates a rotation angle in degrees.
 *
 * @param {Number} rotation
 * @return {Number}
 */
Limelight.prototype.calcRotation = function(rotation) {
	return Math.round(this.rotation + rotation + 360) % 360;
};

// }}}
// {{{ calcScrollableX()

/**
 * Calculates a scrollable X translation.
 *
 * @param {Number} x
 * @return {Number}
 */
Limelight.prototype.calcScrollableX = function(x) {
	return Math.max(this.minTranslateX, Math.min(this.maxTranslateX, x));
};

// }}}
// {{{ calcScrollableY()

/**
 * Calculates a scrollable Y translation.
 *
 * @param {Number} y
 * @return {Number}
 */
Limelight.prototype.calcScrollableY = function(y) {
	return Math.max(this.minTranslateY, Math.min(this.maxTranslateY, y));
};

// }}}
// {{{ focus()

/**
 * Focuses to the Limelight box.
 *
 * @param void
 * @return void
 */
Limelight.prototype.focus = function() {
	window.scrollTo(this.boxX, this.boxY);
};

// }}}
// {{{ transform()

/**
 * Transforms the image.
 *
 * @param void
 * or
 * @param {Event} event The event object which was passwd from 'gesturechange' event.
 *                      Specifies a scale factor and a rotation angle.
 * or
 * @param {Number} x
 * @param {Number} y
 * or
 * @param {Number} x
 * @param {Number} y
 * @param {Number} scale
 * @param {Number} rotation
 *
 * @return void
 */
Limelight.prototype.transform = function() {
	var x, y, transform, scale, rotation;

	if (!this.loaded) {
		return;
	}

	x = this.translateX;
	y = this.translateY;
	scale = this.scale;
	rotation = this.rotation;

	if (arguments.length == 1) {
		scale = this.calcScale(arguments[0].scale);
		rotation = this.calcRotation(arguments[0].rotation);
	} else if (arguments.length > 1) {
		x = arguments[0];
		y = arguments[1];
		if (arguments.length > 2) {
			scale = arguments[2];
			if (arguments.length > 3) {
				rotation = arguments[3];
			}
		}
	}

	this.image.style.top = y + 'px';
	this.image.style.left = x + 'px';
	transform = 'scale(' + scale + ') rotate(' + rotation + 'deg)';
	if (this.image.style.webkitTransform != transform) {
		this.image.style.webkitTransform = transform;
	}

	if (this.indicator && !this.locked) {
		this.indicator.update(x - this.initialTranslateX,
		                      y - this.initialTranslateY,
		                      Math.round(this.image.width * scale),
		                      Math.round(this.image.height * scale),
		                      Math.round(scale * 100),
		                      (rotation > 180) ? rotation - 360 : rotation);
	}
};

// }}}
// {{{ scroll()

/**
 * Scrolls the image.
 *
 * @param void
 * or
 * @param {Event} event (ignored)
 * or
 * @param {Number} x
 * @param {Number} y
 * or
 * @param {Number} x
 * @param {Number} y
 * @param {Number} scale (ignored)
 * @param {Number} rotation (ignored)
 *
 * @return void
 */
Limelight.prototype.scroll = function() {
	var x, y;

	if (!this.loaded) {
		return;
	}

	if (arguments.length == 0) {
		x = this.translateX;
		y = this.translateY;
	} else if (arguments.length > 1) {
		x = arguments[0];
		y = arguments[1];
	} else {
		return;
	}

	this.transform(this.calcScrollableX(x), this.calcScrollableY(y));
};

// }}}
// {{{ hasTransformed()

/**
 * Determines whether the image transformation state is the initial state.
 *
 * @param void
 * @return void
 */
Limelight.prototype.hasTransformed = function() {
	if (this.translateX != this.initialTranslateX) {
		return true;
	}
	if (this.translateY != this.initialTranslateY) {
		return true;
	}
	if (this.scale != this.initialScale) {
		return true
	}
	if (this.rotation != 0) {
		return true;
	}
	return false;
};

// }}}
// {{{ resetTransformation()

/**
 * Sets the image transformation state to the initial state.
 *
 * @param void
 * @return void
 */
Limelight.prototype.resetTransformation = function() {
	if (this.initialScale == 0) {
		var x, y, width, height, origWidth, origHeight;

		x = this.boxWidth;
		y = this.boxHeight;
		width = origWidth = this.image.width;
		height = origHeight = this.image.height;

		this.diagonalLength = Math.sqrt(width * width + height * height);
		this.diagonalAngle1 = Math.atan(height / width);
		this.diagonalAngle2 = Math.atan(width / height);

		if (width > x) {
			height = x / width * height;
			width = x;
		}
		if (height > y) {
			width = y / height * width;
			height = y;
		}

		this.initialTranslateX = Math.floor((x - origWidth) / 2);
		this.initialTranslateY = Math.floor((y - origHeight) / 2);

		if (width < origWidth) {
			this.initialScale = width / origWidth;
		} else {
			this.initialScale = 1;
		}

		this.fitToWidthScale = x / origWidth;
		this.fitToHeightScale = y / origHeight;

		if (this.fitToWidthScale < this.fitToHeightScale) {
			this.fitScale = this.fitToWidthScale;
			this.fillScale = this.fitToHeightScale;
		} else {
			this.fitScale = this.fitToHeightScale;
			this.fillScale = this.fitToWidthScale;
		}
	}

	this.translateX = this.initialTranslateX;
	this.translateY = this.initialTranslateY;
	this.scale = this.initialScale;
	this.rotation = 0;
	this.transform();

	this.setLastClicked(null);
};

// }}}
// {{{ scaleTo()

/**
 * Shows the target image by the given scale factor.
 *
 * @param {Number} scale
 * @return void
 */
Limelight.prototype.scaleTo = function(scale) {
	this.translateX = this.initialTranslateX;
	this.translateY = this.initialTranslateY;
	this.scale = scale;
	this.rotation = 0;
	this.transform();
};

// }}}
// {{{ attachEvent()

/**
 * Attaches the Limelight event handler to the given node.
 *
 * @param {Node} node
 * @param {String} eventName
 * @return void
 */
Limelight.prototype.attachEvent = function(node, eventName) {
	node.addEventListener(eventName, this.handlers[eventName], false);
};

// }}}
// {{{ detachEvent()

/**
 * Detaches the Limelight event handler from the given node.
 *
 * @param {Node} node
 * @param {String} eventName
 * @return void
 */
Limelight.prototype.detachEvent = function(node, eventName) {
	node.removeEventListener(eventName, this.handlers[eventName], false);
};

// }}}
// {{{ activate()

/**
 * Activetes Limelight with the image URI.
 *
 * @param {String} uri
 * @param {String} title
 * @return void
 */
Limelight.prototype.activate = function(uri, title) {
	if (this.active) {
		this.replace(uri, title);
		this.focus();
		return;
	}

	if (this.image) {
		this.box.removeChild(this.image);
		this.image = null;
	}

	this.toggleImageLoading(true);
	this.onOrientationChange();
	this.image = this.createImage(uri, title);
	this.image.addEventListener('load', this.handlers.imageload, false);

	this.toolbar.show();
	this.setLastClicked(null);
	this.box.style.display = 'block';

	this.attachEvent(document.body, 'orientationchange')
	this.attachEvent(this.box, 'touchstart');
	this.attachEvent(this.box, 'touchmove');
	this.attachEvent(this.box, 'touchend');
	this.attachEvent(this.box, 'gesturestart');
	this.attachEvent(this.box, 'gesturechange');
	this.attachEvent(this.box, 'gestureend');

	this.active = true;
};

// }}}
// {{{ activateSlide()

/**
 * Activetes Limelight with the slide object.
 *
 * @param {Limelight.Slide} slide
 * @param {Number} position
 * @return {Boolean}
 */
Limelight.prototype.activateSlide = function(slide, position) {
	var image;

	if (typeof position == 'undefined') {
		image = slide.current();
	} else {
		image = slide.setCursor(position);
	}

	if (image) {
		this.setSlide(slide)
		this.activate(image.uri, image.title);
		return true;
	} else {
		return false;
	}
};

// }}}
// {{{ getSlide()

/**
 * Gets the active slide object.
 *
 * @param void
 * @return {Limelight.Slide|null}
 */
Limelight.prototype.getSlide = function(slide) {
	return this.slide;
};

// }}}
// {{{ setSlide()

/**
 * Sets the active slide object.
 *
 * @param {Limelight.Slide|null} slide
 * @return {Limelight.Slide|null}
 */
Limelight.prototype.setSlide = function(slide) {
	var oldSlide = this.slide;
	this.slide = slide;
	return oldSlide;
};

// }}}
// {{{ beginSlideshow()

/**
 * Begins the slideshow.
 *
 * @param {Number} interval
 * @param {Limelight.Slide} slide
 * @return {Boolean}
 */
Limelight.prototype.beginSlideshow = function(interval, slide) {
	var self = this;

	if (typeof interval != 'number') {
		interval = parseInt(interval);
	}
	if (!isFinite(interval) || interval <= 0) {
		return false;
	}
	interval = Math.ceil(interval * 1000);

	if (!slide) {
		if (!this.defaultSlide) {
			return false;
		}
		slide = this.defaultSlide;
	}

	if (!this.activateSlide(slide, 0)) {
		return false;
	}

	this.toolbar.hide();

	this.slideshow = {
		'next': function() {
			var image = slide.next();
			if (image) {
				self.replace(image.uri, image.title);
				self.image.addEventListener('load', self.slideshow.wait, false);
			} else {
				self.deactivate();
			}
		},
		'wait': function() {
			this.removeEventListener('load', self.slideshow.wait, false);
			self.timeoutId = window.setTimeout(self.slideshow.next, interval);
		}
	};

	this.timeoutId = window.setTimeout(this.slideshow.next, interval);

	return true;
};

// }}}
// {{{ deactivate()

/**
 * Deactivates Limelight.
 *
 * @param void
 * @return void
 */
Limelight.prototype.deactivate = function() {
	if (!this.active) {
		return;
	}

	if (this.timeoutId != -1) {
		window.clearTimeout(this.timeoutId);
		this.timeoutId = -1;
	}

	if (this.slideshow) {
		this.slideshow = null;
	}

	this.setLastClicked(null);
	this.box.style.display = 'none';

	if (this.image) {
		this.box.removeChild(this.image);
		this.image = null;
	}

	this.initialScale = 0;

	this.detachEvent(document.body, 'orientationchange')
	this.detachEvent(this.box, 'touchstart');
	this.detachEvent(this.box, 'touchmove');
	this.detachEvent(this.box, 'touchend');
	this.detachEvent(this.box, 'gesturestart');
	this.detachEvent(this.box, 'gesturechange');
	this.detachEvent(this.box, 'gestureend');

	this.active = false;
};

// }}}
// {{{ createImage()

/**
 * Creates a new image element.
 *
 * @param {String} uri
 * @param {String} title
 * @param {Object} attributes
 * @return {Element}
 */
Limelight.prototype.createImage = function(uri, title, attributes) {
	var image = document.createElement('img');
	image.className = 'limelight-image';
	image.setAttribute('src', uri);
	image.setAttribute('alt', '');
	if (typeof title == 'string' && title.length) {
		image.setAttribute('title', title);
	}
	if (attributes) {
		Limelight.dom.setAttributes(image, attributes);
	}
	return this.box.appendChild(image);
};

// }}}
// {{{ toggleImageLoading()

/**
 * Toggles visibilities of the target image and the loading image.
 *
 * @param {Boolean} isLoading
 * @return void
 */
Limelight.prototype.toggleImageLoading = function(isLoading) {
	var target = this.image;
	var titlebar = this.titlebar;

	if (isLoading) {
		this.loaded = false;
		this.loading.style.visibility = 'visible';
		if (target) {
			target.style.opacity = '0';
		}
		if (titlebar) {
			titlebar.hide();
		}
	} else {
		this.loaded = true;
		this.loading.style.visibility = 'hidden';
		if (target) {
			target.style.opacity = '1';
			if (titlebar && target.hasAttribute('title')) {
				titlebar.update(target.getAttribute('title'));
				titlebar.pop();
			}
		}
	}

	this.setLastClicked(null);
};

// }}}
// {{{ shake()

/**
 * Shakes the target image.
 *
 * @param {Boolean} fromRight
 * @return void
 */
Limelight.prototype.shake = function(fromRight) {
	this.scaleTo(this.initialScale);
	// The following code works well on the iPhone Simulator,
	// but it does not work well on the iPhone 3G because of the lack of performance.
	/*if (this.image) {
		this.image.addEventListener('webkitAnimationEnd', function() {
			this.removeEventListener('webkitAnimationEnd', arguments.callee, false);
			this.style.webkitAnimationName = 'none';
		}, false);
		if (fromRight) {
			this.image.style.webkitAnimationName = '"limelight-shake-r"';
		} else {
			this.image.style.webkitAnimationName = '"limelight-shake-l"';
		}
	}*/
};

// }}}
// {{{ replace()

/**
 * Replaces the old image with the new image.
 *
 * @param {String} uri
 * @param {String} title
 * @return void
 */
Limelight.prototype.replace = function(uri, title) {
	if (this.image) {
		this.box.removeChild(this.image);
		this.image = null;
	}

	this.toggleImageLoading(true);
	this.initialScale = 0;
	this.image = this.createImage(uri, title);
	this.image.addEventListener('load', this.handlers.imageload, false);
};

// }}}
// {{{ setLastClicked()

/**
 * Sets the last clicked button.
 *
 * @param {Limelight.Button|null} button
 * @return void
 */
Limelight.prototype.setLastClicked = function(button) {
	var marginX, marginY;

	if (!this.loaded) {
		return;
	}

	if (button && button === this.lastClicked && !this.locked) {
		button.element.className += ' limelight-locked';
		this.box.className += ' limelight-locked';
		this.transformFunc = this.scroll;
		this.locked = true;

		marginX = Math.round(Math.abs(this.boxWidth
		        - this.image.width  * this.scale) / 2);
		marginY = Math.round(Math.abs(this.boxHeight
		        - this.image.height  * this.scale) / 2);

		this.maxTranslateX = this.initialTranslateX + marginX;
		this.minTranslateX = this.initialTranslateX - marginX;
		this.maxTranslateY = this.initialTranslateY + marginY;
		this.minTranslateY = this.initialTranslateY - marginY;
	} else {
		if (this.lastClicked) {
			this.lastClicked.element.className = 'limelight-button';
		}
		if (this.locked) {
			this.box.className = 'limelight-box';
			this.transformFunc = this.transform;
			this.locked = false;
		}
		this.lastClicked = button;
	}
};

// }}}
// {{{ onOrientationChange()

/**
 * The 'orientationchange' event handler.
 *
 * @param void
 * @return void
 */
Limelight.prototype.onOrientationChange = function() {
	var x, y, width, height, isPortrait, viewportSize, margins;

	isPortrait = Limelight.ui.isPortrait();
	viewportSize = Limelight.ui.getViewportSize(isPortrait);
	margins = Limelight.ui.getMargins(isPortrait);

	x = 0;
	y = window.scrollY;
	width = viewportSize[0] - margins[0]
	height = viewportSize[1] - margins[1]

	this.boxX = x;
	this.boxY = y
	this.boxWidth = width;
	this.boxHeight = height;

	this.movableArea = new Limelight.Rect([-width, -height], [width, height]);

	this.box.style.left = x + 'px';
	this.box.style.top = y + 'px';
	this.box.style.width = width + 'px';
	this.box.style.height = height + 'px';

	this.loading.style.marginTop = Math.floor((height - 32) / 2) + 'px';

	this.initialScale = 0;

	if (this.image && this.loaded) {
		this.resetTransformation();
	}

	this.focus();
};

// }}}
// {{{ onTouchStart()

/**
 * The 'touchstart' event handler.
 *
 * @param {Event} event
 * @return void
 */
Limelight.prototype.onTouchStart = function(event) {
	if (this.savable && this.loaded &&
		event.timeStamp > this.endTime + 500 && // double tap protection
		event.target.isSameNode(this.image))
	{
		event.stopPropagation();
	} else {
		Limelight.dom.stopEvent(event);
	}

	if (this.timeoutId != -1) {
		window.clearTimeout(this.timeoutId);
		this.timeoutId = -1;
		if (this.slideshow) {
			this.slideshow = null;
			this.toolbar.show();
		}
	}

	if (event.touches.length != 1) {
		return;
	}

	this.startTime = event.timeStamp;
	this.startX = this.endX = event.touches[0].screenX;
	this.startY = this.endY = event.touches[0].screenY;

	if (this.indicator) {
		this.indicator.show();
	}
};

// }}}
// {{{ onTouchMove()

/**
 * The 'touchmove' event handler.
 *
 * @param {Event} event
 * @return void
 */
Limelight.prototype.onTouchMove = function(event) {
	Limelight.dom.stopEvent(event);

	if (event.touches.length != 1) {
		return;
	}

	this.endX = event.touches[0].screenX;
	this.endY = event.touches[0].screenY;
	var deltaX = this.endX - this.startX;
	var deltaY = this.endY - this.startY;
	if (this.movableArea.contains([deltaX, deltaY])) {
		this.transformFunc(this.translateX + deltaX, this.translateY + deltaY);
	}
};

// }}}
// {{{ onTouchEnd()

/**
 * The 'touchend' event handler.
 *
 * @param {Event} event
 * @return void
 */
Limelight.prototype.onTouchEnd = function(event) {
	Limelight.dom.stopEvent(event);

	var now = event.timeStamp;
	var deltaX = this.endX - this.startX;
	var deltaY = this.endY - this.startY;
	var delta = [deltaX, deltaY];

	if (this.movableArea.contains(delta)) {
		if (this.locked) {
			this.translateX = this.calcScrollableX(this.translateX + deltaX);
			this.translateY = this.calcScrollableY(this.translateY + deltaY);
		} else {
			this.translateX += deltaX;
			this.translateY += deltaY;
		}
	}

	if (this.clickRect.contains(delta)) {
		var toolbar = this.toolbar;

		if (toolbar.closeButton.isTargetOf(event)) {
			this.deactivate();
		} else if (toolbar.fitButton.isTargetOf(event)) {
			this.scaleTo(this.fitScale);
			this.setLastClicked(toolbar.fitButton);
		} else if (toolbar.fillButton.isTargetOf(event)) {
			this.scaleTo(this.fillScale);
			this.setLastClicked(toolbar.fillButton);
		} else if (toolbar.origButton.isTargetOf(event)) {
			this.scaleTo(1);
			this.setLastClicked(toolbar.origButton);
		} else {
			this.focus();
			if (now - this.endTime < this.doubleTapDuration) {
				toolbar.peekaboo();
			}
			if (!this.locked) {
				this.setLastClicked(null);
			}
		}
	} else if (!this.locked && this.slide && this.image) {
		var direction = Limelight.DIRECTION.NONE;
		var theta, actualWidth, leftEdge, image;

		// The Event.rotation property is a clockwise angle in degrees,
		// but trigonometric functions take a counter-clockwise angle in radians.
		theta = this.rotation * Math.PI / 180;
		actualWidth = this.diagonalLength * this.scale
		            * Math.max(Math.abs(Math.cos(this.diagonalAngle1 - theta)),
		                       Math.abs(Math.sin(this.diagonalAngle2 - theta)));
		leftEdge = this.translateX + (this.image.width - actualWidth) / 2;

		if (deltaX < 0 && leftEdge + actualWidth < this.slideBorder) {
			direction = Limelight.DIRECTION.FORWARD;
		} else if (deltaX > 0 && leftEdge > this.boxWidth - this.slideBorder) {
			direction = Limelight.DIRECTION.BACKWARD;
		}

		switch (direction) {
			case Limelight.DIRECTION.FORWARD:
				if (image = this.slide.next()) {
					this.replace(image.uri, image.title);
				} else if (this.slide.onNoNext){
					this.slide.onNoNext(this, this.slide);
				} else {
					this.shake(true);
				}
				break;
			case Limelight.DIRECTION.BACKWARD:
				if (image = this.slide.prev()) {
					this.replace(image.uri, image.title);
				} else if (this.slide.onNoPrev){
					this.slide.onNoPrev(this, this.slide);
				} else {
					this.shake(false);
				}
				break;
		}

		this.setLastClicked(null);
	} else if (!this.locked) {
		this.setLastClicked(null);
	}

	this.endTime = now;

	if (this.indicator) {
		this.indicator.hide();
	}
};

// }}}
// {{{ onGestureStart()

/**
 * The 'gesturestart' event handler.
 *
 * @param {Event} event
 * @return void
 */
Limelight.prototype.onGestureStart = function(event) {
	Limelight.dom.stopEvent(event);

	if (this.indicator) {
		this.indicator.show();
	}
};

// }}}
// {{{ onGestureChange()

/**
 * The 'gesturechange' event handler.
 *
 * @param {Event} event
 * @return void
 */
Limelight.prototype.onGestureChange = function(event) {
	Limelight.dom.stopEvent(event);

	this.transformFunc(event);
};

// }}}
// {{{ onGestureEnd()

/**
 * The 'gestureend' event handler.
 *
 * @param {Event} event
 * @return void
 */
Limelight.prototype.onGestureEnd = function(event) {
	Limelight.dom.stopEvent(event);

	if (!this.locked) {
		this.scale = this.calcScale(event.scale);
		this.rotation = this.calcRotation(event.rotation);
	}

	if (this.indicator) {
		this.indicator.hide();
	}
};

// }}}
// {{{ bind()

/**
 * Binds the activator function to anchors.
 *
 * @param {String} className
 *  Specifies the class name to be scanned. (optional)
 *  If it is not given or a false-like value, use "limelight".
 *
 * @param {Node} contextNode
 *  Specifies the context node for the XPath evaluation. (optional)
 *  If it is not given or a false-like value, use the document.body.
 *
 * @param {Limelight.Slide|Boolean|null} slide
 *  Specifies the slide object. (optional)
 *  In case it is not given, use the default.
 *  In case it is a boolean true, create a new slide object.
 *  In case it is a boolean false or null, no slideshow.
 *
 * @return {Limelight.Slide|null}
 *  The slide object which contains the images.
 */
Limelight.prototype.bind = function(className, contextNode, slide) {
	var i, l, result;

	className = className || 'limelight';
	contextNode = contextNode || document.body;
	switch (typeof slide) {
		case 'undefined':
			slide = this.defaultSlide;
			break;
		case 'boolean':
			slide = (slide) ? new Limelight.Slide() : null;
			break;
	}

	if (!className.length || className.charCodeAt(0) < 0x41 ||
		className.search(/[^-0-9A-Z_a-z\u0080-\uffff]/) != -1)
	{
		throw new Limelight.Exception('UnexpectedValue',
		                              'Invalid class name specified');
	}

	result = document.evaluate('.//a[@href and contains('
	                           + ["concat(' ', normalize-space(@class), ' ')",
	                              "' " + className + " '"].join(', ')
	                           + ')]',
	                           contextNode,
	                           null,
	                           XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
	                           null);

	l = result.snapshotLength;
	for (i = 0; i < l; i++) {
		this.bindAnchor(result.snapshotItem(i), slide);
	}

	return slide;
};

// }}}
// {{{ bindAnchor()

/**
 * Binds the activator function to the anchor.
 *
 * @param {Element} anchor
 * @param {Limelight.Slide} slide
 * @return {Function(Event)}
 */
Limelight.prototype.bindAnchor = function(anchor, slide) {
	var activator;

	if (!slide) {
		activator = Limelight.bindutil.getActivator(this);
	} else {
		activator = Limelight.bindutil.getSlideActivator(this, slide, slide.length);
		slide.addImage(anchor.href, Limelight.bindutil.getTitleOf(anchor));
	}

	anchor.addEventListener('click', activator, false);

	return activator;
};

// }}}
/**#@+ @static */
// {{{ bindutil

/**
 * The container of the utility functions for Limelight.bind().
 *
 * @type {Object}
 */
Limelight.bindutil = {};

// }}}
// {{{ bindutil.getActivator()

/**
 * Generates the Limelight activator function for anchors.
 *
 * @param {Limelight} self
 * @return {Function(Event)}
 */
Limelight.bindutil.getActivator = function(self) {
	if (typeof self.handlers.anchorclick == 'undefined') {
		self.handlers.anchorclick = function(event) {
			Limelight.dom.stopEvent(event);
			self.setSlide(null);
			self.activate(this.href, Limelight.bindutil.getTitleOf(this));
			return false;
		};
	}
	return self.handlers.anchorclick;
};

// }}}
// {{{ bindutil.getSlideActivator()

/**
 * Generates the Limelight activator function with the slide object.
 *
 * @param {Limelight} self
 * @param {Limelight.Slide} slide
 * @param {Number} position
 * @return {Function(Event)}
 */
Limelight.bindutil.getSlideActivator = function(self, slide, position) {
	return function(event) {
		if (self.activateSlide(slide, position)) {
			Limelight.dom.stopEvent(event);
			return false;
		} else {
			return true;
		}
	};
};

// }}}
// {{{ bindutil.getSlideshowActivator()

/**
 * Generates the Limelight slideshow activator function.
 *
 * @param {Limelight} self
 * @param {Limelight.Slide} slide
 * @param {Number} interval
 * @return {Function(Event)}
 */
Limelight.bindutil.getSlideshowActivator = function(self, slide, interval) {
	return function(event) {
		if (self.beginSlideshow(interval, slide)) {
			Limelight.dom.stopEvent(event);
			return false;
		} else {
			return true;
		}
	};
};

// }}}
// {{{ bindutil.getTitleOf()

/**
 * Gets the title from the element.
 *
 * @param {Element} element
 * @return {String|null}
 */
Limelight.bindutil.getTitleOf = function(element) {
	if (element.hasAttribute('title')) {
		return element.getAttribute('title');
	}
	if (element.childNodes.length &&
	    element.firstChild.nodeType == 1 &&
	    element.firstChild.nodeName.toLowerCase() == 'img' &&
	    element.firstChild.hasAttribute('title'))
	{
		return element.firstChild.getAttribute('title');
	}
	return null;
};

// }}}
/**#@-*/

/*
 * Local Variables:
 * mode: javascript
 * coding: utf-8
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: t
 * End:
 */
// vim: set syn=javascript fenc=utf-8 ai noet ts=4 sw=4 sts=4 fdm=marker:
