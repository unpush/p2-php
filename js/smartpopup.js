/*
 * rep2expack - レス番号ポップアップメニュー
 */

var SPM = {};
var spmResNum     = -1; // ポップアップで参照するレス番号
var spmBlockID    = ''; // フォント変更で参照するID
var spmSelected   = ''; // 選択文字列を一時的に保存
var spmFlexTarget = ''; // フィルタリング結果を開くウインドウ

/**
 * コールバック関数コンテナ
 */
SPM.callbacks = {};

/**
 * スマートポップアップメニューを生成する
 */
SPM.init = function (aThread) {
	var threadId = aThread.objName;
	if (document.getElementById(threadId + '_spm')) {
		return false;
	}
	var opt = aThread.spmOption;

	// ポップアップメニュー生成
	var spm = document.createElement('div');
	spm.id = threadId + '_spm';
	spm.className = 'spm';
	spm.appendItem = function() {
		this.appendChild(SPM.createMenuItem.apply(this, arguments));
	};
	SPM.setOnPopUp(spm, spm.id, false);

	// コピペ用フォーム
	spm.appendItem('レスコピー', (function () {
		SPM.invite(aThread);
	}));

	// これにレス
	if (opt[1] == 1 || opt[1] == 2) {
		spm.appendItem('これにレス', [aThread, 'post_form.php', 'inyou=' + (2 & opt[1]).toString()]);
		spm.appendItem('引用してレス', [aThread, 'post_form.php', 'inyou=' + ((2 & opt[1]) + 1).toString()]);
	}

	// 逆参照
	spm.appendItem('逆参照', (function (event) {
		SPM.openFilter(aThread, 'rres', 'on', event);
	}));

	// ここまで読んだ
	spm.appendItem('ここまで読んだ', (function () {
		SPM.httpcmd('setreadnum', aThread, SPM.callbacks.setreadnum);
	}));

	// ブックマーク (未実装)

	// あぼーんワード・NGワード
	if (opt[2] == 1 || opt[2] == 2) {
		var abnId = threadId + '_ab';
		var ngId = threadId + '_ng';
		spm.appendItem('あぼーんする', [aThread, 'info_sp.php', 'mode=aborn_res']);
		spm.appendItem('あぼーんワード', null, abnId);
		spm.appendItem('NGワード', null, ngId);
		// サブメニュー生成
		var spmAborn = SPM.createNgAbornSubMenu(abnId, aThread, 'aborn');
		var spmNg = SPM.createNgAbornSubMenu(ngId, aThread, 'ng');
	} else {
		var spmAborn = false, spmNg = false;
	}

	// フィルタリング
	if (opt[3] == 1) {
		var filterId = threadId + '_fl';
		spm.appendItem('フィルタリング', null, filterId);
		// サブメニュー生成
		var spmFilter = SPM.createFilterSubMenu(filterId, aThread);
	} else {
		var SpmFilter = false;
	}

	// アクティブモナー
	if (opt[4] == 1) {
		spm.appendItem('AA用フォント', (function () {
			activeMona(SPM.getBlockID());
		}));
	}

	// AAS
	if (opt[5] == 1) {
		spm.appendItem('AAS', [aThread, 'aas.php']);
	}

	// PRE
	/*spm.appendItem('PRE', (function () {
		var msg = document.getElementById(SPM.getBlockID());
		if (msg.style.whiteSpace == 'pre') {
			msg.style.whiteSpace = 'normal';
		} else {
			msg.style.whiteSpace = 'pre';
		}
	}));*/

	// ポップアップ・コンテナを取得 or 作成
	var container = document.getElementById('popUpContainer');
	if (!container) {
		container = document.createElement('div');
		container.id = 'popUpContainer';
		container.style.position = 'absolute';
		document.body.insertBefore(container, document.body.firstChild);
	}

	// ポップアップメニューをコンテナに追加
	container.appendChild(spm);

	// あぼーんワード・サブメニューをコンテナに追加
	if (spmAborn) {
		container.appendChild(spmAborn);
	}
	// NGワード・サブメニューをコンテナに追加
	if (spmNg) {
		container.appendChild(spmNg);
	}
	// フィルタリング・サブメニューをコンテナに追加
	if (spmFilter) {
		container.appendChild(spmFilter);
	}

	// 表示・非表示メソッドを設定
	aThread.show = (function(resnum, resid, event){
		SPM.show(aThread, resnum, resid, event);
	});
	aThread.hide = (function(event){
		SPM.hide(aThread, event);
	});

	return false;
};

/**
 * スマートポップアップメニューをポップアップ表示する
 */
