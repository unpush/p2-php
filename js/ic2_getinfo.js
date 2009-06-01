/**
 * ImageCache2::getInfo
 */

// {{{ ic2_getinfo()

/**
 * 画像情報を取得する
 *
 * @param {String} type     画像を特定するキーの型
 * @param {String} value    画像を特定するキーの値
 * @param {Number} thumb    サムネイルの種類(1,2,3)
 * @return {Object|null}
 */
function ic2_getinfo(type, value, thumb)
{
	var url, req, res, err;

	req = getXmlHttp();
	if (!req) {
		return null;
	}

	url = 'ic2_getinfo.php?';
	if (type == 'id') {
		url += 'id=' + parseInt(value, 10);
	} else {
		url += encodeURIComponent(type) + '=' + encodeURIComponent(value);
	}
	if (typeof thumb === 'number') {
		url += '&t=' + thumb;
	}

	try {
		res = JSON.parse(getResponseTextHttp(req, url, 'nc'));
	} catch (err) {
		return null;
	}

	return (typeof res === 'object' && typeof res.id === 'number') ? res : null;
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
