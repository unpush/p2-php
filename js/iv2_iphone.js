/*
 * ImageCache2::Viewer - DOMÇëÄçÏÇµÇƒiPhoneÇ…ç≈ìKâªÇ∑ÇÈ
 */

// {{{ DOMContentLoaded

document.addEventListener('DOMContentLoaded', function(event) {
	var table, css, cells, adjust, width1, width2, rules, index1, index2, callback;

	document.removeEventListener(event.type, arguments.callee, false);

	table = document.getElementById('iv2-images');
	if (!table) {
		return;
	}

	css = document.styleSheets[document.styleSheets.length - 1];
	cells = table.getElementsByTagName('td');
	width1 = document.body.clientWidth;
	width2 = 0;
	rules = css.cssRules;
	index1 = rules.length;
	index2 = rules.length + 1;

	css.insertRule('table#iv2-images { width: ' + width1 + 'px; }', index1);
	if (cells && cells.length) {
		width2 = cells[0].clientWidth + 20;
		css.insertRule('div.iv2-image-title { width: ' + (width1 - width2) + 'px; }', index2);
	}

	callback = function() {
		var width = document.body.clientWidth;

		rules[index1].style.width = width + 'px';
		if (width2) {
			rules[index2].style.width = (width - width2) + 'px';
		}
	};

	if (typeof window.orientation === 'number') {
		window.addEventListener('orientationchange', callback, false);
	} else {
		window.addEventListener('resize', callback, false);
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
