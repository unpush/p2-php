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

isIE = /*@cc_on!@*/false;

function getElement(id) {
//	// alert(id);
	if (typeof(id) == "string") {
		if (isIE) { // IE用
			return document.all[id];
		} else if (document.getElementById) { // DOM対応用（Mozilla）
			return document.getElementById(id);
		}
	} else {
		return id;
	}
}

function insertRes(outerContainerId,anchors,button) {
	// 参照元の設定
	button.onclick=function () {removeRes(outerContainerId,anchors,button)};
	button.src=button.src.replace(/plus/,'minus');
//	var outerContainer=getElement(outerContainerId);
	var outerContainer=button.parentNode.lastChild; //.lastChild;
	while(outerContainer && outerContainer.className!="reslist") {
		outerContainer=outerContainer.previousSibling;
	}
	// alert(outerContainer.className);
	
	var children=anchors.split("/");
	// alert(children.length);
	for (i=0;i<children.length;i++) {
		var childDiv=outerContainer.childNodes[i];
		// alert(childDiv.className);
		var importId=children[i];
		var importElement=copyHTML(""+importId);
		importElement=importElement.replace(/<!--%%%(.+)%%%-->/,'$1');

		//参照先レス情報をコピー
		var resdiv=document.createElement('blockquote');
		resdiv.innerHTML=importElement; //.replace(/id=\".*?q[rm]\d+?\"/g,"");
		// // alert(resdiv.innerHTML);
		
		resdiv.className='folding_container';
		childDiv.appendChild(resdiv);
		childDiv.lastChild.previousSibling.style.display='none';
	}

}

function removeRes(outerContainerId,anchors,button) {
	// 参照元の設定
	button.onclick=function () {insertRes(outerContainerId,anchors,button)};
	button.src=button.src.replace(/minus/,'plus');
	// // alert(typeof(outerContainerId));
	var outerContainer=getElement(outerContainerId);

	var children=anchors.split("/");
	// alert(children.length);
	for (i=0;i<children.length;i++) {
		var childDiv=outerContainer.childNodes[i];
		childDiv.removeChild(childDiv.lastChild);
		childDiv.firstChild.style.display='block';
	}


}

function copyHTML(qresID) {
	if (qresID.indexOf("-") != -1) { return null; } // 連番 (>>1-100) は非対応なので抜ける
	
	if (document.all) { // IE用
		aResPopUp = document.all[qresID];
	} else if (document.getElementById) { // DOM対応用（Mozilla）
		aResPopUp = document.getElementById(qresID);
	}

	if (aResPopUp) {
		return aResPopUp.innerHTML;
	} else {
		return null;
	}
}

function insertResPopUp(qresID,outer_containerID,button) {
	insertRes(outer_containerID,button);
//	linktext=container.firstChild;
	if (qresID.indexOf("-") != -1) { return; } // 連番 (>>1-100) は非対応なので抜ける
	outer_container=document.getElementById(outer_containerID);
	
	if (document.all) { // IE用
		aResPopUp = document.all[qresID];
	} else if (document.getElementById) { // DOM対応用（Mozilla）
		aResPopUp = document.getElementById(qresID);
	}

	if (aResPopUp) {

		//参照先レス情報をコピー
		resdiv=document.createElement('blockquote');
		resdiv.innerHTML=aResPopUp.innerHTML.replace(/id=\".*?q[rm]\d+\"/g,"");

		resdiv.className='folding_container';
		outer_container.appendChild(resdiv);
		reslist=resdiv.lastChild.childNodes;
		
//		// alert(reslist.length);
		for(i=0;i<reslist.length;i++){  //各要素ループ
			if ((reslist_inner=reslist[i]).className != "reslist_inner") {continue;};
			reslist_inner.innerHTML=reslist_inner.innerHTML.replace(/<!--%%%(.+)%%%-->/,'$1');
		}

		// 参照元の設定
		button.onclick=function () {removeResPopUp(qresID, outer_containerID,button)};
		button.src=button.src.replace(/plus/,'minus');
	}
}

function removeResPopUp(qresID,outer_containerID,button) {
	outer_container=document.getElementById(outer_containerID);
	outer_container.removeChild(outer_container.lastChild);
	button.onclick=function () {insertResPopUp(qresID,outer_containerID,button)};
			button.src=button.src.replace(/minus/,'plus');
}

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

		x = getPageX(ev);
		y = getPageY(ev);

		aShowTimer = new Object();
		aShowTimer.timerID = setTimeout("doShowResPopUp('" + divID + "')", delayShowSec); // 一定時間したら表示する

		aShowTimer.x = x;
		aShowTimer.y = y;

		gShowTimerIds[divID] = aShowTimer;
		//// alert(gShowTimerIds[divID].timerID);
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

	// 表示タイマーを解除
	if (gShowTimerIds[divID] && gShowTimerIds[divID].timerID) {
		clearTimeout(gShowTimerIds[divID].timerID);
	}

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
		var y_adjust = -10;	// y軸位置調整
		if (this.divID.indexOf('spm_') == 0) {
			y_adjust = -10;
		}
		if (this.popOBJ.style.visibility != "visible") {
			this.popOBJ.style.zIndex = this.zNum;
			//x = getPageX(ev); // 現在のマウス位置のX座標
			//y = getPageX(ev); // 現在のマウス位置のY座標
			this.popOBJ.style.left = x + x_adjust + "px"; //ポップアップ位置
			this.popOBJ.style.top = y + y_adjust + "px";
			//// alert(window.pageYOffset);
			//// alert(this.popOBJ.offsetTop);

			var scrollY = getScrollY();
			var windowHeight = getWindowHeight();
			if ((this.popOBJ.offsetTop + this.popOBJ.offsetHeight) > (scrollY + windowHeight)) {
				this.popOBJ.style.top = (scrollY + windowHeight - this.popOBJ.offsetHeight - 20) + "px";
			}
			if (this.popOBJ.offsetTop < scrollY) {
				this.popOBJ.style.top = (scrollY - 2) + "px";
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
