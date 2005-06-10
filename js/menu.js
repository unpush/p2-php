/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

/* p2 - メニューを操作するためのJavaScript */

/**
 * お気に板にする
 */
function setFavIta(php, itaj, host, bbs, itaj_en, noconfirm)
{
	if (noconfirm || window.confirm('"' + itaj + '" をお気に板に登録しますか？')) {
		var setURL = php + '?host=' + host + '&bbs=' +bbs + '&itaj_en=' + itaj_en+ '&setfavita=1';
		this.window.location.href = setURL;
	}
	return false;
}

/**
 * お気に板から外す
 */
function unSetFavIta(php, itaj, host, bbs, noconfirm)
{
	if (noconfirm || window.confirm('"' + itaj + '" をお気に板から外しますか？')) {
		// プルダウンメニューでセットを切り替えたときの特別な処理
		if (php.indexOf('menu_async.php') != -1) {
			if (this.window.location.href.indexOf('menu_side.php') != -1) {
				php = php.replace(/menu_async\.php/, 'menu_side.php');
			} else {
				php = php.replace(/menu_async\.php/, 'menu.php');
			}
		}
		var unsetURL = php + '?host=' + host + '&bbs=' +bbs + '&setfavita=0';
		this.window.location.href = unsetURL;
	}
	return false;
}

/**
 * お気に板・RSSを切り替える
 */
function replaceMenuItem(itemId, qKey, qValue)
{
	var uri = 'menu_async.php?' + qKey + '=' + qValue;

	var req = getXmlHttp();
	if (!req) {
		alert('XMLHttp not available.');
		return;
	}

	var receiver = document.getElementById(itemId);
	if (!receiver) {
		alert('replaceMenuItem() Error: A target element not exists.');
		return;
	}
	receiver.innerHTML = 'Now Loading...';

	req.open('get', uri, false);
	req.send(null);

	if (req.readyState == 4) {
		if (req.status == 200) {
			receiver.innerHTML = req.responseText.replace(/^<\?xml .+?\?>\n?/, '');
		} else {
			receiver.innerHTML = '<em>HTTP Error:<br />' + req.status + ' ' + req.statusText + '</em>';
		}
	}
}

/**
 * お気にスレを開く
 */
function openFavList(subject_php, set_num, tgt)
{
	var url = subject_php + '?spmode=fav&norefresh=1&m_favlist_set=' + set_num;
	if (tgt) {
		tgt.location.href = url;
	} else {
		window.open(url, '', '');
	}
}

/**
 * Google検索する
 */
function doGoogleSearch(word, tgt)
{
	if (!word) {
		return;
	}
	var url = 'gsearch.php?q=' + encodeURIComponent(word);
	if (tgt) {
		tgt.location.href = url;
	} else {
		window.open(url, '', '');
	}
}
