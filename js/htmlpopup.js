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

/**
 * クローズボタンもしくはポップアップ外部をクリックして閉じるための関数
 */
function hideHtmlPopUpCallback(evt)
{
	evt = evt || window.event;

	hideHtmlPopUp();

	// イベントリスナを削除
	if (window.removeEventListener) {
		// W3C  DOM
		document.body.removeEventListener('click', hideHtmlPopUpCallback, false);
		evt.preventDefault();
		evt.stopPropagation();
	} else if (window.detachEvent) {
		// IE
		document.body.detachEvent('onclick', hideHtmlPopUpCallback);
		evt.returnValue = false;
		evt.cancelBubble = true;
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
		gX = getPageX(ev);
		gY = getPageY(ev);
		showHtmlTimerID = setTimeout("showHtmlPopUpDo()", showHtmlDelaySec); // HTML表示ディレイタイマー
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

		node_div.style.left = gX + x_adjust + "px"; //ポップアップ位置
		node_div.style.top = getScrollY() + "px"; //gY + y_adjust + "px";
		if (node_close) {
			node_close.style.left = (gX + x_adjust - closebox_width) + "px"; // ポップアップ位置
			node_close.style.top = node_div.style.top;
		}
		var b_adjust = 4; // iframeの(frameborder+border)*2
		var yokohaba = getWindowWidth() - b_adjust - gX - x_adjust;
		var tatehaba = getWindowHeight() - b_adjust;

		pageMargin = "";
		// 画像の場合はマージンをゼロに
		if (gUrl.search(/\.(jpe?g|gif|png)$/) !== -1) {
			pageMargin = ' marginheight="0" marginwidth="0" hspace="0" vspace="0"';
		}
		node_div.innerHTML = '<iframe src="' + gUrl + '" frameborder="1" border="1" style="background-color:#fff;" width="' + yokohaba + '" height="' + tatehaba + '"' + pageMargin + '>&nbsp;</iframe>';

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

/*
 * Local Variables:
 * mode: javascript
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: t
 * End:
 */
/* vim: set syn=javascript fenc=cp932 ai noet ts=4 sw=4 sts=4 fdm=marker: */
