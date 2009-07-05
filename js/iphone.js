/**
 * rep2expack - DOMを操作してiPhoneに最適化する
 */

// {{{ globals

var iutil = {
	/**
	 * クライアントがiPhoneかどうか
	 * @type {Boolean}
	 */
	'iphone': (/iP(hone|od)/).test(navigator.userAgent),
	/**
	 * 内部リンクの正規表現
	 * @type {RegExp}
	 */
	'internalLinkPattern': /^([a-z]\w+)\.php\?/,
	/**
	 * 外部リンクの正規表現
	 * @type {RegExp}
	 */
	'externalLinkPattern': /^https?:\/\/([^\/]+?@)?([^:\/]+)/,
	/**
	 * リンクホールドのための変数コンテナ
	 * @type {Object}
	 */
	'hold': {
		'duration': 1000,
		'timeoutId': -1,
		'callbacks': {},
		'dialogs': {},
		'dialog': null,
		'anchor': null,
		'query': null,
		'href': null,
		'uri': null
	}
};

// }}}
// {{{ modifyExternalLink()

/**
 * 外部リンクを確認してから新しいタブで開くように変更する
 *
 * @param {Node|String} contextNode
 * @return void
 */
iutil.modifyExternalLink = function(contextNode) {
	var anchors, node, re, i, l, m;

	switch (typeof contextNode) {
		case 'string':
			contextNode = document.getElementById(contextNode);
			break;
		case 'undefined':
			contextNode = document.body;
			break;
	}
	if (!contextNode) {
		return;
	}

	anchors = document.evaluate('.//a[starts-with(@href, "http")]',
	                            contextNode, null,
	                            XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
	l = anchors.snapshotLength;
	re = iutil.externalLinkPattern;

	for (i = 0; i < l; i++) {
		anchor = anchors.snapshotItem(i);
		m = re.exec(anchor.getAttribute('href'));

		if (m !== null && m[2] != location.host) {
			if (typeof anchor.onclick !== 'function') {
				anchor.onclick = iutil.confirmOpenExternalLink;
			}

			if (!anchor.hasAttribute('target')) {
				anchor.setAttribute('target', '_blank');
			}
		}
	}
};

// }}}
// {{{ confirmOpenExternalLink()

/**
 * 外部サイトを開くかどうかを確認する
 *
 * @param void
 * @return {Boolean}
 */
iutil.confirmOpenExternalLink = function() {
	var url, title;

	url = this.href;

	if (this.hasAttribute('title')) {
		title = this.getAttribute(title);
	} else if (this.hasChildNodes() &&
		this.firstChild.nodeType == 3 &&
		this.firstChild.nodeValue.search(/^h?t?tps?:\/\/[^\/]/) != -1)
	{
		title = this.firstChild.nodeValue;
		switch (title.indexOf('tp')) {
			case 0:
				title = 'ht' + title;
				break;
			case 1:
				title = 'h' + title;
				break;
		}
	} else {
		title = '';
	}

	if (!title.length || title == url) {
		return window.confirm('外部サイトを開きますか?\nURL: ' + url);
	} else {
		return window.confirm('外部サイトを開きますか?\nURL: ' + url + '\n(' + title + ')');
	}
};


// }}}
// {{{ toggleChekcbox()

/**
 * チェックボックスをトグルする
 *
 * @param {Node} node
 * @param {Event} evt
 * @return void
 */
iutil.toggleChekcbox = function(node, evt) {
	if (node && node.nodeType === 1 && typeof node.checked != 'undefined') {
		node.checked = !node.checked;
		if (typeof node.onclick == 'function') {
			node.onclick(evt);
		}
		if (typeof node.onchange == 'function') {
			node.onchange(evt);
		}
	}
};

// }}}
// {{{ checkPrev()

/**
 * 前のチェックボックスをトグルする。疑似label効果
 *
 * @param {Element|String} elem
 * @param {Event} evt
 * @return void
 */
iutil.checkPrev = function(elem, evt) {
	elem = (typeof elem == 'string') ? document.getElementById(elem) : elem;
	iutil.toggleChekcbox(elem.previousSibling, evt);
};

// }}}
// {{{ checkNext()

/**
 * 次のチェックボックスをトグルする。疑似label効果
 *
 * @param {Element|String} elem
 * @param {Event} evt
 * @return void
 */
iutil.checkNext = function(elem, evt) {
	elem = (typeof elem == 'string') ? document.getElementById(elem) : elem;
	iutil.toggleChekcbox(elem.nextSibling, evt);
};

// }}}
// {{{ adjustTextareaSize()

/**
 * textareaの幅を最大化する
 *
 * @return void
 */
iutil.adjustTextareaSize = function() {
	var areas, width, i, l;

	areas = document.body.getElementsByTagName('textarea');
	l = (areas) ? areas.length : 0;

	for (i = 0; i < l; i++) {
		width = areas[i].parentNode.clientWidth;
		if (width > 100) {
			width -= 12; // (borderWidth + padding) * 2
			if (width > 480) {
				width = 480; // maxWidth
			}
			areas[i].style.width = width + 'px';
		}
	}
};

// }}}
// {{{ shrinkTextarea()

/**
 * textareaの高さを小さくする
 *
 * @param {Element|String} elem
 * @return void
 */
iutil.shrinkTextarea = function(elem) {
	var rows;

	elem = (typeof elem == 'string') ? document.getElementById(elem) : elem;
	if (!elem) {
		return;
	}

	//var y = elem.clientHeight;
	rows = elem.hasAttribute('rows') ? parseInt(elem.getAttribute('rows'), 10) : 3;
	rows = Math.max(rows - 1, 3);
	elem.setAttribute('rows', rows.toString());
	//window.scrollBy(0, elem.clientHeight - y);
};

// }}}
// {{{ expandTextarea()

/**
 * textareaの高さを大きくする
 *
 * @param {Element|String} elem
 * @return void
 */
iutil.expandTextarea = function(elem) {
	var rows;

	elem = (typeof elem == 'string') ? document.getElementById(elem) : elem;
	if (!elem) {
		return;
	}

	//var y = elem.clientHeight;
	rows = elem.hasAttribute('rows') ? parseInt(elem.getAttribute('rows'), 10) : 3;
	rows = Math.max(rows + 1, 3);
	elem.setAttribute('rows', rows.toString());
	//window.scrollBy(0, elem.clientHeight - y);
};

// }}}
// {{{ toggleAutocorrect()

/**
 * フォームのautocorrectの有効・無効を切り替える
 *
 * @param {Element|String} elem
 * @param {Boolean} toggle
 * @return void
 */
iutil.toggleAutocorrect = function(elem, toggle) {
	elem = (typeof elem == 'string') ? document.getElementById(elem) : elem;
	if (!elem) {
		return;
	}

	elem.setAttribute('autocorrect', (toggle ? 'on' : 'off'));
};

// }}}
// {{{ changeLinkTarget()

/**
 * リンクターゲットを切り替える
 *
 * @param {String|Array} expr
 * @param {Boolean} toggle
 * @param {Node|String} contextNode
 * @param {String} target
 * @return void
 */
iutil.changeLinkTarget = function(expr, toggle, contextNode) {
	var anchors, args, i, l;

	switch (typeof contextNode) {
		case 'string':
			contextNode = document.getElementById(contextNode);
			break;
		case 'undefined':
			contextNode = document.body;
			break;
	}

	if (typeof expr != 'string') {
		args = [toggle, contextNode];
		if (arguments.length > 3) {
			args.push(arguments[3]);
		}
		l = expr.length;
		for (i = 0; i < l; i++) {
			args.unshift(expr[i]);
			iutil.changeLinkTarget.apply(this, args);
			args.shift();
		}
		return;
	}

	anchors = document.evaluate(expr,
	                            contextNode,
	                            null,
	                            XPathResult.ORDERED_NODE_SNAPSHOT_TYPE,
	                            null);

	l = anchors.snapshotLength;

	if (toggle) {
		for (i = 0; i < l; i++) {
			anchors.snapshotItem(i).setAttribute('target', '_blank');
		}
	} else if (arguments.length > 3) {
		for (i = 0; i < l; i++) {
			anchors.snapshotItem(i).setAttribute('target', arguments[3]);
		}
	} else {
		for (i = 0; i < l; i++) {
			anchors.snapshotItem(i).removeAttribute('target');
		}
	}
};

// }}}
// {{{ getTextNodes()

/**
 * 指定されたノードに含まれているテキストノードのリストを取得する
 *
 * @param {Node} node
 * @param {Boolean} needsValue
 * @param {Array} texts
 * @return {Array}
 */
iutil.getTextNodes = function(node, needsValue, texts) {
	var i, l;

	if (typeof texts == 'undefined') {
		texts = [];
	}

	switch (node.nodeType) {
		case 1:
			l = node.childNodes.length;
			for (i = 0; i < l; i++) {
				iutil.getTextNodes(node.childNodes[i], needsValue, texts);
			}
			break;
		case 3:
			texts.push((needsValue) ? node.nodeValue : node);
			break;
	}

	return texts;
};

// }}}
// {{{ httpGetText()

/**
 * GETリクエストの結果をテキストとして取得する
 *
 * @param {String} uri
 * @return {String|null}
 */
iutil.httpGetText = function(uri) {
	var req, err;
	try {
		var req = new XMLHttpRequest();
		req.open('GET', uri, false);
		req.send(null);

		if (req.readyState == 4) {
			if (req.status == 200) {
				return req.responseText;
			}
		}
	} catch (err) {
		// pass
	}
	return null;
};

// }}}
// {{{ stopEvent()

/**
 * デフォルトイベントの発生とイベントの伝播を抑制する
 *
 * @param {Event} event
 * @return {false}
 */
iutil.stopEvent = function(event) {
	event.preventDefault();
	event.stopPropagation();
	return false;
};

// }}}
// {{{ hold.onTouchStart()

/**
 * リンクホールド・タッチ/マウス押し下げ時のイベントハンドラ
 * ダイアログ表示タイマーをセットする
 *
 * @param {Event} event
 * @param {Element} target
 * @return void
 */
iutil.hold.onTouchStart = function(event, target) {
	var x, y;

	event = event || window.event;

	if (event.targetTouches) {
		if (!event.targetTouches.length) {
			return;
		}
		x = event.targetTouches[0].pageX;
		y = event.targetTouches[0].pageY;
	} else {
		x = iutil.getPageX(event);
		y = iutil.getPageY(event);
	}

	if (!target) {
		if (event.currentTarget) {
			target = event.currentTarget;
		} else {
			if (event.target) {
				target = event.target;
			} else {
				target = event.srcElement;
			}
			while (target && (target.nodeType != 1 || target.nodeName.toLowerCase() !== 'a')) {
				target = target.parentNode;
			}
			if (!target) {
				return;
			}
		}
	}

	if (iutil.hold.timeoutId != -1) {
		window.clearTimeout(iutil.hold.timeoutId);
	}

	iutil.hold.timeoutId = window.setTimeout(iutil.hold.showDialog,
	                                         iutil.hold.duration,
	                                         target, x, y);
};

// }}}
// {{{ hold.onTouchMove()

/**
 * リンクホールド・ムーブ/ドラッグ時のイベントハンドラ
 * タッチ/カーソルが移動したならダイアログ表示タイマーをキャンセルする
 *
 * @param {Event} event
 * @return void
 */
iutil.hold.onTouchMove = function(event) {
	if (iutil.hold.timeoutId != -1) {
		window.clearTimeout(iutil.hold.timeoutId);
		iutil.hold.timeoutId = -1;
	}
};

// }}}
// {{{ hold.onClick()

/**
 * リンクホールド・クリック時のイベントハンドラ
 * ダイアログが表示されたならクリックをキャンセルする
 *
 * @param {Event} event
 * @return void
 */
iutil.hold.onClick = function(event) {
	if (iutil.hold.timeoutId != -1) {
		window.clearTimeout(iutil.hold.timeoutId);
		iutil.hold.timeoutId = -1;
		return true;
	} else {
		iutil.stopEvent(event || window.event);
		return false;
	}
};

// }}}
// {{{ hold.showDialog()

/**
 * ダイアログを表示する
 *
 * @param {Element} anchor
 * @param {Number} x
 * @param {Number} y
 * @return void
 */
iutil.hold.showDialog = function(anchor, x, y) {
	var hold, dialog, div, text, button, m, p, left;

	hold = iutil.hold;
	hold.timeoutId = -1;
	hold.anchor = anchor;
	hold.href = anchor.getAttribute('href');
	hold.uri = anchor.href;

	hold.hideDialog();

	m = iutil.internalLinkPattern.exec(anchor.getAttribute('href'));
	if (m === null) {
		return;
	}

	hold.query = hold.href.substring(m[0].length);
	p = hold.query.indexOf('#');
	if (p !== -1) {
		hold.query = hold.query.substring(0, p);
	}

	// 特定のコールバック関数があるとき
	if (typeof hold.callbacks[m[1]] === 'function') {
		hold.callbacks[m[1]](anchor, event);
		return;
	}

	// デフォルトのダイアログを表示する
	if (typeof hold.dialogs._default === 'undefined') {
		dialog = document.createElement('div');
		dialog.className = 'popup-dialog';

		// リンクテキスト
		div = dialog.appendChild(document.createElement('div'));
		div.className = 'popup-dialog-text';
		div.appendChild(document.createTextNode('-'));

		// ボタン類
		div = dialog.appendChild(document.createElement('div'));
		div.className = 'popup-dialog-buttons';

		button = div.appendChild(document.createElement('input'));
		button.setAttribute('type', 'button');
		button.value = 'リンクを開く';
		button.onclick = hold.openUri;

		div.appendChild(document.createTextNode('\u3000'));

		button = div.appendChild(document.createElement('input'));
		button.setAttribute('type', 'button');
		button.value = 'タブで開く';
		button.onclick = hold.openUriInTab;

		// 「閉じる」ボタン
		button = dialog.appendChild(document.createElement('img'));
		button.className = 'close-button';
		button.setAttribute('src', 'img/iphone/close.png');
		button.onclick = hold.hideDialog;

		hold.dialogs._default = document.body.appendChild(dialog);
	} else {
		dialog = hold.dialogs._default;
	}
	hold.setActiveDialog(dialog);

	text = dialog.firstChild.firstChild;
	text.nodeValue = iutil.getTextNodes(anchor, true).join('').replace(/\s+/g, ' ')
	               + ' (' + m[1] + '.php)';

	dialog.style.display = 'block';
	left = iutil.getWindowWidth() - iutil.parsePixels(iutil.getCurrentStyle(dialog).width) - 10;
	dialog.style.top = (y + 5) + 'px';
	dialog.style.left = Math.min(x, Math.max(0, left)) + 'px';
};

// }}}
// {{{ hold.hideDialog()

/**
 * アクティブなダイアログを隠す
 *
 * @param void
 * @return void
 */
iutil.hold.hideDialog = function() {
	if (iutil.hold.dialog) {
		iutil.hold.dialog.style.display = 'none';
		iutil.hold.setActiveDialog(null);
	}
};

// }}}
// {{{ hold.setActiveDialog()

/**
 * アクティブなダイアログを設定する
 *
 * @param {Element|null} element
 * @return void
 */
iutil.hold.setActiveDialog = function(element) {
	iutil.hold.dialog = element;
};

// }}}
// {{{ hold.openUri()

/**
 * リンクを開く
 *
 * @param void
 * @return void
 */
iutil.hold.openUri = function() {
	window.location.href = iutil.hold.uri;
};

// }}}
// {{{ hold.openUriInTab()

/**
 * 新しいタブでリンクを開く
 *
 * @param void
 * @return void
 */
iutil.hold.openUriInTab = function() {
	window.open(iutil.hold.uri, null);
};

// }}}
// {{{ hold.bind()

/**
 * リンクにホールドイベントハンドラを登録する
 *
 * @param {Element} anchor
 * @return void
 */
if (iutil.iphone) {
	iutil.hold.bind = function(anchor) {
		anchor.addEventListener('touchstart', iutil.hold.onTouchStart, false);
		anchor.addEventListener('touchmove',  iutil.hold.onTouchMove, false);
		anchor.addEventListener('click',      iutil.hold.onClick, false);
	};
} else {
	iutil.hold.bind = function(anchor) {
		anchor.addEventListener('mousedown', iutil.hold.onTouchStart, false);
		anchor.addEventListener('drag',      iutil.hold.onTouchMove, false);
		anchor.addEventListener('click',     iutil.hold.onClick, false);
	};
}

// }}}
// {{{ parsePixels(), isStaticLayout()

iutil.parsePixels = function(value) {
	var n = 0;

	switch (typeof value) {
		case 'number':
			n = Math.floor(value);
			break;
		case 'string':
			if (value.length > 2 && value.indexOf('px') === value.length - 2) {
				n = parseInt(value);
				if (!isFinite(n)) {
					n = 0;
				}
			}
			break;
	}

	return n;
};

iutil.isStaticLayout = function(element) {
	switch (iutil.getCurrentStyle(element).position) {
		case 'absolute':
		case 'relative':
		case 'fixed':
			return false;
		default:
			return true;
	}
};

// }}}
// {{{ getCurrentStyle(), getTargetNode(), getScrollXY()

if (document.all && !window.opera) {
	// {{{ IE

	iutil.getCurrentStyle = function(element) {
		return element.currentStyle;
	};

	iutil.getTargetNode = function(event) {
		var target = event.srcElement;
		while (target.nodeType !== 1) {
			target = target.parentNode;
		}
		return target;
	};

	if (document.compatMode === 'BackCompat') {
		iutil.getScrollX = function() { return document.body.scrollLeft; };
		iutil.getScrollY = function() { return document.body.scrollTop; };
		iutil.getScrollXY = function() {
			return [document.body.scrollLeft, document.body.scrollTop];
		};
	} else {
		iutil.getScrollX = function() { return document.documentElement.scrollLeft; };
		iutil.getScrollY = function() { return document.documentElement.scrollTop; };
		iutil.getScrollXY = function() {
			return [document.documentElement.scrollLeft, document.documentElement.scrollTop];
		};
	}

	// }}}
} else {
	// {{{ Others

	iutil.getCurrentStyle = function(element) {
		return document.defaultView.getComputedStyle(element, '');
	};

	iutil.getTargetNode = function(event) {
		var target = event.target;
		while (target.nodeType !== 1) {
			target = target.parentNode;
		}
		return target;
	};

	if (typeof window.scrollX === 'number') {
		iutil.getScrollX = function() { return window.scrollX; };
		iutil.getScrollY = function() { return window.scrollY; };
		iutil.getScrollXY = function() {
			return [window.scrollX, window.scrollY];
		};
	} else {
		iutil.getScrollX = function() { return window.pageXOffset; };
		iutil.getScrollY = function() { return window.pageYOffset; };
		iutil.getScrollXY = function() {
			return [window.pageXOffset, window.pageYOffset];
		};
	}

	// }}}
}

// }}}
// {{{ getWindowWidth(), getWindowHeight(), getWindowSize()

if (typeof document.compatMode === 'undefined') {
	// Safari <= 2.x, etc.
	iutil.getWindowWidth  = function() { return window.innerWidth; };
	iutil.getWindowHeight = function() { return window.innerHeight; };
	iutil.getWindowSize = function() {
		return [window.innerWidth, window.innerHeight, document.width, document.height];
	};
} else if (document.compatMode === 'BackCompat') {
	// Backward Compatibility Mode
	iutil.getWindowWidth  = function() { return document.body.clientWidth; };
	iutil.getWindowHeight = function() { return document.body.clientHeight; };
	iutil.getWindowSize = function() {
		return [document.body.clientWidth, document.body.clientHeight,
		        document.body.scrollWidth, document.body.scrollHeight];
	};
} else {
	// Standard Mode
	iutil.getWindowWidth  = function() { return document.documentElement.clientWidth; };
	iutil.getWindowHeight = function() { return document.documentElement.clientHeight; };
	iutil.getWindowSize = function() {
		return [document.documentElement.clientWidth, document.documentElement.clientHeight,
		        document.documentElement.scrollWidth, document.documentElement.scrollHeight];
	};
}

// }}}
// {{{ getOffsetXY(), getLayerXY(), getPageXY()

// Common
iutil.getOffsetX = function(event) { return event.offsetX; };
iutil.getOffsetY = function(event) { return event.offsetY; };
iutil.getOffsetXY = function(event) {
	return [event.offsetX, event.offsetY];
};

iutil.getLayerX = function(event) { return event.layerX; };
iutil.getLayerY = function(event) { return event.layerY; };
iutil.getLayerXY = function(event) {
	return [event.layerX, event.layerY];
};

iutil.getPageX = function(event) { return event.pageX; };
iutil.getPageY = function(event) { return event.pageY; };
iutil.getPageXY = function(event) {
	return [event.pageX, event.pageY];
};

if (window.opera) {
	// {{{ Opera

	iutil.getOffsetX = function(event) { return iutil.getOffsetXY(event)[0]; };
	iutil.getOffsetY = function(event) { return iutil.getOffsetXY(event)[1]; };
	iutil.getOffsetXY = function(event) {
		var style = iutil.getCurrentStyle(iutil.getTargetNode(event));
		return [event.offsetX + iutil.parsePixels(style.borderLeftWidth) + iutil.parsePixels(style.paddingLeft),
		        event.offsetY + iutil.parsePixels(style.borderTopWidth)  + iutil.parsePixels(style.paddingTop)];
	};

	iutil.getLayerX = function(event) { return iutil.getLayerXY(event)[0]; };
	iutil.getLayerY = function(event) { return iutil.getLayerXY(event)[1]; };
	iutil.getLayerXY = function(event) {
		var target = iutil.getTargetNode(event);
		var offset = iutil.getOffsetXY(event);
		if (iutil.isStaticLayout(target) && target.offsetParent) {
			offset[0] += target.offsetLeft;
			offset[1] += target.offsetTop;
		}
		return offset;
	};

	// }}}
} else if (document.all) {
	// {{{ IE

	iutil.getOffsetX = function(event) { return iutil.getOffsetXY(event)[0]; };
	iutil.getOffsetY = function(event) { return iutil.getOffsetXY(event)[1]; };
	iutil.getOffsetXY = function(event) {
		var style = iutil.getCurrentStyle(iutil.getTargetNode(event));
		return [event.offsetX + iutil.parsePixels(style.borderLeftWidth),
		        event.offsetY + iutil.parsePixels(style.borderTopWidth)];
	};

	iutil.getLayerX = function(event) { return iutil.getLayerXY(event)[0]; };
	iutil.getLayerY = function(event) { return iutil.getLayerXY(event)[1]; };
	iutil.getLayerXY = function(event) {
		var target = iutil.getTargetNode(event);
		var offset = iutil.getOffsetXY(event);
		if (iutil.isStaticLayout(target) && target.offsetParent) {
			offset[0] += target.offsetLeft;
			offset[1] += target.offsetTop;
		}
		return offset;
	};

	iutil.getPageX = function(event) {
		return event.clientX + iutil.getScrollX();
	};
	iutil.getPageY = function(event) {
		return event.clientY + iutil.getScrollY();
	};
	iutil.getPageXY = function(event) {
		return [event.clientX + iutil.getScrollX(), event.clientY + iutil.getScrollY()];
	};

	// }}}
} else if (navigator.userAgent.indexOf('AppleWebKit') === -1) {
	// {{{ Firefox and other non WebKit browsers

	iutil.getOffsetX = function(event) { return iutil.getOffsetXY(event)[0]; };
	iutil.getOffsetY = function(event) { return iutil.getOffsetXY(event)[1]; };
	iutil.getOffsetXY = function(event) {
		var target = iutil.getTargetNode(event);
		var offsetX = event.layerX;
		var offsetY = event.layerY;
		if (iutil.isStaticLayout(target) && target.offsetParent) {
			var style = iutil.getCurrentStyle(target.offsetParent);
			offsetX -= target.offsetLeft + iutil.parsePixels(style.borderLeftWidth);
			offsetY -= target.offsetTop  + iutil.parsePixels(style.borderTopWidth);
		}
		return [offsetX, offsetY];
	};

	// }}}
}

// }}}

// }}}
// {{{ DOMContentLoaded

window.addEventListener('DOMContentLoaded', function(event) {
	window.removeEventListener('DOMContentLoaded', arguments.callee, false);

	if (typeof window.iphone_js_no_modification === 'undefined' || !window.iphone_js_no_modification) {
		// リンクにイベントハンドラを登録する
		iutil.modifyExternalLink(document.body);

		// textareaの幅を調整
		iutil.adjustTextareaSize();

		// 回転時のイベントハンドラを設定
		document.body.addEventListener('orientationchange', iutil.adjustTextareaSize, false);
	}

	// ロケーションバーを隠す
	if (typeof window.iui !== 'undefined') {
		window.scrollTo(0, 1);
	} else if (!window.location.hash.length && iutil.getScrollX() < 1) {
		window.scrollTo(0, 1);
	}
}, false);

// }}}

/*
 * Local Variables:
 * mode: javascript
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: t
 * End:
 */
/* vim: set syn=javascript fenc=cp932 ai noet ts=4 sw=4 sts=4 fdm=marker: */
