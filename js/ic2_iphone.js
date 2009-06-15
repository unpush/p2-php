/*
 * ImageCache2::iPhone
 */

// {{{ GLOBALS

var ic2info = {};

// }}}
// {{{ DOMContentLoaded

window.addEventListener('DOMContentLoaded', function(event) {
	this.removeEventListener('DOMContentLoaded', arguments.callee, false);

	// {{{ initiaize

	ic2info._targetId = null;

	var _infoContainer = document.getElementById('ic2-info');
	if (!_infoContainer) {
		return;
	}
	var _messageContainer = document.getElementById('ic2-info-message');
	var _previewContainer = document.getElementById('ic2-info-preview');
	var _ratingContainer  = document.getElementById('ic2-info-rating');
	var _ranks = _ratingContainer.getElementsByTagName('img');

	// }}}
	// {{{ utilities

	var _number_format = function(n) {
		var i, l, m, r, s;
		s = n.toString();
		l = s.length;
		m = l % 3;
		r = (m) ? s.substr(0, m) : '';
		for (i = m; i < l; i += 3) {
			r += ',' + s.substr(i, 3);
		}
		return (m) ? r : r.substring(1);
	};

	var _uniquery = function() {
		return '&_=' + (new Date()).getTime().toString();
	};

	// }}
	// {{{ show()

	/*
	 * ‰æ‘œî•ñ‚ğ•\¦‚·‚é
	 */
	ic2info.show = function(key, evt) {
		var info = ic2info.get(key);

		while (_messageContainer.childNodes.length) {
			_messageContainer.removeChild(_messageContainer.firstChild);
		}

		if (info) {
			while (_previewContainer.childNodes.length) {
				_previewContainer.removeChild(_previewContainer.firstChild);
			}

			ic2info.setRank(info.rank);

			if (info.rank >= 0) {
				var edit, thumb;

				edit = document.createElement('a');
				edit.setAttribute('href', 'ic2.php?r=0&t=2&id=' + info.id + _uniquery());
				edit.setAttribute('target', '_blank');

				thumb = document.createElement('img');
				thumb.setAttribute('src', info.thumb || 'ic2.php?r=2&t=1&id=' + info.id + _uniquery());

				_previewContainer.appendChild(edit).appendChild(thumb);
			}

			_messageContainer.appendChild(document.createTextNode(info.uri));
			_messageContainer.appendChild(document.createElement('br'));
			_messageContainer.appendChild(document.createTextNode(
				info.width + 'x' + info.height + ' (' + _number_format(info.size) + ' bytes)'
			));
			_ratingContainer.style.display = 'block';
			ic2info._targetId = info.id.toString();
		} else {
			_messageContainer.appendChild(document.createTextNode('‰æ‘œî•ñ‚ğæ“¾‚Å‚«‚Ü‚¹‚ñ‚Å‚µ‚½B'));
			_ratingContainer.style.display = 'none';
			ic2info._targetId = null;
		}

		_infoContainer.style.display = 'block';
		_infoContainer.style.top = Math.max(10, iutil.getPageY(evt) - 80) + 'px';
	};

	// }}}
	// {{{ hide()

	/*
	 * ‰æ‘œî•ñ‚ğ‰B‚·
	 */
	ic2info.hide = function() {
		_infoContainer.style.display = 'none';
		ic2info._targetId = null;
	};

	// }}}
	// {{{ get()

	/*
	 * ‰æ‘œî•ñ‚ğæ“¾‚·‚é
	 *
	 * @param {Number|String} key
	 * @return {Object|null}
	 */
	ic2info.get = function(key) {
		var url, req, res, err;

		url = 'ic2_getinfo.php?';
		if (typeof key == 'number') {
			url += 'id=' + key.toString();
		} else {
			url += 'url=' + encodeURIComponent(key);
		}
		url += '&t=1' + _uniquery();

		try {
			req = new XMLHttpRequest();
			req.open('GET', url, false);
			req.send(null);

			res = null;

			if (req.readyState == 4) {
				if (req.status == 200) {
					res = JSON.parse(req.responseText);
				}
			}
		} catch (err) {
		}

		return (typeof res === 'object' && typeof res.id === 'number') ? res : null;
	};

	// }}}
	// {{{ setRank()

	ic2info.setRank = function(rank) {
		var pos = rank + 1;
		_ranks[0].setAttribute('src', 'img/iphone/sn' + ((rank == -1) ? '1' : '0') + '.png');
		for (var i = 2; i < _ranks.length; i++) {
			_ranks[i].setAttribute('src', 'img/iphone/s' + ((i > pos) ? '0' : '1') + '.png');
		}
	};

	// }}}
	// {{{ updateRank()

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
