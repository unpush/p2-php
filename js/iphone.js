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
	 * リンクスライディングのための変数コンテナ
	 * @type {Object}
	 */
	'sliding': {
		'start': -1,
		'startX': -1,
		'startY': -1,
		'endX': -1,
		'endY': -1,
		'target': null,
		'callbacks': {},
		'dialogs': {},
		'dialog': null,
		'anchor': null,
		'query': null,
		'href': null,
		'uri': null
	},
	/**
	 * ラベルクリックのコールバック関数コンテナ
	 * @type {Object}
	 */
	'labelActions': {},
	/**
	 * accesskey属性持ちのアンカー要素コンテナ
	 * @type {Object}
	 */
	'accessKeys': {}
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
	var anchors, anchor, re, i, l, m;

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
// {{{ setLabelAction()

/**
 * for属性つきのlabel要素にクリック時のイベントハンドラを登録する
 *
 * iPhoneのSafariがlabel要素をサポートすればこの関数は不要になる
 *
 * @param {Node|String} contextNode
 * @return void
 */
iutil.setLabelAction = function(contextNode) {
	var labels, label, targetId, targetElement, i, l;

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

	labels = document.evaluate('.//label[@for]',
	                           contextNode, null,
	                           XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
	l = labels.snapshotLength;

	for (i = 0; i < l; i++) {
		label = labels.snapshotItem(i);
		targetId = label.getAttribute('for');
		targetElement = document.getElementById(targetId);
		if (!targetElement) {
			continue;
		}

		if (typeof iutil.labelActions[targetId] != 'function') {
			if (targetElement.nodeName.toLowerCase() == 'input' &&
				targetElement.hasAttribute('type') &&
				targetElement.getAttribute('type').toLowerCase() == 'checkbox')
			{
				iutil.labelActions[targetId] = (function(element) {
					return function(event){
						event = event || window.event;
						iutil.toggleChekcbox(element, event);
						return false;
					};
				})(targetElement);
			} else {
				iutil.labelActions[targetId] = (function(element) {
					return function(){
						element.forcus();
						return false;
					};
				})(targetElement);
			}
		}

		label.onclick = iutil.labelActions[targetId];
	}
};

// }}}
// {{{ setHashScrool()

/**
 * ページ内リンクのクリックをスクロールにする
 *
 * あまり動きがよくなかったので封印。
 *
 * @param {Node|String} contextNode
 * @return void
 */
iutil.setHashScrool = function(contextNode) {
	var anchors, anchor, targetId, targetElement, i, l, expr;

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

	expr = './/a[starts-with(@href, "#") and ('
		+ '@href = "#header" or @href = "#footer" or @href = "#top" or @href = "#bottom"'
		+ ' or contains(concat(" ", @class, " "), " button ")'
		+ ')]';
	anchors = document.evaluate(expr,
	                            contextNode, null,
	                            XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
	l = anchors.snapshotLength;

	for (i = 0; i < l; i++) {
		anchor = anchors.snapshotItem(i);
		targetId = anchor.getAttribute('href').substring(1);
		targetElement = document.getElementById(targetId);
		if (!targetElement) {
			continue;
		}

		anchor.onclick = (function (element) {
			return function (event) {
				var from, to, d, e, f, g;

				from = iutil.getPageY(event || window.event);
				to = element.offsetTop;
				d = to - from;
				e = 30;
				f = 30;

				for (g = 1; g < f; g++) {
					window.setTimeout(window.scrollTo, e * g,
						0, from + Math.floor(d * Math.sqrt(g / f)));
				}
				window.setTimeout(window.scrollTo, e * g, 0, to);

				return false;
			};
		})(targetElement);
	}
};

// }}}
// {{{ setAccessKeys()

/**
 * 修飾キー + [0-9*#] の組み合わせのキーバインドを登録する
 *
 * OS/ブラウザデフォルトキーバインドとの衝突が解決できていないので封印。
 *
 * @param {Node|String} contextNode
 * @return void
 */
iutil.setAccessKeys = function(contextNode) {
	var anchors, anchor, accessKey, hashKey, i, l;

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

	anchors = document.evaluate('.//a[@accesskey]',
	                            contextNode, null,
	                            XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
	l = anchors.snapshotLength;

	for (i = 0; i < l; i++) {
		anchor = anchors.snapshotItem(i);
		accessKey = anchor.getAttribute('accesskey');
		hashKey = 'a' + accessKey.charCodeAt(0).toString();
		iutil.accessKeys[hashKey] = anchor;
	}

	// キー押し下げ時のイベントハンドラを追加
	window.addEventListener('keypress', function (event) {
		var accessKey, hashKey, keyCode, clickEvent;

		event = event || window.event;

		// バインドが割り当てられている修飾キーか調べる
		if (!(event.ctrlKey || event.metaKey)) {
			return true;
		}

		// バインドが割り当てられているキーか調べる
		keyCode = event.keyCode;
		if (48 <= keyCode && keyCode <= 57 && !event.shiftKey) { // [0-9]
			accessKey = (keyCode - 48).toString();
		// '<' を '*' にマップ
		} else if (keyCode === 60 || (keyCode === 44 && event.shiftKey)) {
			accessKey = '*';
		// '>' を '#' にマップ
		} else if (keyCode === 62 || (keyCode === 46 && event.shiftKey)) {
			accessKey = '#';
		} else {
			return true;
		}

		// 操作感統一のため、アクセスキーの有無に関わらずイベントの伝播を止める
		iutil.stopEvent(event);

		// アクセスキーがあればクリックイベントを発生させる
		hashKey = 'a' + accessKey.charCodeAt(0).toString();
		if (typeof iutil.accessKeys[hashKey] !== 'undefined') {
			clickEvent = document.createEvent('MouseEvents');
			clickEvent.initMouseEvent(
				'click', // type
				true, // bubbles
				true, // cancelable
				window, // view
				1, // detail
				0, // screenX
				0, // screenY
				0, // clientX
				0, // clientY
				false, // ctrlKey
				false, // altKey
				false, // shiftKey
				false, // metaKey
				0, // button
				null // relatedTarget
			);
			iutil.accessKeys[hashKey].dispatchEvent(clickEvent);
		}

		return false;
	}, true);
};

// ]}}
// {{{ showAccessKeys()

/**
 * アクセスキーの一覧を表示する
 *
 * @param void
 * @return void
 */
iutil.showAccessKeys = function () {
	var i, l, accessKeys, accessKey, hashKey, toolbar, button, bodyStyle;

	bodyStyle = iutil.getCurrentStyle(document.body);

	// ツールバー要素を作成
	toolbar = document.createElement('div');
	// ツールバースタイル
	toolbar.style.width = '100%';
	toolbar.style.margin = '0 -' + bodyStyle.paddingRight + ' 0 -' + bodyStyle.paddingLeft;
	toolbar.style.padding = '5px !important';
	toolbar.style.fontSize = '12px';
	toolbar.style.lineHeight = '120%';
	toolbar.style.color = '#fff';
	toolbar.style.backgroundColor = '#333';
	toolbar.style.position = 'fixed';
	toolbar.style.bottom = '0';

	// ラベル
	toolbar.appendChild(document.createElement('span'));
	toolbar.firstChild.appendChild(document.createTextNode('Ctrl'));

	// 各アクセスキーの情報を追加
	accessKeys = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '<', '>'];
	l = accessKeys.length;
	for (i = 0; i < l; i++) {
		accessKey = accessKeys[i];
		hashKey = 'a' + accessKey.charCodeAt(0).toString();
		if (typeof iutil.accessKeys[hashKey] !== 'undefined') {
			button = document.createElement('span');
			// ボタンスタイル
			button.style.cursor = 'pointer';
			button.style.display = 'inline-block';
			button.style.marginLeft = '5px';
			button.style.textDecoration = 'underline';
			button.style.whiteSpace = 'nowrap';
			// ラベル
			button.appendChild(document.createTextNode(accessKey + ':' +
				iutil.accessKeys[hashKey].firstChild.nodeValue));
			// クリックイベントハンドラ
			// アクセスキーが指定されているアンカーにイベントを転送する
			button.addEventListener('click', (function (key) {
				return (function (event) {
					return iutil.accessKeys[key].dispatchEvent(event);
				});
			})(hashKey), false);
			// ツールバーにボタンを追加
			toolbar.appendChild(button);
		}
	}

	// document.bodyに追加し、マージンを調整
	document.body.appendChild(toolbar);
	document.body.style.marginBottom =
		(iutil.parsePixels(iutil.getCurrentStyle(toolbar).height) + 10) + 'px';
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
	if (!contextNode) {
		return;
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
// {{{ sliding.onTouchStart()

/**
 * リンクスライド・タッチ/マウス押し下げ時のイベントハンドラ
 * ダイアログ表示タイマーをセットする
 *
 * @param {Event} event
 * @param {Element} target
 * @return void
 */
iutil.sliding.onTouchStart = function(event, target) {
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

	iutil.sliding.start = event.timeStamp;
	iutil.sliding.startX = x;
	iutil.sliding.startY = y;
	iutil.sliding.target = target;
};

// }}}
// {{{ sliding.onTouchMove()

/**
 * リンクスライド・ムーブ/ドラッグ時のイベントハンドラ
 * タッチ/カーソルが移動したならダイアログ表示タイマーをキャンセルする
 *
 * @param {Event} event
 * @return void
 */
iutil.sliding.onTouchMove = function(event) {
	var x, y;

	event = event || window.event;

	//iutil.stopEvent(event);

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

	iutil.sliding.endX = x;
	iutil.sliding.endY = y;
};

// }}}
// {{{ sliding.onTouchEnd()

/**
 * リンクスライド・リリース時のイベントハンドラ
 * ダイアログが表示されたならクリックをキャンセルする
 *
 * @param {Event} event
 * @return void
 */
iutil.sliding.onTouchEnd = function(event) {
	event = event || window.event;

	if (Math.abs(iutil.sliding.endX - iutil.sliding.startX) > 160 &&
		Math.abs(iutil.sliding.endY - iutil.sliding.startY) < 16 &&
		event.timeStamp < iutil.sliding.start + 1000)
	{
		iutil.sliding.showDialog(iutil.sliding.target, iutil.sliding.startX, iutil.sliding.startY);
		iutil.stopEvent(event);
		return false;
	} else {
		return true;
	}
};

// }}}
// {{{ sliding.showDialog()

/**
 * ダイアログを表示する
 *
 * @param {Element} anchor
 * @param {Number} x
 * @param {Number} y
 * @return void
 */
iutil.sliding.showDialog = function(anchor, x, y) {
	var sliding, dialog, div, text, button, m, p, left;

	sliding = iutil.sliding;
	sliding.timeoutId = -1;
	sliding.anchor = anchor;
	sliding.href = anchor.getAttribute('href');
	sliding.uri = anchor.href;

	sliding.hideDialog();

	m = iutil.internalLinkPattern.exec(anchor.getAttribute('href'));
	if (m === null) {
		return;
	}

	sliding.query = sliding.href.substring(m[0].length);
	p = sliding.query.indexOf('#');
	if (p !== -1) {
		sliding.query = sliding.query.substring(0, p);
	}

	// 特定のコールバック関数があるとき
	if (typeof sliding.callbacks[m[1]] === 'function') {
		sliding.callbacks[m[1]](anchor, event);
		return;
	}

	// デフォルトのダイアログを表示する
	if (typeof sliding.dialogs._default === 'undefined') {
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
		button.onclick = sliding.openUri;

		div.appendChild(document.createTextNode('\u3000'));

		button = div.appendChild(document.createElement('input'));
		button.setAttribute('type', 'button');
		button.value = 'タブで開く';
		button.onclick = sliding.openUriInTab;

		// 「閉じる」ボタン
		button = dialog.appendChild(document.createElement('img'));
		button.className = 'close-button';
		button.setAttribute('src', 'img/iphone/close.png');
		button.onclick = sliding.hideDialog;

		sliding.dialogs._default = document.body.appendChild(dialog);
	} else {
		dialog = sliding.dialogs._default;
	}
	sliding.setActiveDialog(dialog);

	text = dialog.firstChild.firstChild;
	text.nodeValue = iutil.getTextNodes(anchor, true).join('').replace(/\s+/g, ' ')
	               + ' (' + m[1] + '.php)';

	dialog.style.display = 'block';
	left = iutil.getWindowWidth() - iutil.parsePixels(iutil.getCurrentStyle(dialog).width) - 10;
	dialog.style.top = (y + 5) + 'px';
	dialog.style.left = Math.min(x, Math.max(0, left)) + 'px';
};

// }}}
// {{{ sliding.hideDialog()

/**
 * アクティブなダイアログを隠す
 *
 * @param void
 * @return void
 */
iutil.sliding.hideDialog = function() {
	if (iutil.sliding.dialog) {
		iutil.sliding.dialog.style.display = 'none';
		iutil.sliding.setActiveDialog(null);
	}
};

// }}}
// {{{ sliding.setActiveDialog()

/**
 * アクティブなダイアログを設定する
 *
 * @param {Element|null} element
 * @return void
 */
iutil.sliding.setActiveDialog = function(element) {
	iutil.sliding.dialog = element;
};

// }}}
// {{{ sliding.openUri()

/**
 * リンクを開く
 *
 * @param void
 * @return void
 */
iutil.sliding.openUri = function() {
	window.location.href = iutil.sliding.uri;
};

// }}}
// {{{ sliding.openUriInTab()

/**
 * 新しいタブでリンクを開く
 *
 * @param void
 * @return void
 */
iutil.sliding.openUriInTab = function() {
	window.open(iutil.sliding.uri, null);
};

// }}}
// {{{ sliding.bind()

/**
 * リンクにスライドイベントハンドラを登録する
 *
 * @param {Element} anchor
 * @return void
 */
if (iutil.iphone) {
	iutil.sliding.bind = function(anchor) {
		anchor.addEventListener('touchstart',   iutil.sliding.onTouchStart, false);
		anchor.addEventListener('touchmove',    iutil.sliding.onTouchMove, false);
		anchor.addEventListener('touchend',     iutil.sliding.onTouchEnd, false);
		anchor.addEventListener('touchcancel',  iutil.sliding.onTouchEnd, false);
	};
} else {
	iutil.sliding.bind = function(anchor) {
		anchor.addEventListener('mousedown',    iutil.sliding.onTouchStart, false);
		anchor.addEventListener('drag',         iutil.sliding.onTouchMove, false);
		anchor.addEventListener('mosueup',      iutil.sliding.onTouchEnd, false);
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

		// labelにイベントハンドラを登録する
		if (iutil.iphone) {
			iutil.setLabelAction(document.body);
			//iutil.setHashScrool(document.body);
		}

		// accesskeyをバインドする
		//if (!iutil.iphone && typeof window.iui === 'undefined') {
		//	iutil.setAccessKeys(document.body);
		//	iutil.showAccessKeys();
		//}

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
