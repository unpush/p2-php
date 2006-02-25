/* p2 - 引用レス番をポップアップするためのJavaScript */

/*
document.open;
document.writeln('<style type="text/css" media="all">');
document.writeln('<!--');
document.writeln('.respopup{visibility: hidden;}');
document.writeln('-->');
document.writeln('</style>');
document.close;
*/

delayShowSec = 0.1 * 1000;	// レスポップアップを表示する遅延時間。
delaySec = 0.3 * 1000;	// レスポップアップを非表示にする遅延時間。
zNum = 0;

//==============================================================
// gPOPS -- ResPopUp オブジェクトを格納する配列。
// 配列 gPOPS の要素数が、現在生きている ResPopUp オブジェクトの数となる。
//==============================================================
gPOPS = new Array();

gResPopCtl = new ResPopCtl();

gShowTimerIds = new Object();

/**
 * レスポップアップを表示タイマーする
 *
 * 引用レス番に onMouseover で呼び出される
 */
function showResPopUp(divID, ev) {
	if (divID.indexOf("-") != -1) { return; } // 連番 (>>1-100) は非対応なので抜ける

	var aResPopUp = gResPopCtl.getResPopUp(divID);
	if (aResPopUp) {
		if (aResPopUp.hideTimerID) { clearTimeout(aResPopUp.hideTimerID); } // 非表示タイマーを解除
	} else {
		// doShowResPopUp(divID, ev);

		if (document.all) { // IE用
			var body = (document.compatMode=='CSS1Compat') ? document.documentElement : document.body;
			x = body.scrollLeft + event.clientX; // 現在のマウス位置のX座標
			y = body.scrollTop + event.clientY; // 現在のマウス位置のY座標
		} else if (document.getElementById) { // DOM対応用（Mozilla）
			x = ev.pageX; // 現在のマウス位置のX座標
			y = ev.pageY; // 現在のマウス位置のY座標
		} else {
			return;
		}

		aShowTimer = new Object();
		aShowTimer.timerID = setTimeout("doShowResPopUp('" + divID + "')", delayShowSec); // 一定時間したら表示する

		aShowTimer.x = x;
		aShowTimer.y = y;

		gShowTimerIds[divID] = aShowTimer;
		//alert(gShowTimerIds[divID].timerID);
	}

}

/**
 * レスポップアップを表示する
 */
function doShowResPopUp(divID) {

	x = gShowTimerIds[divID].x;
	y = gShowTimerIds[divID].y;

	var aResPopUp = gResPopCtl.getResPopUp(divID);
	if (aResPopUp) {
		if (aResPopUp.hideTimerID) { clearTimeout(aResPopUp.hideTimerID); } // 非表示タイマーを解除

		/*
		// 再表示時の zIndex 処理 ------------------------
		// しかしなぜか期待通りの動作をしてくれない。
		// IEとMozillaで挙動も違う。よって非アクティブ。
		aResPopUp.zNum = zNum;
		aResPopUp.popOBJ.style.zIndex = aResPopUp.zNum;
		//----------------------------------------
		*/

	} else {
		zNum++;
		aResPopUp = gResPopCtl.addResPopUp(divID); // 新しいポップアップを追加
	}

	aResPopUp.showResPopUp(x, y);
}

/**
 * レスポップアップを非表示タイマーする
 *
 * 引用レス番から onMouseout で呼び出される
 */
function hideResPopUp(divID) {
	if (divID.indexOf("-") != -1) { return; } // 連番 (>>1-100) は非対応なので抜ける

	if (gShowTimerIds[divID].timerID) { clearTimeout(gShowTimerIds[divID].timerID); } // 表示タイマーを解除

	var aResPopUp = gResPopCtl.getResPopUp(divID);
	if (aResPopUp) {
		aResPopUp.hideResPopUp();
	}
}

/**
 * レスポップアップを非表示にする
 */
function doHideResPopUp(divID) {
	var aResPopUp = gResPopCtl.getResPopUp(divID);
	if (aResPopUp) {
		aResPopUp.doHideResPopUp();
	}
}


/**
 * オブジェクトデータをコントロールするクラス
 */
