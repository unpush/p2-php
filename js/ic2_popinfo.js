/*
 * ImageCache2 - 画像情報ポップアップ
 */

// {{{ GLOBALS

var ic2info = {};

// }}}
// {{{ _ic2_popinfo_js_onload()

/*
 * ImageCache2 画像情報ポップアップを初期化する
 *
 * @return void
 */
var _ic2_popinfo_js_onload = function() {
	// {{{ initiaize

	ic2info._targetId = null;

	var _infoContainer    = document.getElementById('ic2-info');
	var _messageContainer = document.getElementById('ic2-info-message');
	//var _previewContainer = document.getElementById('ic2-info-preview');
	var _ratingContainer  = document.getElementById('ic2-info-rating');
	var _ranks = _ratingContainer.getElementsByTagName('img');

	// }}}
	// {{{ utilities

	var _number_format = function(n) {
		var r = '';
		var s = n.toString();
		var l = s.length;

		for (; l > 3; l -= 3) {
			r += ',' + s.substr(l - 3, 3) + r;
		}
		return s.substr(0, l) + r;
	};

	var _uniquery = function() {
		return '&_=' + (new Date()).getTime().toString();
	};

	// }}}
	// {{{ show()

	/*
	 * 画像情報を表示する
	 */
	ic2info.show = function(key, evt) {
		var info = ic2_getinfo((typeof key === 'number') ? 'id' : 'url', key);

		while (_messageContainer.childNodes.length) {
			_messageContainer.removeChild(_messageContainer.firstChild);
		}

		if (info) {
			ic2info.setRank(info.rank);

			_messageContainer.appendChild(document.createTextNode(
				info.width + 'x' + info.height + ' (' + _number_format(info.size) + ' bytes)'
			));
			_ratingContainer.style.display = 'block';
			ic2info._targetId = info.id.toString();
		} else {
			_messageContainer.appendChild(document.createTextNode('画像情報を取得できませんでした。'));
			_ratingContainer.style.display = 'none';
			ic2info._targetId = null;
		}

		_infoContainer.style.display = 'block';
		_infoContainer.style.left = (getPageX(evt) - 8) + 'px';
		_infoContainer.style.top = (getPageY(evt) - 5) + 'px';
	};

	// }}}
	// {{{ hide()

	/*
	 * 画像情報を隠す
	 */
	ic2info.hide = function() {
		_infoContainer.style.display = 'none';
		ic2info._targetId = null;
	};

	// }}}
	// {{{ setRank(rank)

	ic2info.setRank = function(rank) {
		var pos = rank + 1;
		_ranks[0].setAttribute('src', 'img/sn' + ((rank == -1) ? '1' : '0') + 'a.png');
		for (var i = 2; i < _ranks.length; i++) {
			_ranks[i].setAttribute('src', 'img/s' + ((i > pos) ? '0' : '1') + 'a.png');
		}
	};

	// }}}
	// {{{ updateRank

	ic2info.updateRank = function(rank) {
		if (!ic2info._targetId) {
			window.alert('Wrong method call');
			return false;
		}

		var req = new XMLHttpRequest();
		req.open('GET',
				 'ic2_setrank.php?id=' + encodeURIComponent(ic2info._targetId)
					+ '&rank=' + encodeURIComponent(rank.toString())
					+ _uniquery(),
				 false
				 );
		req.send(null);

		if (req.readyState == 4) {
			if (req.status == 200) {
				if (req.responseText == '1') {
					ic2info.setRank(rank);
				} else {
					window.alert('Internal error');
				}
			} else {
				window.alert('HTTP error ' + req.statusText);
			}
		}

		return false;
	};

	// }}}

	document.getElementById('ic2-info-closer').onclick = ic2info.hide;

	for (var i = 0; i < _ranks.length; i++) {
		_ranks[i].onclick = (function(n){
			return function(){ ic2info.updateRank(n); };
		})(i - 1);
	};
};

// }}}

(function(){
	if (typeof window.p2BindReady == 'undefined') {
		window.setTimeout(arguments.callee, 100);
	} else {
		window.p2BindReady(_ic2_popinfo_js_onload, 'js/defer/ic2_popinfo.js');
	}
})();

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
