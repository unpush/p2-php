/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */
/*
	expack - レスの非同期読み込みJavaScriptファイル
	イベントリスナのところはオライリーのJavaScript & DHTMLクックブックを参考にした。
*/

/**
 * XMLHttpRequestで特定のレスを読み込むためのリクエストを送信する
 *
 * ユーザの使い方によっては複数のリクエストが同時に送信されうるので
 * リクエストごとにXMLHttpRequestオブジェクトを作成する。
 * （ひとつのオブジェクトでやろうとするとエラー）
 */
function asyncLoad(host, bbs, key, resnum, q, targetId)
{
	var uri = 'read_async.php?host='+host+'&bbs='+bbs+'&key='+key+'&ls='+resnum+'n&q='+q+'&offline=1';

	var req = getXmlHttp();
	if (!req) {
		alert('XMLHttp not available.');
		return;
	}

	var receiver;
	if (typeof targetId == 'string') {
		receiver = document.getElementById(targetId);
	} else {
		receiver = targetId;
	}
	if (!receiver) {
		alert('asyncLoad() Error: A target element not exists.');
		return;
	}
	receiver.innerHTML = 'Now Loading...';

	// レスポンスが返ってくる前にこの関数を抜けるために非同期モードにする。
	// リクエストごとにXMLHttpRequestオブジェクトが作成され、結果を表示させるオブジェクトも変わるので、
	// ハンドラには専用の無名関数を作成する。
	req.onreadystatechange = function() {
		try {
			// 非同期リクエストのとき、req.readyStateのチェックより前にreq.statusを調べようとすると
			// エラー（NS_ERROR_NOT_AVAILABLE）が起こる。
			if (req.readyState == 4) {
				if (req.status == 200) {
					// Safari(KHTML?)ではencoding属性つきのXML宣言が無いと文字化けするので
					// それを回避するために頭に付けたXML宣言を削除してからHTMLコードとして代入。
					receiver.innerHTML = req.responseText.replace(/^<\?xml .+?\?>\n?/, '');
				} else {
					receiver.innerHTML = '<em>HTTP Error:<br />' + req.status + ' ' + req.statusText + '</em>';
				}
			}
		} catch (e) {
			var msg = (typeof e == 'string') ? e : ((e.message) ? e.message : 'Unknown Error');
			alert("XMLHttpRequest Error:\n" + msg);
		}
	};

	req.open('get', uri, true);
	req.send(null);
}

/**
 * レス内容を読み込む
 */
function loadRes(asyncObj, resnum)
{
	alert("function 'loadRes' is not available.");
}

/**
 * レス内容を読み込み、予め用意しておいた要素と置き換える
 */
function loadResBody(asyncObj, resnum)
{
	var resBodyId = 'rb' + resnum + 'of' + asyncObj.key;
	var resButtonId = 'rbr' + resnum + 'of' + asyncObj.key;

	if (document.getElementById(resBodyId)) {
		return;
	}

	var btn = document.getElementById(resButtonId);
	if (!btn) {
		alert("loadResBody Error: A target element '" + resButtonId + "' not exists.");
		return;
	}

	var resBody = document.createElement('div');
	resBody.id = resBodyId;

	btn.parentNode.replaceChild(resBody, btn);

	asyncLoad(asyncObj.host, asyncObj.bbs, asyncObj.key, resnum, 0, resBodyId);
}

/**
 * レスポップアップを読み込む
 */
function loadResPopUp(asyncObj, resnum)
{
	var qResId = 'q' + resnum + 'of' + asyncObj.key;
	if (document.getElementById(qResId)) {
		return;
	}
	var container = document.getElementById('popUpContainer');
	if (!container) {
		alert("Element 'popUpContainer' not exists.");
		return;
	}

	var qResPopUp = document.createElement('div');
	// idとclassはDOMのプロパティが定義されているので、setAttribute()せずにプロパティを書き換える。
	// ※IEではsetAttribute()でclassを設定してもCSSが反映されないがclassNameで設定すれば反映される。
	qResPopUp.id = qResId;
	qResPopUp.className = 'respopup';

	// イベントリスナを設定
	// DOM2
	if (qResPopUp.addEventListener) {
		qResPopUp.addEventListener('mouseover', showResPopUpListener, false);
		qResPopUp.addEventListener('mouseout', hideResPopUpListener, false);
	// old
	} else {
		qResPopUp.onmouseover = showResPopUpListener;
		qResPopUp.onmouseout = hideResPopUpListener;
	}

	container.appendChild(qResPopUp);

	asyncLoad(asyncObj.host, asyncObj.bbs, asyncObj.key, resnum, 1, qResId);
}

