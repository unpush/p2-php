/**
 * rep2expack - レスの表示範囲を指定して元スレを開くポップアップを表示/非表示する
 */

var _MOTOLSPOPUP_PARAMETERS = [
	{ 'label': '>>1',    'parameter': '1' },
	{ 'label': '1-10',   'parameter': '1-10' },
	{ 'label': '1-100',  'parameter': '1-100' },
	{ 'label': '最新50', 'parameter': 'l50' },
	{ 'label': '全部',   'parameter': '' }
];

function showMotoLsPopUp(event, origin, title)
{
	var div, baseUrl, url, anchor, target, i, l;

	l = _MOTOLSPOPUP_PARAMETERS.length;

	div = document.getElementById('motols');
	if (!div) {
		div = document.createElement('div');
		div.id = 'motols';
		div.className = 'popup_element';
		div.onmouseover = showMotoLsPopUpDo;
		div.onmouseout = hideMotoLsPopUp;

		for (i = 0; i < l; i++) {
			anchor = document.createElement('a');
			anchor.appendChild(document.createTextNode(_MOTOLSPOPUP_PARAMETERS[i].label));
			div.appendChild(anchor);
		}

		document.body.insertBefore(div, document.body.firstChild);
	}

	if (title) {
		title += '\n';
	} else {
		title = '';
	}

	if (typeof origin.hasAttribute == 'function') {
		if (origin.hasAttribute('target')) {
			target = origin.getAttribute('target');
		} else {
			target = null;
		}
	} else {
		target = origin.getAttribute('target');
	}
	if (target && !target.length) {
		target = null;
	}

	baseUrl = origin.getAttribute('href');
	for (i = 0; i < l; i++) {
		anchor = div.childNodes[i];
		url = baseUrl + _MOTOLSPOPUP_PARAMETERS[i].parameter;
		anchor.setAttribute('href', url);
		anchor.setAttribute('title', title + url);
		if (target) {
			anchor.setAttribute('target', target);
		}
	}

	showMotoLsPopUpDo(event);
}

function showMotoLsPopUpDo(event)
{
	showResPopUp('motols', event || window.event);
}

function hideMotoLsPopUp()
{
	hideResPopUp('motols');
}

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
