/**
 * Limelight
 */

// {{{ Extend the core objects

if (typeof Element.setAttributes == 'undefined') {
	Element.prototype.setAttributes = function(attrs) {
		for (a in attrs) {
			this.setAttribute(a, attrs[a]);
		}
		return this;
	};
}

if (typeof Element.setStyles == 'undefined') {
	Element.prototype.setStyles = function(props) {
		for (p in props) {
			this.style[p] = props[p];
		}
		return this;
	};
}

if (typeof Number.toInteger == 'undefined') {
	Number.prototype.toInteger = function() {
		return Math.floor(this);
	};
}

if (typeof Number.toFloat == 'undefined') {
	Number.prototype.toFloat = function() {
		return this;
	};
}

if (typeof String.toInteger == 'undefined') {
	String.prototype.toInteger = function(radix) {
		if (typeof radix == 'undefined') {
			return parseInt(this, 10);
		} else {
			return parseInt(this, radix);
		}
	};
}

if (typeof String.toFloat == 'undefined') {
	String.prototype.toFloat = function() {
		return parseFloat(this);
	};
}

// }}}
// {{{ Limelight

/**
 * Limelight
 *
 * @constructor
 * @param void
 */
var Limelight = function() {
};

// }}}
// {{{ Limelight.Exception

/**
 * The object for exception.
 *
 * @constructor
 * @param {String} name
 * @param {String} message
 */
Limelight.Exception = function(name, message) {
	this.name = name;
	this.message = message;
};

// }}}
// {{{ Limelight.Exception.toString()

/**
 *
 *
 * @param void
 * @return {String}
 */
Limelight.Exception.prototype.toString = function() {
	return 'Limelight Exception (' + this.name + ') ' + this.message;
};

// }}}
// {{{ Limelight.Point

/**
 * The object for point.
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
// {{{ Limelight.Rect

/**
 * The object for rectangle.
 *
 * @constructor
 * @param {Limelight.Point} topLeft
 * @param {Limelight.Point} bottomRight
 */
Limelight.Rect = function(topLeft, bottomRight) {
	this.topLeft = topLeft;
	this.bottomRight = bottomRight;
};

// }}}
// {{{ Limelight.Rect.contains()

/**
 * Determine whether contains a given point.
 *
 * @param {Limelight.Point} point
 * @return {Boolean}
 */
Limelight.Rect.prototype.contains = function(point) {
	if (point.x < this.topLeft.x || point.y < this.topLeft.y) {
		return false;
	} else if (point.x > this.bottomRight.x || point.y > this.bottomRight.y) {
		return false;
	} else {
		return true;
	}
};

// }}}
// {{{ Limelight.Button

/**
 * The button object.
 *
 * @constructor
 * @param {Element} elem
 */
Limelight.Button = function(elem) {
	this.elem = elem;
};

// }}}
// {{{ Limelight.Button.isTargetOf()

/**
 * Determine whether the target of a given event is the receiver.
 *
 * @param {Event} evt
 * @return {Boolean}
 */
Limelight.Button.prototype.isTargetOf = function(evt) {
	if (this.elem.isSameNode(evt.target) || this.elem.isSameNode(evt.target.parentNode)) {
		return true;
	} else {
		return false;
	}
};

// }}}
// {{{ Limelight.Button.setLabel()

/**
 * Set the button text.
 *
 * @param {String} str
 * @return void
 */
Limelight.Button.prototype.setLabel = function(str) {
	var txt = this.elem.firstChild;
	if (str != txt.nodeValue) {
		txt.nodeValue = str;
	}
};

// }}}
// {{{ Limelight.util

/**
 * Utility functions
 *
 * @type {Object}
 */
Limelight.util = {};

// }}}
// {{{ Limelight.util.getComputedStyle()

/**
 * The wrapper of document.defaultView.getComputedStyle().
 *
 * @static
 * @param {Element} elem
 * @return {CSSStyleDeclaration}
 */
Limelight.util.getComputedStyle = function(elem) {
	return document.defaultView.getComputedStyle(elem, '');
};

// }}}
// {{{ Limelight.util.stopEvent()

/**
 * Prevent the default event and stop the event propagation.
 *
 * @static
 * @param {Event} evt
 * @return void
 */
