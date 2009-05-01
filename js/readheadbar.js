/* p2 - read ヘッドバーを表示したりする */

gShowKossoriHeadbarTimerId = null;
gDontShowKossoriHeadbar = null; // ダブルクリックで消した後に、再表示してしまわないようにするためのフラグ
gShowKossoriHeadbarTimerDelaySec = 0.5;

gDontHideResbar = null;
gkakikoWidth = 598;

gFadeoutResBarTimerId = null

// @accsess private
// @return integer
function getClientY(ev) {
	var clientY = false;
	
	// Safariのバージョン3未満は特殊
	var safariVer = isSafari(true);
	if (typeof safariVer == 'string') {
		if (res = safariVer.match("[0-9]")) {
			if (res < 3) {
				var clientY = ev.clientY - document.body.scrollTop;
			}
		}
	}
	if (!clientY) {
		clientY = ev.clientY;
	}
	return clientY;
}

// @access  public
// @return  void
function showHeadBar(ev) {
	var pageXY = getPageXY(ev);
	
	var clientY = getClientY(ev);
	
	// id kossoriHeadbar
	// alert(kossoriElm.offsetHeight);
	// 通常 67 過去ログ 183
	
	var hideLine = 90;
	var kossoriElm = document.getElementById("kossoriHeadbar");
	if (kossoriElm) {
		hideLine = kossoriElm.offsetHeight + 23;
	}
	// show the head bar
	if (pageXY[1] > 80) {
		if (clientY < 36 && clientY > 3 && ev.clientX < YAHOO.util.Dom.getViewportWidth() - 12) {
			showKossoriHeadbarTimer();
			return;
		} else if (clientY > hideLine || clientY < 3) {
			hideKossoriHeadbar();
		}
	}
	
	// show the res bar
	var dh = YAHOO.util.Dom.getDocumentHeight();
	var vh = YAHOO.util.Dom.getViewportHeight();
	//window.status = dh + ',' + vh + ',' + pageXY[1] + ',' + ev.offsetY + ',' + ev.clientY + ',' + clientY;
	if ((dh - pageXY[1]) < 48) {
		//window.status = vh - clientY;
	
	// (vh - clientY) < 20
	} else if (ev.clientX > YAHOO.util.Dom.getViewportWidth() - 28 && ev.clientX < YAHOO.util.Dom.getViewportWidth() - 6) {
		//window.status = vh - clientY;
		//showResbar(ev);
	}
}

// @return  void
function showKossoriHeadbarTimer() {
	if (gDontShowKossoriHeadbar) {
		return;
	}
	if (!gShowKossoriHeadbarTimerId) {
		gShowKossoriHeadbarTimerId = setTimeout("showKossoriHeadbarDo()", gShowKossoriHeadbarTimerDelaySec * 1000);
	}
}

// showKossoriHeadbarTimer() から呼ばれる
// @return  void
function showKossoriHeadbarDo()
{
	// レスポップアップ表示中は、表示しない
	if (gPOPS.length) {
		return;
	}

	var kossoriElm = document.getElementById("kossoriHeadbar");
	if (!kossoriElm) {
		var header = document.getElementById("header");
		kossoriElm = header.cloneNode(true)
		kossoriElm.id = 'kossoriHeadbar';
		header.appendChild(kossoriElm);
	}

	if (document.all) {
		var body = getDocumentBodyIE();
		kossoriElm.style.pixelTop  = body.scrollTop;
	} else {
		kossoriElm.style.top  = window.pageYOffset + 'px';
		//window.status = window.pageYOffset;
	}
	//kossoriElm.onmouseout = function(){ this.style.display = 'none' };
	kossoriElm.ondblclick = function(){
		gDontShowKossoriHeadbar = true;
		this.style.display = 'none';
	};
	kossoriElm.style.display = 'block';
}

// @return  void
function hideKossoriHeadbar() {
	var kossoriElm = document.getElementById("kossoriHeadbar");
	if (!kossoriElm) {
		return;
	}
	gDontShowKossoriHeadbar = null;
	kossoriElm.style.display = 'none';
	clearKossoriHeadbarTimerId();
}


// @return  void
function clearKossoriHeadbarTimerId() {
	if (gShowKossoriHeadbarTimerId) {
		clearTimeout(gShowKossoriHeadbarTimerId);
		gShowKossoriHeadbarTimerId = null;
	}
}



// @return  void
function showResbar(ev, fromRes) {
	
	var clientY = getClientY(ev);
	
	var kakikoElm = document.getElementById('kakiko');
	if (!kakikoElm) {
		return;
	}
	//kakikoElm.style.position = 'absolute';
	
	var kakikoHeight = 340;
	
	var kakikoLeft = YAHOO.util.Dom.getViewportWidth() - gkakikoWidth - 38;
	if (kakikoLeft < 0) {
		kakikoLeft = 0;
	}
	
	//var kakikoTop = YAHOO.util.Dom.getViewportHeight() - kakikoHeight - 32;
	var kakikoTop = clientY - 74;
	if (kakikoTop < 0) {
		kakikoTop = 0;
	}
	
	if (fromRes) {
		//kakikoTop = kakikoTop - kakikoHeight + 56;
		kakikoTop = clientY + 18;
	}

	if (document.all) {
		var body = getDocumentBodyIE();
		kakikoElm.style.pixelTop  = body.scrollTop + kakikoTop;
		kakikoElm.style.pixelLeft = kakikoLeft;
	} else {
		var aTop = window.pageYOffset + kakikoTop;
		kakikoElm.style.top  = aTop + 'px';
		kakikoElm.style.left = kakikoLeft + "px";
	}
	
	//if (typeof gKakikoDD == 'undefined') {
		kakikoElm.style.width = gkakikoWidth + "px";
		kakikoElm.style.padding = "4px 16px";
		kakikoElm.style.border = 'solid #ccc';
		kakikoElm.style.borderWidth = '1px 2px 1px 1px';
	
		kakikoElm.ondblclick = function(){hideResBar(null, true);};
		
	if (typeof gKakikoEl == 'undefined') {
		gKakikoEl = getEl(kakikoElm);
		//gKakikoEl.setOpacity(1);
	
		kakikoElm.onmouseover = function(){
			gDontHideResbar = true;
			if (gFadeoutResBarTimerId) {
				clearTimeout(gFadeoutResBarTimerId);
			}
			gKakikoEl.setOpacity(1);
		};
		kakikoElm.onmouseout = function(){
			gFadeoutResBarTimerId = setTimeout("fadeoutResBar()", 0.1 * 1000);
		};

		// DD有効にするとFirefoxでテキストエリア入力ができない？
		// IEではDD不安定…
		if (document.all) {
			gKakikoDD = new YAHOO.util.DD("kakiko");
		}
	}
	
	kakikoElm.style.display = 'block';
}

function fadeoutResBar() {
	gDontHideResbar = false;
	gKakikoEl.setOpacity(0.15, true, 0.15);
}

function hideResBar(ev, forth) {
	/*
	if (typeof gKakikoDD == 'undefined') {
		return;
	}
	   */
	if (!forth && gDontHideResbar) {
		return;
	}
	var kakikoElm = document.getElementById('kakiko');
	if (!kakikoElm) {
		return;
	}
	kakikoElm.style.display = 'none';
	//delete gKakikoDD;
}
