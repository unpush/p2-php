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

function toggleResBlk(evt, res, mark) {
	var evt = (evt) ? evt : ((window.event) ? window.event : null);
	var target = evt.target ? evt.target :
		(evt.srcElement ? evt.srcElement : null);
	if(typeof(res.ondblclick) !== 'function')
		res.ondblclick = res.onclick;

	// イベント発動チェック
	if (target.className == 'respopup') return;
	var resblock = _findChildByClassName(res, 'resblock');
	if (evt == null || res == null || target == null || resblock == null)
		return;
	var button = resblock.firstChild;
	if (!button) return;
	if (target != res && target != resblock && target != button) {
		// レスリストのクリックかどうか
		var isdescend = function (check) {
			if (!check) return false;
			var test = target;
			do {
				if (test == check) return true;
				test = test.parentNode;
			} while (test && test != res);
		};
		if (!isdescend(_findChildByClassName(res, 'reslist')) &&
			!isdescend(_findChildByClassName(res, 'v_reslist')))
			return;
	}

	var anchors = _findAnchorComment(res);
	if (anchors == null) return;

	if (_findChildByClassName(resblock, 'resblock_inner') !== null &&
			evt.type != 'dblclick') {
		if (mark) resetReaded(res, anchors);
		removeRes(res, button);
	} else {
		insertRes(evt, res, anchors, mark);
	}
}

function insertRes(evt, res, anchors, mark) {

	var resblock = _findChildByClassName(res, 'resblock');
	if (!resblock) return;
	var button = resblock.firstChild;
	var resblock_inner = _findChildByClassName(resblock, 'resblock_inner');
	// 既に開いていた場合
	if (resblock_inner) {
		if (evt.type != 'dblclick') return;
		// ダブルクリックならカスケード
		(function (nodes) {
			for  (var i=0;i<nodes.length;i++) {
				if (nodes[i].className != 'folding_container') continue;
				var anchor = _findAnchorComment(nodes[i]);
				if (anchor != null)
					insertRes(evt, nodes[i],
						_findAnchorComment(nodes[i]), mark);
			}
		 })(resblock_inner.childNodes);
		 return;
	 }

	// reslistがあれば非表示に
	var reslist = _findChildByClassName(res, 'reslist');
	if (reslist) reslist.style.display = 'none';

	var resblock_inner = document.createElement('div');
	var children=anchors.split("/");
	for (var i=0;i<children.length;i++) {
		var importId=children[i];
		var importElement=getElementForCopy(""+importId);

		// オリジナルのレスがあれば見た目変更
		if (mark) (function(origId) {
			var orig = (document.all) ?  document.all[origId]
				: ((document.getElementById) ? document.getElementById(origId)
						: null);
			if (orig) {
				var kls = orig.className.split(' ');
				kls.push('readmessage');
				orig.className = kls.join(' ');
			}
		})('m' + importId.substr(2));

		//参照先レス情報をコピー
		var container=document.createElement('blockquote');
		container.innerHTML=importElement.innerHTML.replace(/id=\".+?\"/g,"");

		var anchor = _findAnchorComment(importElement);
		if (anchor) {
			container.onclick = function (evt) {
				toggleResBlk(evt, this, mark);
			};
			var c_resblock=document.createElement('div');
			c_resblock.className = 'resblock';
			if (button)
				c_resblock.appendChild(button.cloneNode(false));

			var reslist = _findChildByClassName(container, 'reslist');
			if (reslist) {
				container.insertBefore(c_resblock, reslist);
			} else {
				container.appendChild(c_resblock);
			}
			// ダブルクリックならカスケード
			if (evt.type == 'dblclick') {
				insertRes(evt, container, anchor, mark);
			}
		}
		container.className='folding_container';
		resblock_inner.appendChild(container);
	}
	resblock_inner.className='resblock_inner';
	resblock.appendChild(resblock_inner);

	if (button) button.src=button.src.replace(/plus/,'minus');
}

function removeRes(res, button) {

	button.src=button.src.replace(/minus/,'plus');
	var resblock_inner = _findChildByClassName(
			button.parentNode, 'resblock_inner');
	if (resblock_inner) button.parentNode.removeChild(resblock_inner);

	// reslistがあれば表示
	var reslist = _findChildByClassName(res, 'reslist');
	if (reslist) reslist.style.display = 'block';
}

function resetReaded(res, anchors) {
	var resblock = _findChildByClassName(res, 'resblock');
	if (resblock == null) return;
	var resblock_inner = _findChildByClassName(resblock, 'resblock_inner');
	if (resblock_inner == null) return;

	var children=anchors.split("/");
	for (var i=0;i<children.length;i++) {
		// オリジナルのレスがあれば見た目変更

		var origId = 'm' + children[i].substr(2);
		var orig = (document.all) ?  document.all[origId]
			: ((document.getElementById) ? document.getElementById(origId)
					: null);
		if (orig) {
			var kls = orig.className.split(' ');
			for (var j=0;j<kls.length;j++) {
				if (kls[j] == 'readmessage') {
					kls.splice(j, 1);
					orig.className = kls.join(' ');
					break;
				}
			}
		}
	}

	for (var i=0;i<resblock_inner.childNodes.length;i++) {
		resetReaded(resblock_inner.childNodes[i],
			_findAnchorComment(resblock_inner.childNodes[i]));
	}
}

function getElementForCopy(qresID) {
	if (qresID.indexOf("-") != -1) { return null; } // 連番 (>>1-100) は非対応なので抜ける
	
	if (document.all) { // IE用
		aResPopUp = document.all[qresID];
	} else if (document.getElementById) { // DOM対応用（Mozilla）
		aResPopUp = document.getElementById(qresID);
	}

	if (aResPopUp) {
		return aResPopUp;
	} else {
		return null;
	}
}

function _findChildByClassName(p, kls) {
	for (var i=0;i<p.childNodes.length;i++) {
		if (p.childNodes[i].className == kls)
			return p.childNodes[i];
	}
	return null;
}

function _findAnchorComment(res) {
	for (var i=0;i<res.childNodes.length;i++) {
		if (res.childNodes[i].nodeName.toLowerCase().indexOf('comment') != -1) {
			var nv = res.childNodes[i].nodeValue.replace(/^\s+|\s+$/g, '');
			if (nv.indexOf('backlinks:') == 0) {
				return nv.substr('backlinks:'.length);
			}
		}
	}
	return null;
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

	(function (divid) {	// ポップアップの元があればハイライト
		if (document.all) { // IE用
			var orig = document.all['r' + divID.substr(2)];
		} else if (document.getElementById) { // DOM対応用（Mozilla）
			var orig = document.getElementById('r' + divID.substr(2));
		}
		 if (orig) {
			var kls = orig.className.split(' ');
			kls.push('highlight');
			orig.className = kls.join(' ');
		}
	})(divID);
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

	(function (divid) {	// ポップアップ元のハイライトを戻し
		if (document.all) { // IE用
			 var orig = document.all['r' + divID.substr(2)];
		} else if (document.getElementById) { // DOM対応用（Mozilla）
			 var orig = document.getElementById('r' + divID.substr(2));
		}
		 if (orig) {
			var kls = orig.className.split(' ');
			for (var j=0;j<kls.length;j++) {
				if (kls[j] == 'highlight') {
					kls.splice(j, 1);
					orig.className = kls.join(' ');
					break;
				}
			}
		}
	})(divID);
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
			//alert(window.pageYOffset);
			//alert(this.popOBJ.offsetTop);

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
