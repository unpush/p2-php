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

//=============================
// 設定
//=============================
cDelayShowSec = 0.1 * 1000;	// レスポップアップを表示する遅延時間。
cDelayHideSec = 0.2 * 1000;	// レスポップアップを非表示にする遅延時間
cSpmDelayHideSec = 0.1 * 1000;	// レスポップアップを非表示にする遅延時間（spm）

//=============================
// 内部変数
//=============================
// gPOPS -- ResPopUp オブジェクトを格納する配列。
// 配列 gPOPS の要素数が、現在生きている ResPopUp オブジェクトの数となる。
gPOPS = new Array(); 

gShowTimerIds = new Object();
gHideTimerIds = new Object();

gOnPopSpaceId = null;

zNum = 0;

//=============================
// スタティックメソッド定義
//=============================

// ResPopUp オブジェクトを取り扱う
var ResPopUpManager = {

	// 配列 gPOPS に新規 ResPopUp オブジェクト を追加する
	// @return  ResPopUp
	addResPopUp: function (popId) {
		var aResPopUp = new ResPopUp(popId);
		// gPOPS.push(aResPopUp); Array.push はIE5.5未満未対応なので代替処理
		return gPOPS[gPOPS.length] = aResPopUp;
	},

	// 配列 gPOPS から 指定の ResPopUp オブジェクト を削除する
	// @return  boolean
	rmResPopUp: function (popId) {
		for (i = 0; i < gPOPS.length; i++) {
	    	if (gPOPS[i].popId == popId) {
				gPOPS = arraySplice(gPOPS, i);
				return true;
			}
		}
		return false;
	},

	// 配列 gPOPS で指定 popId の ResPopUp オブジェクトを返す
	// @return  ResPopUp|false
	getResPopUp: function (popId) {
		for (i = 0; i < gPOPS.length; i++) {
	    	if (gPOPS[i].popId == popId) {
				return gPOPS[i];
			}
		}
		return false;
	}
}

//=============================
// クラス定義
//=============================

// クラス レスポップアップ（名前を ResPopup にしたい気持ち[Uu]）
function ResPopUp(popId)
{
    this.popId = popId;
	this.zNum = zNum;
	this.hideTimerID = 0;
	
	// IE用
	if (document.all) {
		this.popOBJ = document.all[this.popId];
	// DOM対応用（Mozilla）
	} else if (document.getElementById) {
		this.popOBJ = document.getElementById(this.popId);
	}
}