Limelight.util.stopEvent = function(evt) {
	evt.preventDefault();
	evt.stopPropagation();
};

// }}}
// {{{ Limelight.prototype

Limelight.prototype = {
	/**
	 * Flags
	 *
	 * @type {Boolean}
	 */
	isActive: false,
	imageLoaded: false,
	enableScaling: true,
	enableRotation: true,
	enableIndicator: false,
	enableDobuleTap: false,

	/**
	 * The Limelight block
	 *
	 * @type {Element}
	 */
	block: null,

	/**
	 * The indicator
	 *
	 * @type {Object}
	 */
	indicator: null,

	/**
	 * The target image
	 *
	 * @type {Element}
	 */
	targetImage: null,

	/**
	 * The loading image
	 *
	 * @type {Element}
	 */
	loadingImage: null,

	/**
	 * The close button
	 *
	 * @type {Limelight.Button}
	 */
	closeButton: null,

	/**
	 * The resizing buttons
	 *
	 * @type {Limelight.Button}
	 */
	fitSizeButton: null,
	fullSizeButton: null,
	dotByDotButton: null,

	/**
	 * Resizing callbacks
	 *
	 * @type {Function}
	 */
	fitSizeFunc: null,
	fullSizeFunc: null,

	/**
	 * The movable area.
	 *
	 * @type {Limelight.Rect}
	 */
	movableArea: null,

	/**
	 * The click determination rect.
	 *
	 * @type {Limelight.Rect}
	 */
	clickRect: null,

	/**
	 * Parameters
	 *
	 * @type {Number}
	 */
	blockX: 0,
	blockY: 0,
	blockWidth: 0,
	blockHeight: 0,
	startX: 0,
	startY: 0,
	endX: 0,
	endY: 0,
	initialTranslateX: 0,
	initialTranslateY: 0,
	translateX: 0,
	translateY: 0,
	initialScale: 0,
	fitToWidthScale: 0,
	fitToHeightScale: 0,
	scale: 0,
	rotation: 0,
	previousClick: 0,

	/**
	 * Event handlers
	 *
	 * @type {Object}
	 */
	handlers: null
};

// }}}
// {{{ Limelight.init()

/**
 * Initilalize Limelight.
 * Create new Limelight elements and add them to the document body.
 *
 * @param {Object} options
 * @return void
 */