SPM.show = function (aThread, resnum, resid, event) {
	event = event || window.event;
	if (spmResNum != resnum || spmBlockID != resid) {
		SPM.hideImmediately(aThread, event);
	}
	spmResNum  = resnum;
	spmBlockID = resid;
	if (window.getSelection) {
		spmSelected = window.getSelection();
	} else if (document.selection) {
		spmSelected = document.selection.createRange().text;
	}
	showResPopUp(aThread.objName + '_spm' ,event);
	return false;
};

/**
 * スマートポップアップメニューを閉じる
 */
SPM.hide = function (aThread, event) {
	event = event || window.event;
	hideResPopUp(aThread.objName + '_spm');
	return false;
};

/**
 * スマートポップアップメニューを遅延ゼロで閉じる
 */
SPM.hideImmediately = function (aThread, event) {
	event = event || window.event;
	document.getElementById(aThread.objName + '_spm').style.visibility = 'hidden';
	return false;
};

/**
 * クロージャからグローバル変数 spmBlockID を取得するための関数
 */
SPM.getBlockID = function() {
	return spmBlockID;
};

/**
 * クリック時に実行される関数 (ポップアップウインドウを開く) を設定する
 */
SPM.setOnClick = function (obj, aThread, inUrl) {
	var option = (arguments.length > 3) ? arguments[3] : '';
	obj.onclick = function (event) {
		event = event || window.event;
		if (event) {
			return SPM.openSubWin(aThread, inUrl, option);
		}
		return false;
	};
};

/**
 * マウスオーバー/アウト時に実行される関数 (メニューの表示/非表示) を設定する
 */
SPM.setOnPopUp = function (obj, targetId, isSubMenu) {
	// ロールオーバー
	obj.onmouseover = function (event) {
		event = event || window.event;
		if (event) {
			showResPopUp(targetId, event);
		}
	};
	// ロールアウト
	obj.onmouseout = function (event) {
		event = event || window.event;
		if (event) {
			hideResPopUp(targetId);
		}
	}
};

/**
 * アンカーを生成する
 */
SPM.createMenuItem = function (txt) {
	var anchor = document.createElement('a');
	anchor.href = 'javascript:void(null)';
	anchor.onclick = function() {
		return false;
	};
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
};

/**
 * あぼーん/NGサブメニューを生成する
 */
SPM.createNgAbornSubMenu = function (menuId, aThread, mode) {
	var amenu = document.createElement('div');
	amenu.id = menuId;
	amenu.className = 'spm';
	amenu.appendItem = function () {
		this.appendChild(SPM.createMenuItem.apply(this, arguments));
	};
	SPM.setOnPopUp(amenu, amenu.id, true);

	amenu.appendItem('名前', [aThread, 'info_sp.php', 'mode=' + mode + '_name']);
	amenu.appendItem('メール', [aThread, 'info_sp.php', 'mode=' + mode + '_mail']);
	amenu.appendItem('本文', [aThread, 'info_sp.php', 'mode=' + mode + '_msg']);
	amenu.appendItem('ID', [aThread, 'info_sp.php', 'mode=' + mode + '_id']);

	return amenu;
};

/**
 * フィルタリングサブメニューを生成する
 */
SPM.createFilterSubMenu = function (menuId, aThread) {
	this.getOnClick = function (field, match) {
		return (function (event) {
			SPM.openFilter(aThread, field, match, event);
		});
	}

	var fmenu = document.createElement('div');
	fmenu.id = menuId;
	fmenu.className = 'spm';
	fmenu.appendItem = function() {
		this.appendChild(SPM.createMenuItem.apply(this, arguments));
	};
	SPM.setOnPopUp(fmenu, fmenu.id, true);

	fmenu.appendItem('同じ名前', this.getOnClick('name', 'on'));
	fmenu.appendItem('同じメール', this.getOnClick('mail', 'on'));
	fmenu.appendItem('同じ日付', this.getOnClick('date', 'on'));
	fmenu.appendItem('同じID', this.getOnClick('id', 'on'));
	fmenu.appendItem('異なる名前', this.getOnClick('name', 'off'));
	fmenu.appendItem('異なるメール', this.getOnClick('mail', 'off'));
	fmenu.appendItem('異なる日付', this.getOnClick('date', 'off'));
	fmenu.appendItem('異なるID', this.getOnClick('id', 'off'));

	return fmenu;
};

/* ==================== 覚え書き ====================
 * <a href="javascript:void(0);" onclick="foo()">は
 * <a href="javascript:void(foo());">と等価に働く。
 * JavaScriptでURIを生成するとき、&を&amp;としてはいけない。
 * ================================================== */

/**
 * URIの処理をし、ポップアップウインドウを開く
 */
