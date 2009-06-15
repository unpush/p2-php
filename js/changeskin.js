/**
 * スキンを動的に切り替える
 */

// {{{ changeFrameSkin()

/**
 * 指定フレームのスキンを切り替える
 * @param {Window|Frame} frame
 * @param {String} skin
 * @param {String} uniq
 * @param {Boolean} nocache
 * @return void
 */
function changeFrameSkin(frame, skin, uniq, nocache) {
	uniq = (uniq) ? ((nocache) ? uniq + (new Date()).getTime() : uniq) : (new Date()).getTime();

	var repracement = encodeURIComponent(skin) + '&_=' + encodeURIComponent('' + uniq);
	var head = frame.document.getElementsByTagName('head')[0];
	var styles = head.getElementsByTagName('link');
	var i = 0, l = styles.length, r = /^css\.php\?css=\w+&skin=/;
	var m, s, attributeExists;

	if (typeof head.hasAttribute == 'function') {
		attributeExists = function(name, element) {
			return element.hasAttribute(name);
		};
	} else {
		attributeExists = function(name, element) {
			return element.getAttribute(name) !== null;
		};
	}

	for (i = 0; i < l; i++) {
		s = styles[i];
		if (attributeExists('rel', s) && s.getAttribute('rel') == 'stylesheet' &&
			attributeExists('href', s) && (m = r.exec(s.getAttribute('href')))) {
			s.setAttribute('href', m[0] + repracement);
		}
	}
}

// }}}
// {{{ changeSkin()

/**
 * カレントウインドウのスキンを切り替える
 * @param {String} skin
 * @param {String} uniq
 * @param {Boolean} nocache
 * @return void
 */
function changeSkin(skin, uniq, nocache) {
	changeFrameSkin(window.self, skin, uniq, nocache);
}

// }}}
// {{{ changeSkinAll()

/**
 * 全ウインドウのスキンを一括で切り替える
 * @param {String} skin
 * @param {String} uniq
 * @return void
 */
function changeSkinAll(skin, uniq) {
	changeSkin(skin, uniq, true);
	if (window.top.menu && window.top.menu != window.self) {
		changeFrameSkin(window.top.menu, skin, uniq);
	}
	if (window.top.read && window.top.read != window.self) {
		changeFrameSkin(window.top.read, skin, uniq);
	}
	if (window.top.subject && window.top.subject != window.self) {
		changeFrameSkin(window.top.subject, skin, uniq);
	}
}

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
