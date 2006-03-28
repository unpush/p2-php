/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

/* rep2expack - スマートポップアップメニュー  */

var SPM = new Object();
var spmResNum     = new Number(); // ポップアップで参照するレス番号
var spmBlockID    = new String(); // フォント変更で参照するID
var spmSelected   = new String(); // 選択文字列を一時的に保存
var spmFlexTarget = new String(); // フィルタリング結果を開くウインドウ

/**
 * スマートポップアップメニューを生成する
 */
SPM.init = function(aThread)
{
	var threadId = aThread.objName;
	if (document.getElementById(threadId + '_spm')) {
		return false;
	}
	var opt = aThread.spmOption;

	// ポップアップメニュー生成
	var spm = document.createElement('div');
	spm.id = threadId + '_spm';
	spm.className = 'spm';
	SPM.setOnPopUp(spm, spm.id, false);

	// コピペ用フォーム
	spm.appendChild(SPM.createMenuItem('レスコピー', (function(){SPM.invite(aThread)})));

	// これにレス
	if (opt[1] == 1 || opt[1] == 2) {
		spm.appendChild(SPM.createMenuItem('これにレス', [aThread, 'post_form.php', 'inyou=' + (2 & opt[1]).toString()]));
		spm.appendChild(SPM.createMenuItem('引用してレス', [aThread, 'post_form.php', 'inyou=' + ((2 & opt[1]) + 1).toString()]));
	}

	// あぼーんワード・NGワード
	if (opt[2] == 1 || opt[2] == 2) {
		var abnId = threadId + '_ab';
		var ngId = threadId + '_ng';
		spm.appendChild(SPM.createMenuItem('あぼーんする', [aThread, 'info_sp.php', 'mode=aborn_res']));
		spm.appendChild(SPM.createMenuItem('あぼーんワード', null, abnId));
		spm.appendChild(SPM.createMenuItem('NGワード', null, ngId));
		// サブメニュー生成
		var spmAborn = SPM.createNgAbornSubMenu(abnId, aThread, 'aborn');
		var spmNg = SPM.createNgAbornSubMenu(ngId, aThread, 'ng');
	} else {
		var spmAborn = false, spmNg = false;
	}

	// フィルタリング
	if (opt[3] == 1) {
		var filterId = threadId + '_fl';
		spm.appendChild(SPM.createMenuItem('フィルタリング', null, filterId));
		// サブメニュー生成
		var spmFilter = SPM.createFilterSubMenu(filterId, aThread);
	} else {
		var SpmFilter = false;
	}

	// アクティブモナー
	if (opt[4] == 1) {
		spm.appendChild(SPM.createMenuItem('AA用フォント', (function(){activeMona(SPM.getBlockID())})));
	}

	// AAS
	if (opt[5] == 1) {
		spm.appendChild(SPM.createMenuItem('AAS', [aThread, 'aas.php']));
	}

	// ポップアップメニューをコンテンツに追加
	document.body.appendChild(spm);

	// あぼーんワード・サブメニューをコンテンツに追加
	if (spmAborn) {
		document.body.appendChild(spmAborn);
	}
	// NGワード・サブメニューをコンテンツに追加
	if (spmNg) {
		document.body.appendChild(spmNg);
	}
	// フィルタリング・サブメニューをコンテンツに追加
	if (spmFilter) {
		document.body.appendChild(spmFilter);
	}

	return false;
}

/**
 * スマートポップアップメニューをポップアップ表示する
 */
SPM.show = function(aThread, resnum, resid, evt)
{
	var evt = (evt) ? evt : ((window.event) ? event : null);
	if (spmResNum != resnum || spmBlockID != resid) {
		SPM.hide(aThread);
	}
	spmResNum  = resnum;
	spmBlockID = resid;
	if (window.getSelection) {
		spmSelected = window.getSelection();
	} else if (document.selection) {
		spmSelected = document.selection.createRange().text;
	}
	showResPopUp(aThread.objName + "_spm" ,evt);
	return false;
}

/**
 * スマートポップアップメニューを遅延ゼロで閉じる
 */