function ResPopCtl() {

	/**
		* 配列 gPOPS に新規 ResPopUp オブジェクト を追加する
		*/
	ResPopCtl.prototype.addResPopUp = function (divID) {
		var aResPopUp = new ResPopUp(divID);
		// gPOPS.push(aResPopUp); Array.push はIE5.5未満未対応なので代替処理
		return gPOPS[gPOPS.length] = aResPopUp;
	}

	/**
		* 配列 gPOPS から 指定の ResPopUp オブジェクト を削除する
		*/
	ResPopCtl.prototype.rmResPopUp = function (divID) {
		for (i = 0; i < gPOPS.length; i++) {
			if (gPOPS[i].divID == divID) {

				gPOPS = arraySplice(gPOPS, i);

				return true;
			}
		}
		return false;
	}

	/**
		* 配列 gPOPS で指定 divID の ResPopUp オブジェクトを返す
		*/
	ResPopCtl.prototype.getResPopUp = function (divID) {
		for (i = 0; i < gPOPS.length; i++) {
			if (gPOPS[i].divID == divID) {
				return gPOPS[i];
			}
		}
		return false;
	}

	return this;
}

/**
 * arraySplice
 *
 * anArray.splice(i, 1); Array.splice はIE5.5未満未対応なので代替処理
 * @return array
 */
function arraySplice(anArray, i) {
	var newArray = new Array();
	for (j = 0; j < anArray.length; j++) {
		if (j != i) {
			newArray[newArray.length] = anArray[j];
		}
	}
	return newArray;
}

/**
 * レスポップアップクラス
 */
function ResPopUp(divID) {

	this.divID = divID;
	this.zNum = zNum;
	this.hideTimerID = 0;

	if (document.all) { // IE用
		this.popOBJ = document.all[this.divID];
	} else if (document.getElementById) { // DOM対応用（Mozilla）
		this.popOBJ = document.getElementById(this.divID);
	}

	/**
		* レスポップアップを表示する
		*/
	ResPopUp.prototype.showResPopUp = function (x, y) {
		var x_adjust = 10;	// x軸位置調整
		var y_adjust = -68;	// y軸位置調整
		if (this.divID.indexOf('spm_') == 0) {
			y_adjust=-10;
		}
		if (this.popOBJ.style.visibility != "visible") {
			this.popOBJ.style.zIndex = this.zNum;
			if (document.all) { // IE用
				var body = (document.compatMode=='CSS1Compat') ? document.documentElement : document.body;
				//x = body.scrollLeft + event.clientX; // 現在のマウス位置のX座標
				//y = body.scrollTop + event.clientY; // 現在のマウス位置のY座標
				this.popOBJ.style.pixelLeft	= x + x_adjust; //ポップアップ位置
				this.popOBJ.style.pixelTop	= y + y_adjust;

				if( (this.popOBJ.offsetTop + this.popOBJ.offsetHeight) > (body.scrollTop + body.clientHeight) ){
					this.popOBJ.style.pixelTop = body.scrollTop + body.clientHeight - this.popOBJ.offsetHeight -20;
				}
				if (this.popOBJ.offsetTop < body.scrollTop) {
					this.popOBJ.style.pixelTop = body.scrollTop -2;
				}

			} else if (document.getElementById) { // DOM対応用（Mozilla）
				//x = ev.pageX; // 現在のマウス位置のX座標
				//y = ev.pageY; // 現在のマウス位置のY座標
				this.popOBJ.style.left = x + x_adjust + "px"; //ポップアップ位置
				this.popOBJ.style.top = y + y_adjust + "px";
				//alert(window.pageYOffset);
				//alert(this.popOBJ.offsetTop);

				if ((this.popOBJ.offsetTop + this.popOBJ.offsetHeight) > (window.pageYOffset + window.innerHeight)) {
					this.popOBJ.style.top = window.pageYOffset + window.innerHeight - this.popOBJ.offsetHeight -20 + "px";
				}
				if (this.popOBJ.offsetTop < window.pageYOffset) {
					this.popOBJ.style.top = window.pageYOffset -2 + "px";
				}

			}
			this.popOBJ.style.visibility = "visible"; // レスポップアップ表示
		}
	}

	/**
		* レスポップアップを非表示タイマーする
		*/
	ResPopUp.prototype.hideResPopUp = function () {
		this.hideTimerID = setTimeout("doHideResPopUp('" + this.divID + "')", delaySec); // 一定時間表示したら消す
	}

	/**
		* レスポップアップを非表示にする
		*/
	ResPopUp.prototype.doHideResPopUp = function () {

		for (i=0; i < gPOPS.length; i++) {

			if (this.zNum < gPOPS[i].zNum) {
				//clearTimeout(this.hideTimerID); // タイマーを解除
				this.hideTimerID = setTimeout("hideResPopUp('" + this.divID + "')", delaySec); // 一定時間表示したら消す
				return;
			}
		}

		this.popOBJ.style.visibility = "hidden"; // レスポップアップ非表示
		// clearTimeout(this.hideTimerID); // タイマーを解除
		gResPopCtl.rmResPopUp(this.divID);
	}

	return this;
}