Limelight.prototype.init = function(options) {
	var self = this;
	if (this.block) {
		return this;
	}

	if (typeof options == 'object') {
		for (var opt in options) {
			switch (opt) {
				case 'enableScaling':
				case 'enableRotation':
				case 'enableIndicator':
				case 'enableDobuleTap':
					this[opt] = options[opt];
					break;
			}
		}
	}

	this.clickRect = new Limelight.Rect(new Limelight.Point(-5, -5), new Limelight.Point(5, 5));

	this.handlers = {
		'orientationchange': function() { self.onOrientationChange(); },
		'touchstart':     function(evt) { self.onTouchStart(evt); },
		'touchmove':      function(evt) { self.onTouchMove(evt); },
		'touchend':       function(evt) { self.onTouchEnd(evt); },
		'gesturestart':   function(evt) { self.onGestureStart(evt); },
		'gesturechange':  function(evt) { self.onGestureChange(evt); },
		'gestureend':     function(evt) { self.onGestureEnd(evt); },
		'imageload': function() { self.toggleImageLoading(false); self.resetTransformation(); }
	}

	var block = document.createElement('div');
	block.className = 'limelight-block';
	document.body.appendChild(block);

	if (this.enableIndicator) {
		var indicator = document.createElement('div');
		indicator.className = 'limelight-indicator';
		block.appendChild(indicator);

		indicator.appendChild(document.createTextNode('('));
		var xIndicator = indicator.appendChild(document.createElement('span'))
		                          .appendChild(document.createTextNode('-'));
		indicator.appendChild(document.createTextNode(','));
		var yIndicator = indicator.appendChild(document.createElement('span'))
		                          .appendChild(document.createTextNode('-'));
		indicator.appendChild(document.createTextNode(')'));
		indicator.appendChild(document.createElement('br'));
		var sIndicator = indicator.appendChild(document.createElement('span'))
		                          .appendChild(document.createTextNode('-'));
		indicator.appendChild(document.createTextNode('%'));
		indicator.appendChild(document.createElement('br'));
		var rIndicator = indicator.appendChild(document.createElement('span'))
		                          .appendChild(document.createTextNode('-'));
		indicator.appendChild(document.createTextNode('\xb0'));

		this.indicator = {
			'container': indicator,
			'x': xIndicator,
			'y': yIndicator,
			's': sIndicator,
			'r': rIndicator,
			'show': function() {
				indicator.style.visibility = 'visible';
				indicator.style.webkitOpacity = '1';
				indicator.style.webkitAnimationName = '';
			},
			'hide': function() {
				indicator.style.webkitAnimationName = 'limelight-fadeout';
				indicator.style.webkitOpacity = '0';
			},
			'update': function(x, y, scale, rotation) {
				xIndicator.nodeValue = x;
				yIndicator.nodeValue = y;
				sIndicator.nodeValue = scale;
				rIndicator.nodeValue = rotation;
			}
		};
	}

	var loadingImage = document.createElement('img');
	loadingImage.className = 'limelight-loading';
	loadingImage.setAttributes({
		'src': 'img/limelight-loading.gif',
		'width': '32',
		'height': '32',
		'title': 'loading',
		'alt': ''
	});
	block.appendChild(loadingImage);

	var toolbar = document.createElement('table');
	toolbar.className = 'limelight-toolbar';
	toolbar.setAttribute('cellspacing', '0');
	block.appendChild(toolbar);

	var row = toolbar.appendChild(document.createElement('tbody'))
	                 .appendChild(document.createElement('tr'));

	var closeButton = document.createElement('span');
	closeButton.className = 'limelight-button';
	closeButton.addEventListener('click', function(evt) {
		Limelight.util.stopEvent(evt);
		self.deactivate();
		return false;
	}, false);
	row.appendChild(document.createElement('td'))
	   .appendChild(closeButton)
	   .appendChild(document.createTextNode('close'));

	var fitSizeButton = document.createElement('span');
	fitSizeButton.className = 'limelight-button';
	fitSizeButton.addEventListener('click', function(evt) {
		Limelight.util.stopEvent(evt);
		self.fitSizeFunc();
		return false;
	}, false);
	row.appendChild(document.createElement('td'))
	   .appendChild(fitSizeButton)
	   .appendChild(document.createTextNode('fit'));

	var fullSizeButton = document.createElement('span');
	fullSizeButton.className = 'limelight-button';
	fullSizeButton.addEventListener('click', function(evt) {
		Limelight.util.stopEvent(evt);
		self.fullSizeFunc();
		return false;
	}, false);
	row.appendChild(document.createElement('td'))
	   .appendChild(fullSizeButton)
	   .appendChild(document.createTextNode('full'));

	var dotByDotButton = document.createElement('span');
	dotByDotButton.className = 'limelight-button';
	dotByDotButton.addEventListener('click', function(evt) {
		Limelight.util.stopEvent(evt);
		self.dotByDot();
		return false;
	}, false);
	row.appendChild(document.createElement('td'))
	   .appendChild(dotByDotButton)
	   .appendChild(document.createTextNode('1:1'))

	this.block = block;
	this.loadingImage = loadingImage;
	this.closeButton = new Limelight.Button(closeButton);
	this.fitSizeButton = new Limelight.Button(fitSizeButton);
	this.fullSizeButton = new Limelight.Button(fullSizeButton);
	this.dotByDotButton = new Limelight.Button(dotByDotButton);

	return this;
};

// }}}
// {{{ Limelight.focus()

/**
 * Focus to the Limelight block.
 *
 * @param void
 * @return void
 */
Limelight.prototype.focus = function() {
	window.scrollTo(this.blockX, this.blockY);
};

// }}}
// {{{ Limelight.toggleImageLoading()

/**
 * Toggle visibilities of the target image and the loading image.
 *
 * @param {Boolean} isLoading
 * @return void
 */
Limelight.prototype.toggleImageLoading = function(isLoading) {
	if (isLoading) {
		this.imageLoaded = false;
		this.loadingImage.style.visibility = 'visible';
		if (this.targetImage) {
			this.targetImage.style.visibility = 'hidden';
			this.targetImage.style.webkitAnimationName = '';
		}
	} else {
		this.imageLoaded = true;
		this.loadingImage.style.visibility = 'hidden';
		if (this.targetImage) {
			this.targetImage.style.visibility = 'visible';
			this.targetImage.style.webkitAnimationName = 'limelight-fadein';
		}
	}
};

