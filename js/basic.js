/* p2 - 基本JavaScriptファイル */

// サブウィンドウをポップアップする
function OpenSubWin(inUrl, inWidth, inHeight, boolS, boolR){
	var proparty3rd = "width=" + inWidth + ",height=" + inHeight + ",scrollbars=" + boolS + ",resizable=1";
	SubWin = window.open(inUrl,"",proparty3rd);
	if (boolR == 1){
		SubWin.resizeTo(inWidth,inHeight);
	}
	SubWin.focus();
	return false;
}

// HTMLドキュメントのタイトルをセットする関数
function setWinTitle()
{
	if (top != self) { top.document.title = self.document.title; }
}

// p2GetElementById -- DOMオブジェクトを取得
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