/**
 * >>1-10のようなレス範囲指定を個別にポップアップするコンテナを生成する
 */
function makeRangeResPopUp(asyncObj, fromNum, toNum)
{
	var rangeResPopId = 'rp' + fromNum + 'to' + toNum + 'of' + asyncObj.key;
	if (document.getElementById(rangeResPopId)) {
		return;
	}
	var container = document.getElementById('popUpContainer');
	if (!container) {
		alert("Element 'popUpContainer' not exists.");
		return;
	}

	var rangeResPopUp = document.createElement('div');
	rangeResPopUp.id = rangeResPopId;
	rangeResPopUp.className = 'respopup';
	rangeResPopUp.style.lineHeight = '150%';

	// イベントリスナを設定
	// DOM2
	if (rangeResPopUp.addEventListener) {
		rangeResPopUp.addEventListener('mouseover', showResPopUpListener, false);
		rangeResPopUp.addEventListener('mouseout', hideResPopUpListener, false);
	// old
	} else {
		rangeResPopUp.onmouseover = showResPopUpListener;
		rangeResPopUp.onmouseout = hideResPopUpListener;
	}

	// 各レスをポップアップさせるリンク（+改行）を挿入
	for (var i = fromNum; i <= toNum; i++) {
		rangeResPopUp.appendChild(makeResPopUpElement(asyncObj, i));
		if (i < toNum) {
			rangeResPopUp.appendChild(document.createElement('br'));
		}
	}

	container.appendChild(rangeResPopUp);
}

/**
 * 指定レス番号を非同期ポップアップさせるa要素を作る
 */
function makeResPopUpElement(asyncObj, resnum, inString)
{
	var qResPopId = 'q' + resnum + 'of' + asyncObj.key;
	var url = asyncObj.readPhp + '?host=' + asyncObj.host + '&bbs=' + asyncObj.bbs + '&key=' + asyncObj.key + '&ls=' + resnum + 'n' + '&offline=1';

	var elem = document.createElement('a');
	if (inString) {
		elem.innerHTML = inString;
	} else {
		elem.innerHTML = '&gt;&gt;' + resnum;
	}

	elem.setAttribute('href', url);
	if (asyncObj.readTarget) {
		elem.setAttribute('target', asyncObj.readTarget);
	}

	// ポップアップ表示/非表示する要素が自分自身ではないので
	// 対象要素のIDを埋め込んだ無名関数としてイベントリスナを作成
	var mouseOverListener = function(evt) {
		var evt = (evt) ? evt : ((window.event) ? event : null);
		loadResPopUp(asyncObj, resnum);
		showResPopUp(qResPopId, evt);
	};
	var mouseOutListener = function() {
		hideResPopUp(qResPopId);
	};

	// イベントリスナを設定
	// DOM2
	if (elem.addEventListener) {
		elem.addEventListener('mouseover', mouseOverListener, false);
		elem.addEventListener('mouseout', mouseOutListener, false);
	// old
	} else {
		elem.onmouseover = mouseOverListener;
		elem.onmouseout = mouseOutListener;
	}

	return elem;
}

/**
 * レスポップアップ表示（維持）用イベントリスナ
 */
function showResPopUpListener(evt)
{
	var evt = (evt) ? evt : ((window.event) ? event : null);
	if (evt) {
		var tgt = (evt.currentTarget) ? evt.currentTarget : ((evt.srcElement) ? evt.srcElement : null);
		var tgtId = getResPopUpId(tgt, 0);
		if (tgtId) {
			showResPopUp(tgtId, evt);
		}
	}
}

/**
 * レスポップアップ非表示用イベントリスナ
 */
function hideResPopUpListener(evt)
{
	var evt = (evt) ? evt : ((window.event) ? event : null);
	if (evt) {
		var tgt = (evt.currentTarget) ? evt.currentTarget : ((evt.srcElement) ? evt.srcElement : null);
		if (tgt && tgt.id && tgt.className == 'respopup') {
			hideResPopUp(tgt.id);
		}
	}
}

/**
 * レスポップアップIDを取得する
 *
 * IEで（非同期の）レスポップアップの子要素にカーソルを重ねたときにも
 * レスポップアップが維持されるようにするために必要
 * タグのonmouseover属性とDOMのonmouseroverプロパティで扱いに違いがあるのかな？
 */
function getResPopUpId(tgt, repeat)
{
	var repeat = (typeof repeat == 'number') ? repeat : 0;
	if (repeat > 10) {
		return false;
	}
	if (tgt && tgt.id && tgt.className == 'respopup') {
		return tgt.id;
	} else if (tgt.parentNode) {
		return getResPopUpId(tgt.parentNode, repeat + 1);
	} else {
		return false;
	}
}