SPM.hide = function(aThread)
{
	document.getElementById(aThread.objName + "_spm").style.visibility = "hidden";
	return false;
}

/**
 * クロージャからグローバル変数 spmBlockID を取得するための関数
 */
SPM.getBlockID = function()
{
	return spmBlockID;
}

/**
 * クリック時に実行される関数 (ポップアップウインドウを開く) を設定する
 */
SPM.setOnClick = function(obj, aThread, inUrl)
{
	var option = (arguments.length > 3) ? arguments[3] : '';
	obj.onclick = function(evt)
	{
		evt = (evt) ? evt : ((window.event) ? window.event : null);
		if (evt) {
			return SPM.openSubWin(aThread, inUrl, option);
		}
		return false;
	}
}

/**
 * マウスオーバー/アウト時に実行される関数 (メニューの表示/非表示) を設定する
 */
SPM.setOnPopUp = function(obj, targetId, isSubMenu)
{
	// ロールオーバー
	obj.onmouseover = function(evt)
	{
		evt = (evt) ? evt : ((window.event) ? window.event : null);
		if (evt) {
			showResPopUp(targetId, evt);
		}
	}
	// ロールアウト
	obj.onmouseout = function(evt)
	{
		evt = (evt) ? evt : ((window.event) ? window.event : null);
		if (evt) {
			hideResPopUp(targetId);
		}
	}
}

/**
 * アンカーを生成する
 */
SPM.createMenuItem = function(txt)
{
	var anchor = document.createElement('a');
	anchor.href = 'javascript:void(null)';
	anchor.onclick = function() { return false; }
	anchor.appendChild(document.createTextNode(txt));

	// クリックされたときのイベントハンドラを設定
	if (arguments.length > 1 && arguments[1] != null) {
		if (typeof arguments[1] === 'function') {
			anchor.onclick = arguments[1];
		} else {
			var aThread = arguments[1][0];
			var inUrl = arguments[1][1];
			var option = (arguments[1].length > 2) ? arguments[1][2] : '';
			SPM.setOnClick(anchor, aThread, inUrl, option);
		}
	}

	// サブメニューをポップアップするイベントハンドラを設定
	if (arguments.length > 2 && arguments[2] != null) {
		SPM.setOnPopUp(anchor, arguments[2], true);
	}

	return anchor;
}

/**
 * あぼーん/NGサブメニューを生成する
 */
SPM.createNgAbornSubMenu = function(menuId, aThread, mode)
{
	var amenu = document.createElement('div');
	amenu.id = menuId;
	amenu.className = 'spm';
	SPM.setOnPopUp(amenu, amenu.id, true);

	amenu.appendChild(SPM.createMenuItem('名前', [aThread, 'info_sp.php', 'mode=' + mode + '_name']));
	amenu.appendChild(SPM.createMenuItem('メール', [aThread, 'info_sp.php', 'mode=' + mode + '_mail']));
	amenu.appendChild(SPM.createMenuItem('本文', [aThread, 'info_sp.php', 'mode=' + mode + '_msg']));
	amenu.appendChild(SPM.createMenuItem('ID', [aThread, 'info_sp.php', 'mode=' + mode + '_id']));

	return amenu;
}

/**
 * フィルタリングサブメニューを生成する
 */
SPM.createFilterSubMenu = function(menuId, aThread)
{
	this.getOnClick = function(field, match)
	{
		return (function(evt){
			evt = (evt) ? evt : ((window.event) ? window.event : null);
			if (evt) { SPM.openFilter(aThread, field, match); }
		});
	}

	var fmenu = document.createElement('div');
	fmenu.id = menuId;
	fmenu.className = 'spm';
	SPM.setOnPopUp(fmenu, fmenu.id, true);

	fmenu.appendChild(SPM.createMenuItem('同じ名前', this.getOnClick('name', 'on')));
	fmenu.appendChild(SPM.createMenuItem('同じメール', this.getOnClick('mail', 'on')));
	fmenu.appendChild(SPM.createMenuItem('同じ日付', this.getOnClick('date', 'on')));
	fmenu.appendChild(SPM.createMenuItem('同じID', this.getOnClick('id', 'on')));
	fmenu.appendChild(SPM.createMenuItem('異なる名前', this.getOnClick('name', 'off')));
	fmenu.appendChild(SPM.createMenuItem('異なるメール', this.getOnClick('mail', 'off')));
	fmenu.appendChild(SPM.createMenuItem('異なる日付', this.getOnClick('date', 'off')));
	fmenu.appendChild(SPM.createMenuItem('異なるID', this.getOnClick('id', 'off')));

	return fmenu;
}