SPM.openSubWin = function (aThread, inUrl, option) {
	var inWidth  = 650; // ポップアップウインドウの幅
	var inHeight = 350; // ポップアップウインドウの高さ
	var boolS = 1; // スクロールバーを表示（off:0, on:1）
	var boolR = 0; // 自動リサイズ（off:0, on:1）
	var popup = 1; // ポップアップウインドウか否か（no:0, yes:1, yes&タイマーで閉じる:2）
	if (inUrl == 'info_sp.php') {
		inWidth  = 480;
		inHeight = 240;
		boolS = 0;
		if (aThread.spmOption[2] == 1) {
			popup = 2; // あぼーん/NGワード登録の確認をしないとき
		}
		if (option.indexOf('_msg') != -1 && spmSelected != '') {
			option += '&selected_string=' + encodeURIComponent(spmSelected);
		}
	} else if (inUrl == 'post_form.php') {
		if (aThread.spmOption[1] == 2) {
			// inHeight = 450;
		}
		if (location.href.indexOf('/read_new.php?') != -1) {
			if (option == '') {
				option = 'from_read_new=1';
			} else {
				option += '&from_read_new=1';
			}
		}
	} else if (inUrl == 'tentori.php') {
		inWidth  = 450;
		inHeight = 150;
		popup = 2;
	} else if (inUrl == 'aas.php') {
		inWidth  = (aas_popup_width) ? aas_popup_width : 250;
		inHeight = (aas_popup_height) ? aas_popup_height : 330;
	}
	inUrl += '?host=' + aThread.host + '&bbs=' + aThread.bbs + '&key=' + aThread.key.toString();
	inUrl += '&rescount=' + aThread.rc.toString() + '&ttitle_en=' + aThread.ttitle_en;
	inUrl += '&resnum=' + spmResNum.toString() + '&popup=' + popup.toString();
	if (option != '') {
		inUrl += '&' + option;
	}
	OpenSubWin(inUrl, inWidth, inHeight, boolS, boolR);
	return true;
};

/**
 * URIの処理をし、フィルタリング結果を表示する
 */
SPM.openFilter = function (aThread, field, match, event) {
	var target;
	var inUrl = 'read_filter.php?bbs=' + aThread.bbs + '&key=' + aThread.key + '&host=' + aThread.host;
	inUrl += '&rescount=' + aThread.rc + '&ttitle_en=' + aThread.ttitle_en + '&resnum=' + spmResNum;
	inUrl += '&ls=all&field=' + field + '&method=just&match=' + match + '&offline=1';

	event = event || window.event;
	if (event.shiftKey) {
		target = '_blank';
	} else {
		target = spmFlexTarget;
	}

	switch (target) {
		case '_popup':
			showHtmlPopUp(inUrl, event, 0);
			SPM.hideImmediately(aThread, event);
			break;
		case '_blank':
			window.open(inUrl, '', '');
			break;
		case '_self':
			window.self.location.href = inUrl;
			break;
		case '_parent':
			window.parent.location.href = inUrl;
			break;
		case '_top':
			window.top.location.href = inUrl;
			break;
		default:
			if (window.parent != window.self &&
				typeof window.parent[target] !== 'undefined' &&
				typeof window.parent[target].location !== 'undefined' &&
				typeof window.parent[target].location.href !== 'undefined')
			{
				window.parent[target].location.href = inUrl;
			} else {
				window.open(inUrl, target, '')
			}
	}

	return true;
};

/**
 * コピペ用にスレ情報をポップアップする (for SPM)
 */
SPM.invite = function (aThread) {
	Invite(aThread.title, aThread.url, aThread.host, aThread.bbs, aThread.key, spmResNum);
};

/**
 * httpcmd.phpのラッパー
 */
SPM.httpcmd = function (cmd, aThread, callback) {
	var num = spmResNum;
	var url = 'httpcmd.php?host=' + aThread.host + '&bbs=' + aThread.bbs + '&key=' + aThread.key
	        + '&cmd=' + cmd + '&' + cmd + '=' + num;
	var result = getResponseTextHttp(getXmlHttp(), url, true);
	if (typeof callback === 'function') {
		callback(result, cmd, aThread, num, url);
	}
};

/**
 * 「ここまで読んだ」リクエスト後に実行するコールバック関数
 */
SPM.callbacks.setreadnum = function (result, cmd, aThread, num, url) {
	var msg = 'スレッド“' + aThread.title + '”の既読数を';
	if (result == '1') {
		msg += ' ' + num + ' にセットしました。';
	} else {
		msg += 'セットできませんでした。';
	}
	window.alert(msg);
};

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
