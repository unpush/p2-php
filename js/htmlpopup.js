/*
	p2 - HTMLをポップアップするためのJavaScript
*/

//showHtmlDelaySec = 0.2 * 1000; // HTML表示ディレイタイム。マイクロ秒。

showHtmlTimerID = 0;
node_div = false;
node_close = false;
tUrl = ""; // URLテンポラリ変数
gUrl = ""; // URLグローバル変数
gX = 0;
gY = 0;
ecX = 0;
ecY = 0;

/**
 * クローズボタンもしくはポップアップ外部をクリックして閉じるための関数
 */
function hideHtmlPopUpCallback(evt)
{
	hideHtmlPopUp();

	// イベントリスナを削除
	if (window.removeEventListener) {
		// W3C  DOM
		document.body.removeEventListener('click', hideHtmlPopUpCallback, false);
		evt.preventDefault();
	} else if (window.detachEvent) {
		// IE
		document.body.detachEvent('onclick', hideHtmlPopUpCallback);
		window.event.returnValue = false;
	}
}

/**
 * HTMLポップアップを表示する
 *
 * 複数の引用レス番や(p)の onMouseover で呼び出される
 */
function showHtmlPopUp(url,ev,showHtmlDelaySec)
{
	if (!document.createElement) { return; } // DOM非対応

	// まだ onLoad されていなく、コンテナもなければ、抜ける
	if (!gIsPageLoaded && !document.getElementById('popUpContainer')) {
		return;
	}

	showHtmlDelaySec = showHtmlDelaySec * 1000;

	if (!node_div || url != gUrl) {
		tUrl = url;
		gX = ev.pageX;
		gY = ev.pageY;
		if (document.all) { // IE
			ecX = event.clientX;
			ecY = event.clientY;
		}
		showHtmlTimerID = setTimeout("showHtmlPopUpDo()", showHtmlDelaySec); // HTML表示ディレイタイマー
	}

	// HTMLポップアップ外部をクリックしても閉じられるようにする
	if (window.addEventListener) {
		// W3C  DOM
		document.body.addEventListener('click', hideHtmlPopUpCallback, false);
	} else if (window.attachEvent) {
		// IE
		document.body.attachEvent('onclick', hideHtmlPopUpCallback);
	}
}

/**
 * HTMLポップアップの実行
 */
function showHtmlPopUpDo()
{
	// あらかじめ既存のHTMLポップアップを閉じておく
	hideHtmlPopUp();

	gUrl = tUrl;
	var x_adjust = 7;	// x軸位置調整
	var y_adjust = -46;	// y軸位置調整
	var closebox_width = 18;

	if (!node_div) {
		node_div = document.createElement('div');
		node_div.setAttribute('id', "iframespace");

		if (!window.addEventListener && !window.attachEvent) {
			node_close = document.createElement('div');
			node_close.setAttribute('id', "closebox");
		}

		// IE用
		if (document.all) {
			var body = (document.compatMode=='CSS1Compat') ? document.documentElement : document.body;
			gX = body.scrollLeft + ecX; // 現在のマウス位置のX座標
			gY = body.scrollTop + ecY; // 現在のマウス位置のY座標
			node_div.style.pixelLeft  = gX + x_adjust; //ポップアップ位置
			node_div.style.pixelTop  = body.scrollTop; //gY + y_adjust;
			var cX = gX + x_adjust - closebox_width;
			if (node_close) {
				node_close.style.pixelLeft  = cX; //ポップアップ位置
				node_close.style.pixelTop  = body.scrollTop; //gY + y_adjust;
			}
			var yokohaba = body.clientWidth - node_div.style.pixelLeft -20; //微調整付
			var tatehaba = body.clientHeight -20;

		// DOM対応用（Mozilla）
		} else {
			node_div.style.left = gX + x_adjust + "px"; //ポップアップ位置
			node_div.style.top = window.pageYOffset + "px"; //gY + y_adjust + "px";
			var cX = gX + x_adjust - closebox_width;
			if (node_close) {
				node_close.style.left = cX + "px"; // ポップアップ位置
				node_close.style.top = window.pageYOffset + "px"; // gY + y_adjust + "px";
			}
			var yokohaba = window.innerWidth - gX - x_adjust -20; // 微調整付
			var tatehaba = window.innerHeight - 20;
		}

		pageMargin = "";
		// 画像の場合はマージンをゼロに
		if (gUrl.match(/(jpg|jpeg|gif|png)$/)) {
			pageMargin = " marginheight=\"0\" marginwidth=\"0\" hspace=\"0\" vspace=\"0\"";
		}
		node_div.innerHTML = "<iframe src=\""+gUrl+"\" frameborder=\"1\" border=\"1\" style=\"background-color:#fff;\" width=" + yokohaba + " height=" + tatehaba + pageMargin +">&nbsp;</iframe>";

		if (node_close) {
			node_close.innerHTML = '<b onclick="hideHtmlPopUpCallback(event)" style="cursor:pointer;">×</b>';
		}

		var popUpContainer = document.getElementById("popUpContainer");
		if (!popUpContainer) {
			popUpContainer = document.body;
		}
		popUpContainer.appendChild(node_div);
		if (node_close) {
			popUpContainer.appendChild(node_close);
		}
	}
}

/**
 * HTMLポップアップを非表示にする
 */
function hideHtmlPopUp()
{
	if (!document.createElement) { return; } // DOM非対応
	if (showHtmlTimerID) { clearTimeout(showHtmlTimerID); } // HTML表示ディレイタイマーを解除
	if (node_div) {
		node_div.style.visibility = "hidden";
		node_div.parentNode.removeChild(node_div);
		node_div = false;
	}
	if (node_close) {
		node_close.style.visibility = "hidden";
		node_close.parentNode.removeChild(node_close);
		node_close = false;
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
	if (showHtmlTimerID) {
		clearTimeout(showHtmlTimerID);
	}
}