// }}}
// {{{ Limelight.getScale()

/**
 * Get a scale factor.
 *
 * @param {Event} evt
 * @return {Number}
 */
Limelight.prototype.getScale = function(evt) {
	if (typeof evt.scale != 'number' || !this.enableScaling) {
		return this.scale;
	} else {
		return this.scale * evt.scale;
	}
};

// }}}
// {{{ Limelight.getRotation()

/**
 * Get a rotation angle in degrees.
 *
 * @param {Event} evt
 * @return {Number}
 */
Limelight.prototype.getRotation = function(evt) {
	if (typeof evt.rotation != 'number' || !this.enableRotation) {
		return 0;
	} else {
		return Math.round(this.rotation + evt.rotation + 360) % 360;
	}
};

// }}}
// {{{ Limelight.transform()

/**
 * Transform the image.
 *
 * @param void
 * or
 * @param {Event} evt The event object which was passwd from 'gesturechange' event.
 *                    Specifies a scale factor and a rotation angle.
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
	if (!this.imageLoaded) {
		return;
	}

	var x = this.translateX;
	var y = this.translateY;
	var scale = this.scale;
	var rotation = this.rotation;

	if (arguments.length == 1) {
		var evt = arguments[0];
		scale = this.getScale(evt);
		rotation = this.getRotation(evt);
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

	this.targetImage.style.webkitTransform = 'translate(' + x + 'px, ' + y + 'px)'
	                                       + ' scale(' + scale + ')'
	                                       + ' rotate(' + rotation + 'deg)';

	if (this.enableIndicator) {
		this.indicator.update(x - this.initialTranslateX,
		                      y - this.initialTranslateY,
		                      Math.floor(scale * 100),
		                      (rotation > 180) ? rotation - 360 : rotation);
	}
};

// }}}
// {{{ Limelight.hasChanged()

/**
 * Determine whether the image transformation state is default.
 *
 * @param void
 * @return void
 */
Limelight.prototype.hasChanged = function() {
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
// {{{ Limelight.attachEvent()

/**
 * Attach the Limelight event handler to the given element.
 *
 * @param {Node} node
 * @param {String} eventName
 * @return void
 */
Limelight.prototype.attachEvent = function(node, eventName) {
	node.addEventListener(eventName, this.handlers[eventName], false);
};

// }}}
// {{{ Limelight.detachEvent()

/**
 * Detach the Limelight event handler from the given element.
 *
 * @param {Node} node
 * @param {String} eventName
 * @return void
 */
Limelight.prototype.detachEvent = function(node, eventName) {
	node.removeEventListener(eventName, this.handlers[eventName], false);
};

// }}}
// {{{ Limelight.activate()

/**
 * Activete Limelight with the given image.
 *
 * @param {String} src
 * @return void
 */
Limelight.prototype.activate = function(src) {
	if (this.isActive) {
		this.focus();
		return;
	}

	if (this.targetImage) {
		this.block.removeChild(this.targetImage);
		this.targetImage = null;
	}

	this.toggleImageLoading(true);
	this.onOrientationChange();

	this.targetImage = document.createElement('img');
	this.targetImage.className = 'limelight-image';
	this.targetImage.setAttributes({ 'src': src, 'alt': '' });
	this.targetImage.style.visibility = 'hidden';
	this.targetImage.addEventListener('load', this.handlers.imageload, false);
	this.block.appendChild(this.targetImage);
	this.block.style.display = 'block';

	this.attachEvent(document.body, 'orientationchange')
	this.attachEvent(this.block, 'touchstart');
	this.attachEvent(this.block, 'touchmove');
	this.attachEvent(this.block, 'touchend');
	this.attachEvent(this.block, 'gesturestart');
	this.attachEvent(this.block, 'gesturechange');
	this.attachEvent(this.block, 'gestureend');

	this.isActive = true;
};

// }}}
// {{{ Limelight.deactivate()

/**
 * Deactivate Limelight.
 *
 * @param void
 * @return void
 */
Limelight.prototype.deactivate = function() {
	if (!this.isActive) {
		return;
	}

	this.block.style.display = 'none';

	if (this.targetImage) {
		this.block.removeChild(this.targetImage);
		this.targetImage = null;
	}

	this.initialScale = 0;

	this.detachEvent(document.body, 'orientationchange')
	this.detachEvent(this.block, 'touchstart');
	this.detachEvent(this.block, 'touchmove');
	this.detachEvent(this.block, 'touchend');
	this.detachEvent(this.block, 'gesturestart');
	this.detachEvent(this.block, 'gesturechange');
	this.detachEvent(this.block, 'gestureend');

	this.isActive = false;
};

// }}}
// {{{ Limelight.resetTransformation()

/**
 * Set the image transformation state to the default.
 *
 * @param void
 * @return void
 */
Limelight.prototype.resetTransformation = function() {
	if (this.initialScale == 0) {
		var x, y, width, height, origWidth, origHeight;

		x = this.blockWidth;
		y = this.blockHeight;
		width = origWidth = this.targetImage.width;
		height = origHeight = this.targetImage.height;

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
			this.fitSizeFunc = this.fitToWidth;
			this.fullSizeFunc = this.fitToHeight;
		} else {
			this.fitSizeFunc = this.fitToHeight;
			this.fullSizeFunc = this.fitToWidth;
		}
	}

	this.translateX = this.initialTranslateX;
	this.translateY = this.initialTranslateY;
	this.scale = this.initialScale;
	this.rotation = 0;
	this.transform();
};

