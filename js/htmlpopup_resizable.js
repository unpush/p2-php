/*
   p2 - HTMLをポップアップするためのJavaScript
   
   @thanks http://www.yui-ext.com/deploy/yui-ext/docs/
*/

//showHtmlDelaySec = 0.2 * 1000; // HTML表示ディレイタイム。マイクロ秒。

gShowHtmlTimerID = null;
gNodePopup = null;	// iframeを格納するdiv要素
//gNodeClose = null; // ×を格納するdiv要素
tUrl = ""; // URLテンポラリ変数
gUrl = ""; // URLグローバル変数

// ブラウザ画面（スクリーン上）のマウスの X, Y座標
gMouseX = 0;
gMouseY = 0;

iResizable = null;
stophide = false;

/**
 * HTMLプアップを表示する
 * 複数の引用レス番や(p)の onMouseover で呼び出される
 * [memo] 第一引数をeventオブジェクトにした方がよいだろうか。
 *
 * @access public
 */
function showHtmlPopUp(url, ev, showHtmlDelaySec)
{
	if (!document.createElement) { return; } // DOM非対応なら抜ける
	
	// まだ onLoad されていなく、コンテナもなければ、抜ける
	if (!gIsPageLoaded && !document.getElementById('popUpContainer')) {
		return;
	}
	
	showHtmlDelaySec = showHtmlDelaySec * 1000;

	if (!gNodePopup || url != gUrl) {
		tUrl = url;

		var pointer = getPageXY(ev);
		gMouseX = pointer[0];
		gMouseY = pointer[1];
		
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
	hideHtmlPopUp(null, true);

	gUrl = tUrl;
	var popup_x_adjust = 7;			// popup(iframe)のx軸位置調整
	var closebox_width = 18;		// ×の横幅
	var adjust_for_scrollbar = 22;	// 22 スクロールバーを考慮して少し小さ目に微調整
	
	if (gUrl.indexOf("kanban.php?") != -1) { popup_x_adjust += 23; }

	if (!gNodePopup) {
		gNodePopup = document.createElement('iframe');
		gNodePopup.setAttribute('id', 'iframespace');
		gNodePopup.style.backgroundColor = "#ffffff";
		
		/*
		gNodeClose = document.createElement('div');
		gNodeClose.setAttribute('id', "closebox");
		gNodeClose.setAttribute('onMouseover', "hideHtmlPopUp(ev)");
		*/
		
		var closeX = gMouseX + popup_x_adjust - closebox_width;
		
		// IE用
		if (document.all) {
			var body = getDocumentBodyIE();
			
			var iframeX = gMouseX + popup_x_adjust;
			gNodePopup.style.pixelLeft  = iframeX;			// ポップアップ位置 iframeのX座標
			gNodePopup.style.pixelTop  = body.scrollTop;	// ポップアップ位置 iframeのY座標
			// document.body.scrollTop は DOCTIYEで document.documentElement.scrollTop になるらしい
			
			/*
			gNodeClose.style.pixelLeft  = closeX; 		// ポップアップ位置 ×のX座標
			// ポップアップ位置 ×のY座標
			var close_top = getCloseTop(body.scrollTop + body.clientHeight);
			gNodeClose.style.pixelTop = close_top;
			*/
			
			var iframe_width = body.clientWidth - gNodePopup.style.pixelLeft - adjust_for_scrollbar;
			var iframe_height = body.clientHeight - adjust_for_scrollbar;
			
			widthRatio = 0.6;
			if (iframe_width < body.clientWidth * widthRatio) {
				addIframeWidth = (body.clientWidth * widthRatio) - iframe_width;
				iframe_width += addIframeWidth;
				gNodePopup.style.pixelLeft = iframeX - addIframeWidth;
			}
		
		// DOM対応用（Mozilla）
		} else if (document.getElementById) {
			
			var iframeX = gMouseX + popup_x_adjust;
			gNodePopup.style.left = iframeX + "px"; 			// ポップアップ位置 iframeのX座標
			gNodePopup.style.top  = window.pageYOffset + "px";	// ポップアップ位置 iframeのY座標
			
			/*
			gNodeClose.style.left = closeX + "px"; 			// ポップアップ位置 ×のX座標
			// ポップアップ位置 ×のY座標
			var close_top = getCloseTop(window.pageYOffset + window.innerHeight);
			gNodeClose.style.top = close_top + "px";
			*/
			
			var iframe_width = window.innerWidth - iframeX - adjust_for_scrollbar;
			var iframe_height = window.innerHeight - adjust_for_scrollbar;
			
			widthRatio = 0.6;
			if (iframe_width < window.innerWidth * widthRatio) {
				addIframeWidth = (window.innerWidth * widthRatio) - iframe_width;
				iframe_width += addIframeWidth;
				var iframe_left = iframeX - addIframeWidth;
				gNodePopup.style.left = iframe_left + 'px';
			}
		}

		gNodePopup.src = gUrl;
		gNodePopup.frameborder = 0;
		gNodePopup.width = iframe_width;
		gNodePopup.height = iframe_height;
		
		pageMargin_at = "";
		// 画像の場合はマージンをゼロにする
		if (gUrl.match(/(jpg|jpeg|gif|png)$/)) {
			//pageMargin_at = ' marginheight="0" marginwidth="0" hspace="0" vspace="0"';
			
			// ↓の設定は効いていない？innerHTMLでは効いていた気がする
			gNodePopup.marginheight = 0;
			gNodePopup.marginwidth = 0;
			gNodePopup.hspace = 0;
			gNodePopup.vspace = 0;
		}
		
		// 2006/11/30 これまでdiv内のinnerHTMLにしていたのは、何か理由があった気もするが忘れた。
		// IEでのポップアップ内ポップアップはどちらにしろできていないようだ。
		//gNodePopup.innerHTML = "<iframe id=\"iframepop\" src=\""+gUrl+"\" frameborder=\"1\" border=\"1\" style=\"background-color:#fff;margin-right:8px;margin-bottom:8px;\" width=" + iframe_width + " height=" + iframe_height + pageMargin_at +">&nbsp;</iframe>";
		
		//gNodeClose.innerHTML = "<b onMouseover=\"hideHtmlPopUp(ev)\">×</b>";
		
		var popUpContainer = document.getElementById("popUpContainer");
		
		var headerEI = document.getElementById("header"); //read用
		if (headerEI) {
			popUpContainer = headerEI;
		} else {
			var Ntd1EI = document.getElementById("ntd1"); // read_new用
			if (Ntd1EI) {
				popUpContainer = Ntd1EI;
			}
		}
		// popUpContainer はbody読み込みを完了する前から利用できるように用意している。
		// popUpContainer では、YAHOO.ext.Resizable の表示を閉じた時に、IEで空白スペースが入ってしまう（？）ので、
		// header がある時は、headerを利用している
		if (popUpContainer) {
			popUpContainer.appendChild(gNodePopup);
			//popUpContainer.appendChild(gNodeClose);
		} else {
			document.body.appendChild(gNodePopup);
			//document.body.appendChild(gNodeClose);
		}
		
		if (gIsPageLoaded) {
			setIframeResizable();
		} else {
			var setIframeResizableOnLoad = function(){ setIframeResizable(); }
			YAHOO.util.Event.addListener(window, 'load', setIframeResizableOnLoad);
		}
	}
}

function setIframeResizable()
{
	if (!gNodePopup) {
		return;
	}
	
    iResizable = new YAHOO.ext.Resizable('iframespace', {
            pinned:true,
            //width: 200,
            //height: 100,
            minWidth:100,
            minHeight:50,
            handles: 'all',
            wrap:true,
            draggable:true,
            dynamic: true
    });
	
	var iframespaceEl = iResizable.getEl();
	
    iframespaceEl.dom.style.backgroundColor = "#ffffff";
	iframespaceEl.dom.ondblclick = hideHtmlPopUp;
	
	var msgClose = '閉じるには、ポップアップ外をクリック';
	window.status = msgClose;
	
    iframespaceEl.on('resize', function(){
        stophide = true;
		this.dom.title = msgClose;
    });
}

// ページトップからのマウス位置のX, Y座標
// @return  array
function getPageXY(ev)
{
	/*
	// Yahoo UI は使えそうで使えない？何かありそうな気がするんだが…
	alert(YAHOO.util.Dom.getClientHeight()); // 画面の高さ // Deprecated Now using getViewportHeight. 
	alert(YAHOO.util.Dom.getViewportHeight()); // 画面  (excludes scrollbars)
	alert(YAHOO.util.Dom.getDocumentHeight()); // ドキュメント全体
	// YAHOO.util.Event.getPageX(ev)
	var cursor1 = YAHOO.util.Event.getXY(ev); // ページ内
	alert(cursor1);
	*/
	
	// IE用
	if (document.all) {
		// 現在のマウス位置のX, Y座標
		var body = getDocumentBodyIE();
		// IEならwindow.eventでグローバルに参照できるが、ここではIE以外にも合わせて使わないでおく
		var pageX = body.scrollLeft + ev.clientX;
		var pageY = body.scrollTop  + ev.clientY;

	} else {
		// pageX, pageY は、IEは非サポート
		var pageX = ev.pageX;
		var pageY = ev.pageY;
	}
	return [pageX, pageY];
}

/**
 * HTMLポップアップを非表示にする
 *
 * @access public
 */
function hideHtmlPopUp(ev, fast)
{
	if (!gIsPageLoaded) {
		return false;
	}
	
	if (!document.createElement) { return; } // DOM非対応なら抜ける
	
	if (stophide) {
		stophide = false;
		return;
	}
	
	if (!gFade) {
		fast = true;
	}
	
	if (iResizable) {
		var iframespaceEl = iResizable.getEl();
		
		var iRegion = YAHOO.util.Region.getRegion(iframespaceEl.dom);
		
		if (ev) {
			var pageXY = getPageXY(ev);
			//alert(pageXY);
			var pagePoint = new YAHOO.util.Point(pageXY);
			
			if (iRegion.intersect(pagePoint)) {
				return;
			}
			//alert(iRegion);
		}
		
		if (fast) {
			iframespaceEl.remove();
			iResizable = null;
			hideHtmlPopUpDo();
		} else {
			iframespaceEl.setOpacity(0, true, 0.15, function(){
					this.remove();
					iResizable = null;
					hideHtmlPopUpDo();
				});
		}
	
	} else {
		hideHtmlPopUpDo();
	}

}

function hideHtmlPopUpDo()
{
	if (gShowHtmlTimerID) { clearTimeout(gShowHtmlTimerID); } // HTML表示ディレイタイマーを解除する
	

	if (gNodePopup) {
		gNodePopup.style.visibility = "hidden";
		gNodePopup.parentNode.removeChild(gNodePopup);
		gNodePopup = null;
	}
	
	/*
	if (gNodeClose) {
		gNodeClose.style.visibility = "hidden";
		gNodeClose.parentNode.removeChild(gNodeClose);
		gNodeClose = null;
	}
	*/
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
