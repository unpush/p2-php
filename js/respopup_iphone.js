/**
 * rep2expack - iPhone用レスポップアップ
 *
 * iphone.jsの後に読み込む
 */

// {{{ globals

var _IRESPOPG = {
	'hash': {},
	'serial': 0,
	'callbacks': []
};

var ipoputil = {};

// }}}
// {{{ ipoputil.getZ()

/**
 * z-indexに設定する値を返す
 *
 * css/ic2_iphone.css で div#ic2-info の z-index が 999 で
 * 固定されているのでポップアップを繰り返すと不具合がある。
 * ポップアップオブジェクトの z-index を集中管理する必要あり。
 *
 * @param {Element} obj
 * @return {String}
 */
ipoputil.getZ = function(obj) {
	return (10 + _IRESPOPG.serial).toString();
};

// }}}
// {{{ getActivator()

/**
 * オブジェクトを最前面に移動する関数を返す
 *
 * @param {Element} obj
 * @return void
 */
ipoputil.getActivator = function(obj) {
	return (function(){
		_IRESPOPG.serial++;
		obj.style.zIndex = ipoputil.getZ();
	});
};

// }}}
// {{{ getDeactivator()

/**
 * DOMツリーからオブジェクトを取り除く関数を返す
 *
 * @param {Element} obj
 * @param {String} key
 * @return void
 */
ipoputil.getDeactivator = function(obj, key) {
	return (function(){
		delete _IRESPOPG.hash[key];
		obj.parentNode.removeChild(obj);
		delete obj;
	});
};

// }}}
// {{{ iResPopUp()

/**
 * iPhone用レスポップアップ
 *
 * @param {String} url
 * @param {Event} evt
 * @return {Boolean}
 * @todo use asynchronous request
 */
var iResPopUp = function(url, evt) {
	var yOffset = Math.max(10, iutil.getPageY(evt) - 20);

	if (_IRESPOPG.hash[url]) {
		_IRESPOPG.serial++;
		_IRESPOPG.hash[url].style.top = yOffset.toString() + 'px';
		_IRESPOPG.hash[url].style.zIndex = ipoputil.getZ();
		return false;
	}

	_IRESPOPG.serial++
	var popnum = _IRESPOPG.serial;
	var popid = '_respop' + popnum;
	var req = new XMLHttpRequest();
	req.open('GET', url + '&ajax=true&respop_id=' + popnum, false);
	req.send(null);

	if (req.readyState == 4) {
		if (req.status == 200) {
			var container = document.createElement('div');
			var closer = document.createElement('img');

			container.id = popid;
			container.className = 'respop';
			container.innerHTML = req.responseText;
			/*
			var rx = req.responseXML;
			while (rx.hasChildNodes()) {
				container.appendChild(document.importNode(rx.removeChild(rx.firstChild), true));
			}
			*/
			container.style.top = yOffset.toString() + 'px';
			container.style.zIndex = ipoputil.getZ();
			//container.onclick = ipoputil.getActivator(container);

			closer.className = 'close-button';
			closer.setAttribute('src', 'img/iphone/close.png');
			closer.onclick = ipoputil.getDeactivator(container, url);

			container.appendChild(closer);
			document.body.appendChild(container);

			//iutil.modifyInternalLink(container);
			iutil.modifyExternalLink(container);

			_IRESPOPG.hash[url] = container;

			var lastres = document.evaluate('./div[@class="res" and position() = last()]',
			                                container,
			                                null,
			                                XPathResult.ANY_UNORDERED_NODE_TYPE,
			                                null
			                                ).singleNodeValue;

			if (lastres) {
				var back = document.createElement('div');
				back.className = 'respop-back';
				var anchor = document.createElement('a');
				anchor.setAttribute('href', '#' + popid);
				anchor.onclick = (function(){
					scrollTo(0, yOffset - 10);
					return false;
				});
				anchor.appendChild(document.createTextNode('▲'));
				back.appendChild(anchor);
				lastres.appendChild(back);
			}

			for (var i = 0; i < _IRESPOPG.callbacks.length; i++) {
				_IRESPOPG.callbacks[i](container);
			}

			return false;
		}
	}

	return true;
};

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