// }}}
// {{{ Limelight.scaleTo()

/**
 * Show the target image by the given scale factor.
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
// {{{ Limelight.dotByDot()

/**
 * Show the target image in its original size.
 *
 * @param void
 * @return void
 */
Limelight.prototype.dotByDot = function() {
	this.scaleTo(1);
};

// }}}
// {{{ Limelight.fitToWidth()

/**
 * Fit the target image to the block width.
 *
 * @param void
 * @return void
 */
Limelight.prototype.fitToWidth = function() {
	this.scaleTo(this.fitToWidthScale);
};

// }}}
// {{{ Limelight.fitToHeight()

/**
 * Fit the target image to the block height.
 *
 * @param void
 * @return void
 */
Limelight.prototype.fitToHeight = function() {
	this.scaleTo(this.fitToHeightScale);
};

// }}}
// {{{ Limelight.initialSize()

/**
 * Show the target image in its initial size.
 *
 * @param void
 * @return void
 */
Limelight.prototype.initialSize = function() {
	this.scaleTo(this.initialScale);
};

// }}}
// {{{ Limelight.onOrientationChange()

/**
 * The 'orientationchange' event handler.
 *
 * @param void
 * @return void
 */
Limelight.prototype.onOrientationChange = function() {
	var x, y, width, height;

	x = this.blockX = 0;
	y = this.blockY = window.scrollY;
	if (typeof window.orientation != 'number' || window.orientation % 180 == 0) {
		width = 320;
		height = 480 - 20 - 44;
	} else {
		width = 480;
		height = 320 - 20 - 32;
	}

	this.blockWidth = width;
	this.blockHeight = height;
	this.movableArea = new Limelight.Rect(new Limelight.Point(-width, -height),
	                                      new Limelight.Point(width, height));

	this.block.setStyles({
		'left': x + 'px',
		'top': y + 'px',
		'width': width + 'px',
		'height': height + 'px'
	});

	this.loadingImage.style.marginTop = Math.floor((height - 32) / 2) + 'px';

	this.initialScale = 0;

	if (this.targetImage) {
		this.resetTransformation();
	}

	this.focus();
};

// }}}
// {{{ Limelight.onTouchStart()

/**
 * The 'touchstart' event handler.
 *
 * @param {Event} evt
 * @return void
 */
Limelight.prototype.onTouchStart = function(evt) {
	Limelight.util.stopEvent(evt);
	if (evt.touches.length != 1) {
		return;
	}

	this.startX = this.endX = evt.touches[0].screenX;
	this.startY = this.endY = evt.touches[0].screenY;

	if (this.enableIndicator) {
		this.indicator.show();
	}
};

// }}}
// {{{ Limelight.onTouchMove()

/**
 * The 'touchmove' event handler.
 *
 * @param {Event} evt
 * @return void
 */
