/*
	p2 - HTMLをポップアップするためのJavaScript
*/

//showHtmlDelaySec = 0.2 * 1000; // HTML表示ディレイタイム。マイクロ秒。

gShowHtmlTimerID = 0;
gNodePopup = false;	// iframeを格納するdiv要素
gNodeClose = false; // ×を格納するdiv要素
tUrl = ""; // URLテンポラリ変数
gUrl = ""; // URLグローバル変数

// ブラウザ画面（スクリーン上）のマウスの X, Y座標
gMouseX = 0;
gMouseY = 0;

/**
 * getDocumentBodyIE
 */
function getDocumentBodyIE()
{
	return (document.compatMode=='CSS1Compat') ? document.documentElement : document.body;
}

/**
 * HTMLプアップを表示する
 * 複数の引用レス番や(p)の onMouseover で呼び出される
 *
 * @access public
 */
function showHtmlPopUp(url,ev,showHtmlDelaySec)
{
	if (!document.createElement) { return; } // DOM非対応なら抜ける
	
	// まだ onLoad されていなく、コンテナもなければ、抜ける
	if (!gIsPageLoaded && !document.getElementById('popUpContainer')) {
		return;
	}
	
	showHtmlDelaySec = showHtmlDelaySec * 1000;

	if (!gNodePopup || url != gUrl) {
		tUrl = url;

		// IE用
		if (document.all) {
			// 現在のマウス位置のX, Y座標
			var body = getDocumentBodyIE();
			gMouseX = body.scrollLeft + event.clientX;
			gMouseY = body.scrollTop  + event.clientY;
		
		} else {
			// pageX, pageY - ブラウザ画面（スクリーン上）のマウスの X, Y座標。IEは非サポート
			gMouseX = ev.pageX;
			gMouseY = ev.pageY;
		}
		
		// HTML表示ディレイタイマー
		gShowHtmlTimerID = setTimeout("showHtmlPopUpDo()", showHtmlDelaySec);
	}
}

/**
 * showHtmlPopUpDo() から利用される
 *
 * @return integer
 */
function getCloseTop(win_bottom)
{
	var close_top_adjust = 16;

	close_top = Math.min(win_bottom - close_top_adjust, gMouseY + close_top_adjust);
	if (close_top >= win_bottom - close_top_adjust) {
		close_top = gMouseY - close_top_adjust - 12;
	}
	return close_top;
}

/**
 * HTMLポップアップの実行
 */
function showHtmlPopUpDo()
{
	// あらかじめ既存のHTMLポップアップを閉じておく
	hideHtmlPopUp();

	gUrl = tUrl;
	var popup_x_adjust = 7;			// popup(iframe)のx軸位置調整
	var closebox_width = 18;		// ×の横幅
	var adjust_for_scrollbar = 22;	// スクロールバーを考慮して少し小さ目に微調整
	
	if (gUrl.indexOf("kanban.php?") != -1) { popup_x_adjust += 23; }

	if (!gNodePopup) {
		gNodePopup = document.createElement('div');
		gNodePopup.setAttribute('id', "iframespace");

		gNodeClose = document.createElement('div');
		gNodeClose.setAttribute('id', "closebox");
		//gNodeClose.setAttribute('onMouseover', "hideHtmlPopUp()");
		
		var closeX = gMouseX + popup_x_adjust - closebox_width;
		
		// IE用
		if (document.all) {
			var body = getDocumentBodyIE();

			gNodePopup.style.pixelLeft  = gMouseX + popup_x_adjust;	// ポップアップ位置 iframeのX座標
			gNodePopup.style.pixelTop  = body.scrollTop;	// ポップアップ位置 iframeのY座標
			gNodeClose.style.pixelLeft  = closeX; 		// ポップアップ位置 ×のX座標
			
			// ポップアップ位置 ×のY座標
			var close_top = getCloseTop(body.scrollTop + body.clientHeight);
			gNodeClose.style.pixelTop = close_top;
			
			var iframe_width = body.clientWidth - gNodePopup.style.pixelLeft - adjust_for_scrollbar;
			var iframe_height = body.clientHeight - adjust_for_scrollbar;
		
		// DOM対応用（Mozilla）
		} else if (document.getElementById) {
			
			gNodePopup.style.left = (gMouseX + popup_x_adjust) + "px"; 	// ポップアップ位置 iframeのX座標
			gNodePopup.style.top  = window.pageYOffset;		// ポップアップ位置 iframeのY座標
			gNodeClose.style.left = closeX + "px"; 			// ポップアップ位置 ×のX座標
			
			// ポップアップ位置 ×のY座標
			var close_top = getCloseTop(window.pageYOffset + window.innerHeight);
			gNodeClose.style.top = close_top + "px";
			
			var iframe_width = window.innerWidth - (gMouseX + popup_x_adjust) - adjust_for_scrollbar;
			var iframe_height = window.innerHeight - adjust_for_scrollbar;
		}

		pageMargin = "";
		// 画像の場合はマージンをゼロにする
		if (gUrl.match(/(jpg|jpeg|gif|png)$/)) {
			pageMargin = ' marginheight="0" marginwidth="0" hspace="0" vspace="0"';
		}
		gNodePopup.innerHTML = "<iframe src=\""+gUrl+"\" frameborder=\"1\" border=\"1\" style=\"background-color:#fff;\" width=" + iframe_width + " height=" + iframe_height + pageMargin +">&nbsp;</iframe>";
		
		gNodeClose.innerHTML = "<b onMouseover=\"hideHtmlPopUp()\">×</b>";
		
		var popUpContainer = document.getElementById("popUpContainer");
		if (popUpContainer) {
			popUpContainer.appendChild(gNodePopup);
			popUpContainer.appendChild(gNodeClose);
		} else {
			document.body.appendChild(gNodePopup);
			document.body.appendChild(gNodeClose);
		}
	}
}

/**
 * HTMLポップアップを非表示にする
 *
 * @access public
 */
function hideHtmlPopUp()
{
	if (!document.createElement) { return; } // DOM非対応なら抜ける
	
	if (gShowHtmlTimerID) { clearTimeout(gShowHtmlTimerID); } // HTML表示ディレイタイマーを解除する
	if (gNodePopup) {
		gNodePopup.style.visibility = "hidden";
		gNodePopup.parentNode.removeChild(gNodePopup);
		gNodePopup = false;
	}
	if (gNodeClose) {
		gNodeClose.style.visibility = "hidden";
		gNodeClose.parentNode.removeChild(gNodeClose);
		gNodeClose = false;
	}
}

/**
 * HTML表示タイマーを解除する
 *
 * (p)の onMouseout で呼び出される
 */
function offHtmlPopUp()
{
	// HTML表示ディレイタイマーがあれば解除しておく
	if (gShowHtmlTimerID) {
		clearTimeout(gShowHtmlTimerID);
	}
}
