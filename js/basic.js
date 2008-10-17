/* p2 - 基本JavaScriptファイル */

// サブウィンドウをポップアップする
// @return  true
function openSubWin(inUrl, inWidth, inHeight, boolS, boolR)
{
	var proparty3rd = "width=" + inWidth + ",height=" + inHeight + ",scrollbars=" + boolS + ",resizable=1";
	SubWin = window.open(inUrl,"",proparty3rd);
	if (boolR == 1) {
		SubWin.resizeTo(inWidth,inHeight);
	}
	SubWin.focus();
	return true;
}

// フレーム内のHTMLドキュメントのタイトルを、Window(top)タイトルにセットする
// @return  true|null|false
function setWinTitle()
{
	if (top == self) {
		return null;
	}
	try {
		top.document.title = self.document.title;
	} catch (e) {
		return false;
	}
	return true;
}

// DOMオブジェクトを取得する
function p2GetElementById(id)
{
	if (document.getElementById) {
		return (document.getElementById(id));
	} else if (document.all) {
		return (document.all[id]);
	} else if (document.layers) {
		return (document.layers[id]);
	} else {
		return false;
	}
}

// XMLHttpRequest オブジェクトを取得する
// @return  object
function getXmlHttp()
{
	var xmlHttpObj = null ;
	try {
		xmlHttpObj = new ActiveXObject("Msxml2.XMLHTTP") ; // Mozilla用
	} catch (e) {
		try {
			xmlHttpObj = new ActiveXObject("Microsoft.XMLHTTP") ; // IE用
		} catch (oc) {
			xmlHttpObj = null ;
		}
	}
	if (!xmlHttpObj && typeof XMLHttpRequest != "undefined") {
		xmlHttpObj = new XMLHttpRequest(); // 他
	}
	return xmlHttpObj;
}

// xmlHttpObj とurlを渡して、結果テキストを取得する
// @param nc string|null 指定するとこれをキーとしたキャッシュ回避のためのダミークエリーが追加される
function getResponseTextHttp(xmlHttpObj, url, nc)
{
	if (nc) {
		var now = new Date();
		url = url + '&' + nc + '=' + now.getTime(); // キャッシュ回避用
	}
	xmlHttpObj.open('GET', url, false);
	xmlHttpObj.send(null);
	
	if (xmlHttpObj.readyState == 4) {
		if (xmlHttpObj.status == 200) {
			return xmlHttpObj.responseText.replace(/^<\?xml .+?\?>\n?/, '');
		} else {
			// rt = '<em>HTTP Error:<br />' + req.status + ' ' + req.statusText + '</em>';
		}
	}
	return '';
}

// isSafari?
// @return  boolean
function isSafari() {
	var ua = navigator.userAgent;
	if (ua.indexOf("Safari") != -1 || ua.indexOf("AppleWebKit") != -1 || ua.indexOf("Konqueror") != -1) {
		return true;
	} else {
		return false;
	}
}

/**
 * @return  object
 */
function getDocumentBodyIE()
{
	return (document.compatMode=='CSS1Compat') ? document.documentElement : document.body;
}

// @return  void
function addLoadEvent(func) {
	var oldonload = window.onload;
	
	if (typeof window.onload != 'function') {
		window.onload = func;
	} else {
		window.onload = function() {
			oldonload();
			func();
		}
	}
}