Limelight.prototype.onTouchMove = function(evt) {
	Limelight.util.stopEvent(evt);
	if (evt.touches.length != 1) {
		return;
	}

	this.endX = evt.touches[0].screenX;
	this.endY = evt.touches[0].screenY;
	var deltaX = this.endX - this.startX;
	var deltaY = this.endY - this.startY;
	if (this.movableArea.contains(new Limelight.Point(deltaX, deltaY))) {
		this.transform(this.translateX + deltaX, this.translateY + deltaY);
	}
};

// }}}
// {{{ Limelight.onTouchEnd()

/**
 * The 'touchend' event handler.
 *
 * @param {Event} evt
 * @return void
 */
Limelight.prototype.onTouchEnd = function(evt) {
	Limelight.util.stopEvent(evt);

	var deltaX = this.endX - this.startX;
	var deltaY = this.endY - this.startY;
	var delta = new Limelight.Point(deltaX, deltaY);

	if (this.movableArea.contains(delta)) {
		this.translateX += deltaX;
		this.translateY += deltaY;
	}

	if (this.clickRect.contains(delta)) {
		if (this.closeButton.isTargetOf(evt)) {
			this.deactivate();
		} else if (this.fitSizeButton.isTargetOf(evt)) {
			this.fitSizeFunc();
		} else if (this.fullSizeButton.isTargetOf(evt)) {
			this.fullSizeFunc();
		} else if (this.dotByDotButton.isTargetOf(evt)) {
			this.dotByDot();
		} else {
			this.focus();
			if (this.enableDobuleTap) {
				var now = (new Date()).getTime();
				if (now - this.previousClick < 250) {
					if (this.hasChanged()) {
						this.initialSize();
					} else {
						this.dotByDot();
					}
				}
				this.previousClick = now;
			}
		}
	}

	if (this.enableIndicator) {
		this.indicator.hide();
	}
};

// }}}
// {{{ Limelight.onGestureStart()

/**
 * The 'gesturestart' event handler.
 *
 * @param {Event} evt
 * @return void
 */
Limelight.prototype.onGestureStart = function(evt) {
	Limelight.util.stopEvent(evt);
	if (this.enableIndicator) {
		this.indicator.show();
	}
};

// }}}
// {{{ Limelight.onGestureChange()

/**
 * The 'gesturechange' event handler.
 *
 * @param {Event} evt
 * @return void
 */
Limelight.prototype.onGestureChange = function(evt) {
	Limelight.util.stopEvent(evt);
	this.transform(evt);
};

// }}}
// {{{ Limelight.onGestureEnd()

/**
 * The 'gestureend' event handler.
 *
 * @param {Event} evt
 * @return void
 */
Limelight.prototype.onGestureEnd = function(evt) {
	Limelight.util.stopEvent(evt);
	this.scale = this.getScale(evt);
	this.rotation = this.getRotation(evt);

	if (this.enableIndicator) {
		this.indicator.hide();
	}
};

// }}}
// {{{ Limelight.inject()

/**
 *
 *
 * @param {String} className
 * @param {Node} contextNode
 * @return void
 */
Limelight.prototype.inject = function() {
	var self = this;
	var className = 'limelight';
	var contextNode = document.body;

	if (arguments.length > 0) {
		className = arguments[0];
		if (className.length == 0 || className.charCodeAt(0) < 0x41 ||
			className.search(/[^-0-9A-Z_a-z\u0080-\uffff]/) != -1)
		{
			throw new Limelight.Exception('UnexpectedValue', 'Invalid class name specified');
		}
		if (arguments.length > 1) {
			contextNode = arguments[1];
		}
	}

	var result = document.evaluate('.//a[@href and contains('
	                               + ["concat(' ', normalize-space(@class), ' ')",
	                                  "' " + className + " '"].join(', ')
	                               + ')]',
	                               contextNode,
	                               null,
	                               XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
	                               null);
	var l = result.snapshotLength;
	if (l > 0) {
		var callback = function(evt) {
			Limelight.util.stopEvent(evt);
			self.activate(this.href);
			return false;
		};

		for (var i = 0; i < l; i++) {
			result.snapshotItem(i).addEventListener('click', callback, false);
		}
	}
};

// }}}

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