ResPopUp.prototype = {
	
	// レスポップアップの位置をセットする
	// @return  void
	setPosResPopUp: function (x, y)
	{
		var x_adjust = 10;	// x軸位置調整
		var y_adjust = -68;	// y軸位置調整
	
		if (this.isModeSpm()) {
			x_adjust = 0;
			y_adjust = -10;
		}
	
		if (document.all) { // IE用
			var body = (document.compatMode=='CSS1Compat') ? document.documentElement : document.body;
			//x = body.scrollLeft + event.clientX; // 現在のマウス位置のX座標
			//y = body.scrollTop + event.clientY; // 現在のマウス位置のY座標
			this.popOBJ.style.pixelLeft  = x + x_adjust; //ポップアップ位置
			this.popOBJ.style.pixelTop  = y + y_adjust;
		
			if (this.popOBJ.offsetTop + this.popOBJ.offsetHeight > body.scrollTop + body.clientHeight) {
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
	},

	// レスポップアップを表示する
	// @return  void
	showResPopUp: function (x, y)
	{
		if (this.popOBJ.style.visibility == "visible") {
			return;
		}
	
		this.popOBJ.style.zIndex = this.zNum;
		this.setPosResPopUp(x, y);
	
		// スマートポップアップメニューのとき
		var mode = this.popId.charAt(0);
		if (mode == "p" || mode == "a" || mode == "n") {
			this.opacity = 0.88;
		} else {
			this.opacity = 1;
		}
	
		this.popOBJ.style.visibility = "visible"; // レスポップアップ表示
		/*
		this.popOBJ.ondblclick = function () {
			this.style.visibility = "hidden";
			ResPopUpManager.rmResPopUp(this.id);
		}
		*/
		this.popOBJ.onmouseout = function () {
			hideResPopUp(this.id)
		}
	
	},

	// レスポップアップを非表示タイマーする
	// @return  void
	hideResPopUp: function ()
	{
		var delaySec = cDelayHideSec;
		
		if (this.isModeSpm()) {
			delaySec = cSpmDelayHideSec;
		}
		
		// 一定時間表示したら消す
		this.hideTimerID = setTimeout("doHideResPopUp('" + this.popId + "')", delaySec);
	},

	// レスポップアップを非表示にする 順番待ち
	// @return  void
	doHideResPopUp: function ()
	{
		if (!this.isModeSpm()) {
			for (i = 0; i < gPOPS.length; i++) {
				// 自分より表示順位の高いのがあれば、消すのを遅延する
				if (this.zNum < gPOPS[i].zNum) {
					//clearTimeout(this.hideTimerID); // タイマーを解除
					// 一定時間表示したら消す
					this.hideTimerID = setTimeout("hideResPopUp('" + this.popId + "')", cDelayHideSec);
					return;
				}
			}
		}
		this.nowHideResPopUp();
	},

	// レスポップアップを非表示にする 即
	// @return  void
	nowHideResPopUp: function ()
	{
		var me = this;
		gHideTimerIds[me.popId] = true;
		if (!gHideTimerIds[me.popId]) {
			//this.setOpacity(1, true, 0.15);
			return;
		} else {
			delete gHideTimerIds[me.popId];
			me.popOBJ.style.visibility = "hidden"; // レスポップアップ非表示
			// clearTimeout(this.hideTimerID); // タイマーを解除
			ResPopUpManager.rmResPopUp(me.popId);
		}
	},
	
	// スマートポップアップメニューなら true
	// @return  boolean
	isModeSpm: function ()
	{
		// popId
		// q{resnum}of{datkey}
		// aThread_{$this->bbs}_{$this->key}
		// p,nは不明
		
		var mode = this.popId.charAt(0);
		if (mode == "p" || mode == "a" || mode == "n") {
			return true;
		}
		return false;
	}
}

//=============================
// 関数定義
//=============================
/**
 * arraySplice
 *
 * anArray.splice(i, 1); Array.splice はIE5.5未満未対応なので代替処理
 * @return array
 */
function arraySplice(anArray, i)
{
	var newArray = new Array();
	
	for (j = 0; j < anArray.length; j++) {
		if (j != i) {
			newArray[newArray.length] = anArray[j];
		}
	}
	return newArray;
}

/**
 * レスポップアップを表示タイマーする
 *
 * 引用レス番に onMouseover で呼び出される
 * [memo] 第一引数をeventオブジェクトにした方がよいだろうか。
 *
 * @param  boolean  onPopSpace  ポップアップスペースへのonmouseoverでの呼び出しなら。重複呼び出し回避のため。
 */
function showResPopUp(popId, ev, onPopSpace)
{
	if (popId.indexOf("-") != -1) { return; } // 連番 (>>1-100) は非対応なので抜ける
	
	if (document.all) { // IE用
		var body = (document.compatMode=='CSS1Compat') ? document.documentElement : document.body;
		var x = body.scrollLeft + event.clientX; // 現在のマウス位置のX座標
		var y = body.scrollTop + event.clientY; // 現在のマウス位置のY座標
	} else if (document.getElementById) { // DOM対応用（Mozilla）
		var x = 0; // 現在のマウス位置のX座標 ev.pageX 070828 左はしから出るよう書き換え
		var y = ev.pageY+20; // 現在のマウス位置のY座標
	} else {
		return;
	}
	
	var aResPopUp = ResPopUpManager.getResPopUp(popId);
	if (aResPopUp) {
		delete gHideTimerIds[popId];
		if (aResPopUp.hideTimerID) { clearTimeout(aResPopUp.hideTimerID); } // 非表示タイマーを解除

		if (onPopSpace) {
			if (gOnPopSpaceId == popId) {
				return;
			} else {
				gOnPopSpaceId = popId;
			}
		}
		
		// 再表示時の zIndex 処理
		if (aResPopUp.zNum < zNum) {
			aResPopUp.zNum = ++zNum;
			aResPopUp.popOBJ.style.zIndex = aResPopUp.zNum;
		}
		
		if (!onPopSpace) {
			// Safariでは高速でマウスオーバー、マウスアウトが発生してマウスについてきてしまう（嫌な仕様だ）
			if (!isSafari()) {
				aResPopUp.setPosResPopUp(x,y);
			}
		}
		
		return;
	}
	
	// doShowResPopUp(popId, ev);
	
	aShowTimer = new Object();
	aShowTimer.x = x;
	aShowTimer.y = y;
	
	// 一定時間したら表示する
	aShowTimer.timerID = setTimeout("doShowResPopUp('" + popId + "')", cDelayShowSec);
	
	gShowTimerIds[popId] = aShowTimer;
	//alert(gShowTimerIds[popId].timerID);
}

/**
 * レスポップアップを表示する
 */
function doShowResPopUp(popId)
{
	var x = gShowTimerIds[popId].x;
	var y = gShowTimerIds[popId].y;
	var aResPopUp = ResPopUpManager.getResPopUp(popId);
	if (aResPopUp) {
		if (aResPopUp.hideTimerID) { clearTimeout(aResPopUp.hideTimerID); } // 非表示タイマーを解除
		/*
		// 再表示時の zIndex 処理
		if (aResPopUp.zNum < zNum) {
			aResPopUp.zNum = ++zNum;
			aResPopUp.popOBJ.style.zIndex = aResPopUp.zNum;
		}
		*/
		return;
	}
	
	zNum++;
	aResPopUp = ResPopUpManager.addResPopUp(popId); // 新しいポップアップを追加

	aResPopUp.showResPopUp(x, y);
}

/**
 * レスポップアップを非表示タイマーする
 *
 * 引用レス番から onMouseout で呼び出される
 */
function hideResPopUp(popId)
{
	if (popId.indexOf("-") != -1) { return; } // 連番 (>>1-100) は非対応なので抜ける
	
	if (gShowTimerIds[popId].timerID) { clearTimeout(gShowTimerIds[popId].timerID); } // 表示タイマーを解除
	
	var aResPopUp = ResPopUpManager.getResPopUp(popId);
	if (aResPopUp) {
		aResPopUp.hideResPopUp();
	}
}

/**
 * レスポップアップを非表示にする
 */
function doHideResPopUp(popId)
{
	var aResPopUp = ResPopUpManager.getResPopUp(popId);
	if (aResPopUp) {
		aResPopUp.doHideResPopUp();
	}
}


