/**
 * rep2expack - 設定管理ページ用JavaScript
 */

// {{{ globals

var _EDITPREF_ACTIVEDIVID = null;

// }}}
// {{{ showCacheMetaData()

/**
 * まとめ読みキャッシュのメタデータをポップアップ表示する
 */
function showCacheMetaData(divID, event) {
	var popup = document.getElementById(divID);
	if (!popup) {
		reutrn;
	}

	if (_EDITPREF_ACTIVEDIVID != divID) {
		if (_EDITPREF_ACTIVEDIVID) {
			// 遅延なしでポップアップを隠す
			doHideResPopUp(_EDITPREF_ACTIVEDIVID);
		}
		_EDITPREF_ACTIVEDIVID = divID;
		setCacheMetaDataPopUpHeight(popup);
	} else if (popup.style.visibility != 'visible') {
		setCacheMetaDataPopUpHeight(popup);
	}

	showResPopUp(divID, event);
}

// }}}
// {{{ hideCacheMetaData()

/**
 * まとめ読みキャッシュのメタデータを隠す
 */
function hideCacheMetaData(divID) {
	hideResPopUp(divID);
}

// }}}
// {{{ setCacheMetaDataPopUpHeight()

/**
 * ポップアップの高さを調整する
 */
function setCacheMetaDataPopUpHeight(popup)
{
	var popupHeight, windowHeight;

	windowHeight = getWindowHeight();
	popup.style.height = 'auto';
	popup.style.overflow = 'visible';
	popupHeight = getCurrentStyle(popup).height;
	if (popupHeight == 'auto') {
		popupHeight = popup.clientHeight;
	} else {
		popupHeight = parsePixels(popupHeight);
	}

	if (popupHeight > windowHeight) {
		if (windowHeight > 50) {
			popupHeight = windowHeight - 20;
		} else {
			popupHeight = windowHeight;
		}
		popup.style.height = popupHeight + 'px';
		popup.style.overflow = 'auto';
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
