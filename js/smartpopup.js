/* p2 - スマートポップアップメニューJavaScriptファイル */

/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

var spmResNum     = new Number(); // ポップアップで参照するレス番号
var spmBlockID    = new String(); // フォント変更で参照するID
var spmSelected   = new String(); // 選択文字列を一時的に保存
var spmFlexTarget = new String(); // フィルタリング結果を開くウインドウ

// makeSPM -- スマートポップアップメニューを生成する
function makeSPM(aThread, isClickOnOff)
{
	var thread_id = aThread.objName;
	var a_tag  = "<a href=\"javascript:void(spmOpenSubWin(" + thread_id + ",";
	var numbox = "";
	if (document.getElementById || document.all) {
		numbox = "<span id=\"" + thread_id + "_numbox\"></span>";
	}

	// ポップアップメニューを生成

	document.writeln("<div id=\"" + thread_id + "_spm\" class=\"spm\"" + makeOnPopUp(thread_id+"_spm", false) + ">");
	// ヘッダ
	if (aThread.spmHeader != "") {
		if (isClickOnOff) {
			document.writeln("<a href=\"javascript:void(0);\" onclick=\"closeSPM(" + thread_id + ");\" class=\"closebox\">×</a>");
		}
		document.write("<p>" + aThread.spmHeader.replace("resnum", numbox) + "</p>");
	}
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
	// しおり
	if (aThread.spmOption[2] == 1) {
		document.writeln(a_tag + "'info_sp.php','mode=readhere'));\">ここまで読んだ</a>");
	}
	// あぼーんワード
	if (aThread.spmOption[3] == 1) {
		document.writeln(a_tag + "'info_sp.php','mode=aborn_res'));\">あぼーんする</a>");
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_ab", true) + ">あぼーんワード</a>");
	}
	// NGワード
	if (aThread.spmOption[4] == 1) {
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_ng", true) + ">NGワード</a>");
	}
	// アクティブモナー
	if (aThread.spmOption[5] == 1) {
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_ds", true) + ">フォント設定</a>");
	} else if (aThread.spmOption[5] == 2) {
		makeDynamicStyleSPM(thread_id+"_ds", false);
	}
	// フィルタリング
	if (aThread.spmOption[6] == 1) {
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_fl", true) + ">フィルタリング</a>");
	}
	// 点取り占い
	if (aThread.spmOption[7] == 1) {
		document.writeln(a_tag + "'tentori.php',''));\">おみくじを引く</a>");
	}
	// クローズメニュー
	if (aThread.spmHeader == "" && isClickOnOff) {
		document.writeln("<a href=\"javascript:void(0);\" onclick=\"closeSPM(" + thread_id + ");\" class=\"closemenu\">閉じる</a>");
	}
	// ブロックを閉じる
	document.writeln("</div>");

	// /サブメニューを生成

	// あぼーんワード・サブメニュー
	if (aThread.spmOption[3] == 1) {
		makeAbornSPM(thread_id+"_ab", a_tag);
	}
	// NGワード・サブメニュー
	if (aThread.spmOption[4] == 1) {
		makeAbornSPM(thread_id+"_ng", a_tag);
	}
	// フォント設定・サブメニュー
	if (aThread.spmOption[5] == 1) {
		makeDynamicStyleSPM(thread_id+"_ds", true);
	}
	// フィルタリング・サブメニュー
	if (aThread.spmOption[6] == 1) {
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
function makeAbornSPM(menu_id, a_tag)
{
	var mode = "aborn";
	if (menu_id.substr(menu_id.lastIndexOf("_"), 3) == "_ng") {
		mode = "ng";
	}
	document.writeln("<div id=\"" + menu_id + "\" class=\"spm\"" + makeOnPopUp(menu_id, true) + ">");
	document.writeln(a_tag + "'info_sp.php','mode=" + mode + "_name'));\">名前</a>");
	document.writeln(a_tag + "'info_sp.php','mode=" + mode + "_mail'));\">メール</a>");
	document.writeln(a_tag + "'info_sp.php','mode=" + mode + "_msg'));\">本文</a>");
	document.writeln(a_tag + "'info_sp.php','mode=" + mode + "_id'));\">ID</a>");
	document.writeln("</div>");
}


// makeDynamicStyleSPM -- フォント設定サブメニューを生成する
function makeDynamicStyleSPM(menu_id, isSubMenu)
{
	var spmActiveMona  = "<div class=\"spmMona\">　（";
		spmActiveMona += "<a href=\"javascript:void(spmDynamicStyle('12px'));\">´</a>";
		spmActiveMona += "<a href=\"javascript:void(spmDynamicStyle('14px'));\">∀</a>";
		spmActiveMona += "<a href=\"javascript:void(spmDynamicStyle('16px'));\">｀</a>";
		spmActiveMona += "）</div>";
	if (isSubMenu) {
		document.writeln("<div id=\"" + menu_id + "\" class=\"spm\"" + makeOnPopUp(menu_id, true) + ">");
		document.writeln(spmActiveMona);
		document.writeln("<a href=\"javascript:void(spmDynamicStyle('normal'));\">標準フォント</a>");
		document.writeln("<a href=\"javascript:void(spmDynamicStyle('monospace'));\">等幅フォント</a>");
		document.writeln("<a href=\"javascript:void(spmDynamicStyle('larger'));\">大きく</a>");
		document.writeln("<a href=\"javascript:void(spmDynamicStyle('smaller'));\">小さく</a>");
		document.writeln("<a href=\"javascript:void(spmDynamicStyle('rewind'));\">元に戻す</a>");
		document.writeln("</div>");
	} else {
		document.writeln(spmActiveMona);
	}
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
	spmResNum  = resnum;
	spmBlockID = resid;
	if (window.getSelection) {
		spmSelected = window.getSelection();
	} else if (document.selection) {
		spmSelected = document.selection.createRange().text;
	}
	var numbox;
	if (numbox = document.getElementById(aThread.objName + "_numbox")) {
		numbox.innerHTML = resnum;
	}
	closeSPM(aThread);
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
		if (aThread.spmOption[0] == 0) {
			popup = 2; // しおり,あぼーん/NGワード登録の確認をしないとき
		}
		if (option.indexOf("_msg") != -1 && spmSelected != '') {
			option += "&selected_string=" + encodeURIComponent(spmSelected);
		}
	} else if (inUrl == "post_form.php") {
		if (aThread.spmOption[1] == 2) {
			// inHeight = 450;
		}
		if (read_new > 0) {
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

// spmDynamicStyle -- 対象オブジェクトを設定し、書式を変える
function spmDynamicStyle(mode)
{
	var dsTarget     = document.getElementById(spmBlockID);
	var dsFontSize   = dsTarget.style.fontSize;
	var isAutoMona   = false;
	if (dsTarget.className == "AutoMona") {
		isAutoMona   = true;
	}
	var isPopUp      = false;
	if (spmBlockID.charAt(0) == "q") {
		isPopUp      = true;
	}
	// 再設定
	if (dsFontSize.length   < 1) {
		if (isAutoMona) {
			dsFontSize = "14px";
		} else if (isPopUp) {
			dsFontSize = am_respop_fontSize;
		} else {
			dsFontSize = am_read_fontSize;
		}
	}
	// 分岐
	switch (mode) {
		// アクティブモナー
		case "16px":
		case "14px":
		case "12px":
			activeMona(spmBlockID, mode);
			return true;
		// 元のフォントサイズに戻す
		case "rewind":
			if (isQuoteBlock) {
				dsTarget.style.fontSize = am_respop_fontSize;
			} else {
				dsTarget.style.fontSize = am_read_fontSize;
			}
			// 引き続き標準フォントにする
		// 標準フォントにする
		case "normal":
			if (spmBlockID.charAt(0) == "q") {
				dsTarget.className = "NoMonaQ";
			} else {
				dsTarget.className = "NoMona";
			}
			return true;
		// 等幅フォントにする
		case "monospace":
			dsTarget.className = "spmMonoSpace";
			return true;
		// フォントサイズを変える
		case "larger":
		case "smaller":
			var newFontSize  = new Number;
			var curFontSize  = new Number(dsFontSize.match(/^\d+/));
			var FontSizeUnit = new String(dsFontSize.match(/\D+$/));
			if (mode == "larger") {
				newFontSize = curFontSize * 1.25;
			} else {
				newFontSize = curFontSize * 0.8;
			}
			dsTarget.style.fontSize   = newFontSize.toString() + FontSizeUnit;
			return true;
		// ...
		default:
			return false;
	}
}

// spmInvite -- コピペ用にスレ情報をポップアップする (for SPM)
function spmInvite(aThread)
{
	Invite(aThread.title, aThread.url, aThread.host, aThread.bbs, aThread.key, spmResNum);
}
