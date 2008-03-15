/* p2 - 基本JavaScriptファイル */

// サブウィンドウをポップアップする
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

// HTMLドキュメントのタイトルをセットする
function setWinTitle()
{
	if (top != self) {
		try {
			top.document.title = self.document.title;
		} catch (e) {
			// 何もしない
		}
	}
}

// DOMオブジェクトを取得
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

// XMLHttpRequest オブジェクトを取得
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

/**
 * objHTTP とurlを渡して、結果テキストを取得する
 *
 * @param nc string これをキーとしたキャッシュ回避のためのクエリーが追加される
 */
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
			return objHTTP.responseText;
		} else {
			// rt = '<em>HTTP Error:<br />' + req.status + ' ' + req.statusText + '</em>';
		}
	}
	
	return '';
}

// prototype.js 1.4.0 : string.js : escapeHTML をワンライナーで
// IE6 標準モード対策で改行コードを CR+LF に統一
/*  Prototype JavaScript framework, version 1.4.0
 *  (c) 2005 Sam Stephenson <sam@conio.net>
 *
 *  Prototype is freely distributable under the terms of an MIT-style license.
 *  For details, see the Prototype web site: http://prototype.conio.net/
 */
function escapeHTML(cont)
{
	return document.createElement('div').appendChild(document.createTextNode(cont)).parentNode.innerHTML;
}
