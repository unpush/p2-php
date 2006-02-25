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
		if (document.all) { //IE用
			var body = (document.compatMode=='CSS1Compat') ? document.documentElement : document.body;
			x = body.scrollLeft+event.clientX; //現在のマウス位置のX座標
			y = body.scrollTop+event.clientY; //現在のマウス位置のY座標
			popOBJ.style.pixelLeft  = x + x_adjust; //ポップアップ位置
			popOBJ.style.pixelTop  = y + y_adjust;

			if ((popOBJ.offsetTop + popOBJ.offsetHeight) > (body.scrollTop + body.clientHeight)) {
				popOBJ.style.pixelTop = body.scrollTop + body.clientHeight - popOBJ.offsetHeight -20;
			}
			if (popOBJ.offsetTop < body.scrollTop) {
				popOBJ.style.pixelTop = body.scrollTop -2;
			}

		} else if (document.getElementById) { //DOM対応用（Mozilla）
			x = ev.pageX; //現在のマウス位置のX座標
			y = ev.pageY; //現在のマウス位置のY座標
			popOBJ.style.left = x + x_adjust + "px"; //ポップアップ位置
			popOBJ.style.top = y + y_adjust + "px";
			//alert(window.pageYOffset);
			//alert(popOBJ.offsetTop);

			if ((popOBJ.offsetTop + popOBJ.offsetHeight) > (window.pageYOffset + window.innerHeight)) {
				popOBJ.style.top = window.pageYOffset + window.innerHeight - popOBJ.offsetHeight -20 + "px";
			}
			if (popOBJ.offsetTop < window.pageYOffset) {
				popOBJ.style.top = window.pageYOffset -2 + "px";
			}

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
