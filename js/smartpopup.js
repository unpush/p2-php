/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

/* p2 - スマートポップアップメニューJavaScriptファイル */

var spmResNum     = new Number(); // ポップアップで参照するレス番号
var spmBlockID    = new String(); // フォント変更で参照するID
var spmSelected   = new String(); // 選択文字列を一時的に保存
var spmFlexTarget = new String(); // フィルタリング結果を開くウインドウ

// makeSPM -- スマートポップアップメニューを生成する
function makeSPM(aThread)
{
	var thread_id = aThread.objName;
	var a_tag  = "<a href=\"javascript:void(spmOpenSubWin(" + thread_id + ",";

	// ポップアップメニューを生成

	document.writeln("<div id=\"" + thread_id + "_spm\" class=\"spm\"" + makeOnPopUp(thread_id+"_spm", false) + ">");
	// コピペ用フォーム
	document.writeln("<a href=\"javascript:void(spmInvite(" + thread_id + "));\">このレスをコピペ</a>");
	// これにレス
	if (aThread.spmOption[1] == 1) {
		document.writeln(a_tag + "'post_form.php',''));\">これにレス</a>");
		document.writeln(a_tag + "'post_form.php','inyou=1'));\">引用してレス</a>");
	} else if (aThread.spmOption[1] == 2) {
		document.writeln(a_tag + "'post_form.php','inyou=2'));\">これにレス</a>");
		document.writeln(a_tag + "'post_form.php','inyou=3'));\">引用してレス</a>");
	}
	// あぼーんワード・NGワード
	if (aThread.spmOption[2] == 1 || aThread.spmOption[2] == 2) {
		//document.writeln(a_tag + "'info_sp.php','mode=aborn_res'));\">あぼーんする</a>");
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_ab", true) + ">あぼーんワード</a>");
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_ng", true) + ">NGワード</a>");
	}
	// フィルタリング
	if (aThread.spmOption[3] == 1) {
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_fl", true) + ">フィルタリング</a>");
	}
	// アクティブモナー
	if (aThread.spmOption[4] == 1) {
		document.writeln("<a href=\"javascript:void(activeMona(spmBlockID));\">AAフォント表示</a>");
	}
	// AAS
	if (aThread.spmOption[5] == 1) {
		document.writeln(a_tag + "'aas.php',''));\">AAS表示</a>");
	}
	// ブロックを閉じる
	document.writeln("</div>");

	// /サブメニューを生成

	// あぼーんワード・NGワード・サブメニュー
	if (aThread.spmOption[2] == 1 || aThread.spmOption[2] == 2) {
		makeAbornSPM(thread_id+"_ab", a_tag, "aborn");
		makeAbornSPM(thread_id+"_ng", a_tag, "ng");
	}
	// フィルタリング・サブメニュー
	if (aThread.spmOption[3] == 1) {
		makeFilterSPM(thread_id+"_fl", thread_id);
	}

	return false;
}


// makeOnPopUp -- マウスオーバー/アウト時に実行されるスクリプトを生成する
function makeOnPopUp(popup_id, isSubMenu)
{
	// 遅延時間
	var spmPopUpDelay = "delaySec=(0.3*1000);";
	if (isSubMenu) {
		spmPopUpDelay = "delaySec=0;";
	}
	// ロールオーバー
	var spmPopUpEvent  = " onmouseover=\"" + spmPopUpDelay + "showResPopUp('" + popup_id + "',event);\"";
	// ロールアウト
		spmPopUpEvent += " onmouseout=\""  + spmPopUpDelay + "hideResPopUp('" + popup_id + "');\"";
	return spmPopUpEvent;
}


// makeAbornSPM -- あぼーん/NGサブメニューを生成する
function makeAbornSPM(menu_id, a_tag, submenu_mode)
{
	document.writeln("<div id=\"" + menu_id + "\" class=\"spm\"" + makeOnPopUp(menu_id, true) + ">");
	document.writeln(a_tag + "'info_sp.php','mode=" + submenu_mode + "_name'));\">名前</a>");
	document.writeln(a_tag + "'info_sp.php','mode=" + submenu_mode + "_mail'));\">メール</a>");
	document.writeln(a_tag + "'info_sp.php','mode=" + submenu_mode + "_msg'));\">本文</a>");
	document.writeln(a_tag + "'info_sp.php','mode=" + submenu_mode + "_id'));\">ID</a>");
	document.writeln("</div>");
}


// makeFilterSPM -- フィルタリングサブメニューを生成する
function makeFilterSPM(menu_id, thread_id)
{
	var filter_anchor = "<a href=\"javascript:void(spmOpenFilter(" + thread_id;
	document.writeln("<div id=\"" + menu_id + "\" class=\"spm\"" + makeOnPopUp(menu_id, true) + ">");
	document.writeln(filter_anchor + ",'name','on'));\">同じ名前</a>");
	document.writeln(filter_anchor + ",'mail','on'));\">同じメール</a>");
	document.writeln(filter_anchor + ",'date','on'));\">同じ日付</a>");
	document.writeln(filter_anchor + ",'id','on'));\">同じID</a>");
	document.writeln(filter_anchor + ",'name','off'));\">異なる名前</a>");
	document.writeln(filter_anchor + ",'mail','off'));\">異なるメール</a>");
	document.writeln(filter_anchor + ",'date','off'));\">異なる日付</a>");
	document.writeln(filter_anchor + ",'id','off'));\">異なるID</a>");
	document.writeln("</div>");
}


// showSPM -- スマートポップアップメニューをポップアップ表示する
function showSPM(aThread, resnum, resid, evt)
{
	var evt = (evt) ? evt : ((window.event) ? event : null);
	if (spmResNum != resnum || spmBlockID != resid) {
		closeSPM(aThread);
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


// makeSPM -- スマートポップアップメニューを遅延ゼロで閉じる
function closeSPM(aThread)
{
	document.getElementById(aThread.objName + "_spm").style.visibility = "hidden";
	return false;
}


/* ==================== 覚え書き ====================
 * <a href="javascript:void(0);" onclick="foo()">は
 * <a href="javascript:void(foo());">と等価。
 * JavaScriptでURIを生成するとき、&を&amp;としてはいけない。
 * ================================================== */


// spmOpenSubWin -- URIの処理をし、ポップアップウインドウを開く
function spmOpenSubWin(aThread, inUrl, option)
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
	inUrl += "&rc=" + aThread.rc + "&ttitle_en=" + aThread.ttitle_en;
	inUrl += "&resnum=" + spmResNum + "&popup=" + popup;
	if (option != "") {
		inUrl += "&" + option;
	}
	OpenSubWin(inUrl, inWidth, inHeight, boolS, boolR);
	return true;
}


// spmOpenFilter -- URIの処理をし、フィルタリング結果を表示する
function spmOpenFilter(aThread, field, match)
{
	var inUrl = "read_filter.php?bbs=" + aThread.bbs + "&key=" + aThread.key + "&host=" + aThread.host;
	inUrl += "&rc=" + aThread.rc + "&ttitle_en=" + aThread.ttitle_en + "&resnum=" + spmResNum;
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


// spmInvite -- コピペ用にスレ情報をポップアップする (for SPM)
function spmInvite(aThread)
{
	Invite(aThread.title, aThread.url, aThread.host, aThread.bbs, aThread.key, spmResNum);
}
