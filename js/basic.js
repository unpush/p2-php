/* p2 - 基本JavaScriptファイル */

//OpenSubWin -- サブウィンドウをポップアップする
function OpenSubWin(inUrl, inWidth, inHeight, boolS, boolR){
	var proparty3rd = "width=" + inWidth + ",height=" + inHeight + ",scrollbars=" + boolS + ",resizable=1";
	SubWin = window.open(inUrl,"",proparty3rd);
	if (boolR == 1){
		SubWin.resizeTo(inWidth,inHeight);
	}
	SubWin.focus();
	return false;
}

function setWinTitle(){
	if (top != self) {top.document.title=self.document.title;}
}