/* ==================== 覚え書き ====================
 * <a href="javascript:void(0);" onclick="foo()">は
 * <a href="javascript:void(foo());">と等価に働く。
 * JavaScriptでURIを生成するとき、&を&amp;としてはいけない。
 * ================================================== */

/**
 * URIの処理をし、ポップアップウインドウを開く
 */
SPM.openSubWin = function(aThread, inUrl, option)
{
	var inWidth  = 650; // ポップアップウインドウの幅
	var inHeight = 350; // ポップアップウインドウの高さ
	var boolS = 1; // スクロールバーを表示（off:0, on:1）
	var boolR = 0; // 自動リサイズ（off:0, on:1）
	var popup = 1; // ポップアップウインドウか否か（no:0, yes:1, yes&タイマーで閉じる:2）
	if (inUrl == "info_sp.php") {
		inWidth  = 480;
		inHeight = 240;
		boolS = 0;
		if (aThread.spmOption[2] == 1) {
			popup = 2; // あぼーん/NGワード登録の確認をしないとき
		}
		if (option.indexOf("_msg") != -1 && spmSelected != '') {
			option += "&selected_string=" + encodeURIComponent(spmSelected);
		}
	} else if (inUrl == "post_form.php") {
		if (aThread.spmOption[1] == 2) {
			// inHeight = 450;
		}
		if (location.href.indexOf("/read_new.php?") != -1) {
			if (option == "") {
				option = "from_read_new=1";
			} else {
				option += "&from_read_new=1";
			}
		}
	} else if (inUrl == "tentori.php") {
		inWidth  = 450;
		inHeight = 150;
		popup = 2;
	} else if (inUrl == "aas.php") {
		inWidth  = (aas_popup_width) ? aas_popup_width : 250;
		inHeight = (aas_popup_height) ? aas_popup_height : 330;
	}
	inUrl += "?host=" + aThread.host + "&bbs=" + aThread.bbs + "&key=" + aThread.key;
	inUrl += "&rescount=" + aThread.rc + "&ttitle_en=" + aThread.ttitle_en;
	inUrl += "&resnum=" + spmResNum + "&popup=" + popup;
	if (option != "") {
		inUrl += "&" + option;
	}
	OpenSubWin(inUrl, inWidth, inHeight, boolS, boolR);
	return true;
}

/**
 * URIの処理をし、フィルタリング結果を表示する
 */
SPM.openFilter = function(aThread, field, match)
{
	var inUrl = "read_filter.php?bbs=" + aThread.bbs + "&key=" + aThread.key + "&host=" + aThread.host;
	inUrl += "&rescount=" + aThread.rc + "&ttitle_en=" + aThread.ttitle_en + "&resnum=" + spmResNum;
	inUrl += "&ls=all&field=" + field + "&method=just&match=" + match + "&offline=1";

	switch (spmFlexTarget) {
		case "_self":
			location.href = inUrl;
			break;
		case "_parent":
			parent.location.href = inUrl;
			break;
		case "_top":
			top.location.href = inUrl;
			break;
		case "_blank":
			window.open(inUrl, "", "");
			break;
		default:
			if (parent.spmFlexTarget.location.href) {
				parent.spmFlexTarget.location.href = inUrl;
			} else {
				window.open(inUrl, spmFlexTarget, "")
			}
	}

	return true;
}

/**
 * コピペ用にスレ情報をポップアップする (for SPM)
 */
SPM.invite = function(aThread)
{
	Invite(aThread.title, aThread.url, aThread.host, aThread.bbs, aThread.key, spmResNum);
}

// 後方互換のため、一応
makeSPM = SPM.init;
showSPM = SPM.show;
closeSPM = SPM.hide;
