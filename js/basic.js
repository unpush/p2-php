/* p2 - 基本JavaScriptファイル */

////
// サブウィンドウをポップアップする
// @return  false
//
function OpenSubWin(inUrl, inWidth, inHeight, boolS, boolR)
{
	var proparty3rd = "width=" + inWidth + ",height=" + inHeight + ",scrollbars=" + boolS + ",resizable=1";
	SubWin = window.open(inUrl,"",proparty3rd);
	if (boolR == 1) {
		SubWin.resizeTo(inWidth,inHeight);
	}
	SubWin.focus();
	return false;
}

////
// フレーム内のHTMLドキュメントのタイトルを、Window(top)タイトルにセットする
// @return  true|null|false
//
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

////
// DOMオブジェクトを取得する
//
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

////
// XMLHttpRequest オブジェクトを取得する
// @return  object
//
function getXmlHttp()
{
	var objHTTP = null ;
	try {
		objHTTP = new ActiveXObject("Msxml2.XMLHTTP") ; // Mozilla用
	} catch (e) {
		try {
			objHTTP = new ActiveXObject("Microsoft.XMLHTTP") ; // IE用
		} catch (oc) {
			objHTTP = null ;
		}
	}
	if (!objHTTP && typeof XMLHttpRequest != "undefined") {
		objHTTP = new XMLHttpRequest(); // 他
	}
	return objHTTP
}

////
// objHTTP とurlを渡して、結果テキストを取得する
//
// @param nc string これをキーとしたキャッシュ回避のためのダミークエリーが追加される
//
function getResponseTextHttp(objHTTP, url, nc)
{
	if (nc) {
		var now = new Date();
		url = url + '&' + nc + '=' + now.getTime(); // キャッシュ回避用
	}
	objHTTP.open('GET', url, false);
	objHTTP.send(null);
	
	if (objHTTP.readyState == 4) {
		if (objHTTP.status == 200) {
			return objHTTP.responseText.replace(/^<\?xml .+?\?>\n?/, '');
		} else {
			// rt = '<em>HTTP Error:<br />' + req.status + ' ' + req.statusText + '</em>';
		}
	}
	
	return '';
}

////
// isSafari?
// @return  boolean
//
function isSafari() {
	var ua = navigator.userAgent;
	if (ua.indexOf("Safari") != -1 || ua.indexOf("AppleWebKit") != -1 || ua.indexOf("Konqueror") != -1) {
		return true;
	} else {
		return false;
	}
}
