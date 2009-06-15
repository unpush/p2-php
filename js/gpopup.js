/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

/* expack - Google検索の要約をポップアップするためのJavaScript */
/* respopup.jsのサブセット */

var zNum = 0;

//==============================================================
// gShowPopUp -- 要約ポップアップを表示する関数
//==============================================================

function gShowPopUp(divID, ev)
{
	zNum++;

	var popOBJ = document.getElementById(divID);
	var x_adjust = 10; //x軸位置調整
	var y_adjust = 10; //y軸位置調整

	if (popOBJ && popOBJ.style.visibility != "visible") {
		popOBJ.style.zIndex = zNum;
		var x = getPageX(ev);
		var y = getPageY(ev);
		var scrollY = getScrollY();
		var windowHeight = getWindowHeight();
		popOBJ.style.left = x + x_adjust + "px"; //ポップアップ位置
		popOBJ.style.top = y + y_adjust + "px";

		if ((popOBJ.offsetTop + popOBJ.offsetHeight) > (scrollY + windowHeight)) {
			popOBJ.style.top = (scrollY + windowHeight - popOBJ.offsetHeight - 20) + "px";
		}
		if (popOBJ.offsetTop < scrollY) {
			popOBJ.style.top = (scrollY - 2) + "px";
		}
		popOBJ.style.visibility = "visible"; //レスポップアップ表示
	}
}

//==============================================================
// gHidePopUp -- 要約ポップアップを非表示にする関数
//==============================================================

function gHidePopUp(divID)
{
	var popOBJ = document.getElementById(divID);
	if (popOBJ) {
		popOBJ.style.visibility = "hidden"; //レスポップアップ非表示
	}
}
