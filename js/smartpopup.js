/* p2 - スマートポップアップメニューJavaScriptファイル */

var spmResNum   = new Number(); // ポップアップで参照するレス番号
var spmBlockID  = new String(); // フォント変更で参照するID
var spmSelected = new String(); // 選択文字列を一時的に保存
var spmTarget   = new String(); // フィルタリング結果を開くウインドウ

// スマートポップアップメニューを生成出力する
// @access  public
// @return  void
function makeSPM(aThread)
{
	var thread_id = aThread.objName;
	var a_tag    = "<a href=\"#\" onclick=\"return !spmOpenSubWin(" + thread_id + ",";
	var numbox   = "";
	if (document.getElementById || document.all) {
		//numbox = "<input disabled type=\"text\" id=\"" + thread_id + "_numbox\" class=\"numbox\" size=\"4\" value=\"0\">";
		numbox = "<span id=\"" + thread_id + "_numbox\" class=\"numbox\"> </span>";
	}

	//ポップアップメニューを生成

	document.writeln("<div id=\"" + thread_id + "_spm\" class=\"spm\"" + makeOnPopUp(thread_id+"_spm", false) + ">");
	
	//ヘッダ
	if (aThread.spmHeader != "") {
		document.writeln("<p>" + aThread.spmHeader.replace("resnum", numbox) + "</p>");
	}
	
	// これにレス
	var baseOptionKoreRes = '';
	if ((typeof(gIsReadNew) == 'boolean') && gIsReadNew) {
		baseOptionKoreRes = 'from_read_new=1&';
	}
	if (aThread.spmOption['spm_kokores'] && aThread.spmOption['spm_kokores'] == 1) {
		document.writeln(getSpmLinkTag(thread_id, 'post_form.php', baseOptionKoreRes, 'これにレス', thread_id + '_kore_res'));
		document.writeln(getSpmLinkTag(thread_id, 'post_form.php', baseOptionKoreRes + 'inyou=1', '引用してレス', thread_id + '_kore_res1'));
	} else if (aThread.spmOption['spm_kokores'] && aThread.spmOption['spm_kokores'] == 2) {
		document.writeln(getSpmLinkTag(thread_id, 'post_form.php', baseOptionKoreRes + 'inyou=2', 'これにレス', thread_id + '_kore_res2'));
		document.writeln(getSpmLinkTag(thread_id, 'post_form.php', baseOptionKoreRes + 'inyou=3', '引用してレス', thread_id + '_kore_res3'));
	}
	
	//しおり
	if (aThread.spmOption['enable_bookmark']) {
		document.writeln(a_tag + "'info_sp.php','mode=readhere');\">ここまで読んだ</a>");
	}
	//あぼーんワード
	if (aThread.spmOption['spm_aborn']) {
		document.writeln(a_tag + "'info_sp.php','mode=aborn_res');\">あぼーんする</a>");
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_ab", true) + ">あぼーんワード</a>");
	}
	//NGワード
	if (aThread.spmOption['spm_ng']) {
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_ng", true) + ">NGワード</a>");
	}
	//アクティブモナー
	if (aThread.spmOption['enable_am_on_spm']) {
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_ds", true) + ">フォント設定</a>");
	} else if (aThread.spmOption['enable_am_on_spm'] == 2) {
		makeDynamicStyleSPM(thread_id+"_ds", false);
	}
	
	/*
	//フィルタリング
	if (aThread.spmOption['enable_fl_on_spm']) {
		document.writeln("<a href=\"javascript:void(0);\"" + makeOnPopUp(thread_id+"_fl", true) + ">フィルタ表示</a>");
	}
	*/

	// 同じ名前をフィルタ表示
	document.writeln('<a id="' + thread_id + '_same_name" href="#" target="' + spmTarget + '">同じ名前</a>');
	
	// 逆参照
	document.writeln('<a id="' + thread_id + '_ref_res" href="' + getSpmFilterUrl(aThread, 'rres', 'on') + '" target="' + spmTarget + '">逆参照</a>');

	document.writeln("</div>");

	///サブメニューを生成

	//あぼーんワード・サブメニュー
	if (aThread.spmOption['spm_aborn']) {
		makeAbornSPM(thread_id+"_ab", a_tag);
	}
	//NGワード・サブメニュー
	if (aThread.spmOption['spm_ng']) {
		makeAbornSPM(thread_id+"_ng", a_tag);
	}
	//フォント設定・サブメニュー
	if (aThread.spmOption['enable_am_on_spm']) {
		makeDynamicStyleSPM(thread_id+"_ds", true);
	}
	//フィルタリング・サブメニュー
	if (aThread.spmOption['enable_fl_on_spm']) {
		makeFilterSPM(thread_id+"_fl", thread_id);
	}
}

// スマートポップアップメニューをポップアップ表示する
// @access  public
// @return  void
function showSPM(aThread, resnum, resid, event, obj)
{
	var ls_q = gExistWord ? resnum + '-n' : resnum;
	
	obj.href = "read.php?bbs=" + aThread.bbs + "&key=" + aThread.key + "&host=" + aThread.host + '&offline=1&ls=' + ls_q;
	
	spmResNum  = resnum;
	spmBlockID = resid;
	if (navigator.userAgent.indexOf("Gecko") != -1) {
		spmSelected = window.getSelection();
	}
	if (aThread.spmHeader.indexOf("resnum") != -1 && (document.getElementById || document.all)) {
		//p2GetElementById(aThread.objName + "_numbox").value = resnum;
		p2GetElementById(aThread.objName + "_numbox").innerHTML = resnum;
	}
	
	var spm_same_name = p2GetElementById(aThread.objName + "_same_name");
	if (spm_same_name) {
		spm_same_name.href = getSpmFilterUrl(aThread, 'name', 'on');
	}
	var spm_ref_res = p2GetElementById(aThread.objName + "_ref_res");
	if (spm_ref_res) {
		spm_ref_res.href = getSpmFilterUrl(aThread, 'rres', 'on');
	}
	var kore_res = p2GetElementById(aThread.objName + "_kore_res");
	if (kore_res) {
		kore_res.href = getSpmOpenSubUrl(aThread, 'post_form.php', '');
	}
	var kore_res1 = p2GetElementById(aThread.objName + "_kore_res1");
	if (kore_res1) {
		kore_res1.href = getSpmOpenSubUrl(aThread, 'post_form.php', 'inyou=1');
	}
	var kore_res2 = p2GetElementById(aThread.objName + "_kore_res2");
	if (kore_res2) {
		kore_res2.href = getSpmOpenSubUrl(aThread, 'post_form.php', 'inyou=2');
	}
	var kore_res3 = p2GetElementById(aThread.objName + "_kore_res3");
	if (kore_res3) {
		kore_res3.href = getSpmOpenSubUrl(aThread, 'post_form.php', 'inyou=3');
	}
	
	showResPopUp(aThread.objName + "_spm", event);
}


/* ==================== 覚え書き ====================
 * <a href="javascript:void(0);" onclick="foo()">は
 * <a href="javascript:void(foo());">と等価。
 * JavaScriptでURIを生成するとき、&を&amp;としてはいけない。
 * ================================================== */


// @access  private only this file
// @return  string
function getSpmLinkTag(thread_id, path, option, text, a_id)
{
	return '<a id="' + a_id + '" href="#" onclick="return !spmOpenSubWin(' + thread_id + ",'" + path + "','" + option + "');\">" + text + "</a>";
}

// マウスオーバー/アウト時に実行されるスクリプトを生成する
// @access  private only this file
// @return  string
function makeOnPopUp(popup_id, isSubMenu)
{
	//遅延時間
	var spmPopUpDelay = "delaySec=(0.3*1000);";
	if (isSubMenu) {
		spmPopUpDelay = "delaySec=0;";
	}
	//ロールオーバー
	var spmPopUpEvent  = " onmouseover=\"" + spmPopUpDelay + "showResPopUp('" + popup_id + "',event,true);\"";
	//ロールアウト
		spmPopUpEvent += " onmouseout=\""  + spmPopUpDelay + "hideResPopUp('" + popup_id + "');\"";
	return spmPopUpEvent;
}


// あぼーん/NGサブメニューを生成出力する
// @access  private only this file
// @return  void
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


// フォント設定サブメニューを生成出力する
// @access  private only this file
// @return  void
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


// フィルタリングサブメニューを生成する
// @access  private only this file
// @return  void
function makeFilterSPM(menu_id, thread_id)
{
	var filter_anchor = "<a href=\"javascript:void(spmOpenFilter(" + thread_id;
	document.writeln("<div id=\"" + menu_id + "\" class=\"spm\"" + makeOnPopUp(menu_id, true) + ">");
	document.writeln(filter_anchor + ",'name','on'));\">同じ名前</a>");
	document.writeln(filter_anchor + ",'mail','on'));\">同じメール</a>");
	//document.writeln(filter_anchor + ",'date','on'));\">同じ日付</a>");
	//document.writeln(filter_anchor + ",'id','on'));\">同じID</a>");
	//document.writeln(filter_anchor + ",'name','off'));\">異なる名前</a>");
	//document.writeln(filter_anchor + ",'mail','off'));\">異なるメール</a>");
	//document.writeln(filter_anchor + ",'date','off'));\">異なる日付</a>");
	//document.writeln(filter_anchor + ",'id','off'));\">異なるID</a>");
	//document.writeln(filter_anchor + ",'rres','on'));\">逆参照</a>");
	document.writeln("</div>");
}

// URIの処理をし、ポップアップウインドウを開く
// @access  private only this file
// @return  boolean
function spmOpenSubWin(aThread, path, option)
{
	var width  = 650; //ポップアップウインドウの幅
	var height = 350; //ポップアップウインドウの高さ
	var boolScrl = 1; //スクロールバーを表示（off:0, on:1）
	var boolResize = 0; //自動リサイズ（off:0, on:1）
	if (path == "info_sp.php") {
		width  = 480;
		height = 240;
		boolScrl = 0;
	} else if (path == "post_form.php" && aThread.spmOption['spm_kokores'] && aThread.spmOption['spm_kokores'] == 2) {
		//height = 450;
	} else if (path == "tentori.php") {
		width  = 450;
		height = 150;
	}
	
	var url = getSpmOpenSubUrl(aThread, path, option);
	return openSubWin(url, width, height, boolScrl, boolResize);
}

// ポップアップウィンドウを開くためのURIを返す
// @access  private only this file
// @return  string
function getSpmOpenSubUrl(aThread, path, option)
{
	var popup = 1;    //ポップアップウインドウか否か（no:0, yes:1, yes&タイマーで閉じる:2）
	if (path == "info_sp.php") {
		if (!aThread.spmOption['spm_confirm']) {
			popup = 2; //しおり,あぼーん/NGワード登録の確認をしないとき
		}
		if (option.indexOf("_msg") != -1 && spmSelected != '') {
			option += "&selected_string=" + encodeURIComponent(spmSelected);
		}
	} else if (path == "tentori.php") {
		popup = 2;
	}
	
	var url = path + "?host=" + aThread.host + "&bbs=" + aThread.bbs + "&key=" + aThread.key;
	url += "&rescount=" + aThread.rc + "&ttitle_en=" + aThread.ttitle_en;
	url += "&resnum=" + spmResNum + "&popup=" + popup;
	if (option != "") {
		url += "&" + option;
	}
	return url;
}

// フィルタリング用URLを返す
// @access  private only this file
// @return  string
function getSpmFilterUrl(aThread, field, match)
{
	var url = "read_filter.php?bbs=" + aThread.bbs + "&key=" + aThread.key + "&host=" + aThread.host;
	url += "&rescount=" + aThread.rc + "&ttitle_en=" + aThread.ttitle_en + "&resnum=" + spmResNum;
	url += "&ls=all&field=" + field + "&method=just&match=" + match + "&offline=1";
	return url;
}

// URIの処理をし、フィルタリング結果を表示する
// @access  private only this file
// @return  string
function spmOpenFilter(aThread, field, match)
{
	var url = getSpmFilterUrl(aThread, field, match);
	
	switch (spmTarget) {
		case "_self":
			location.href = url;
			break;
		case "_parent":
			parent.location.href = url;
			break;
		case "_top":
			top.location.href = url;
			break;
		case "_blank":
			window.open(url, "", "");
			break;
		default:
			if (parent.spmTarget.location.href) {
				parent.spmTarget.location.href = url;
			} else {
				window.open(url, spmTarget, "")
			}
	}
	
	return true;
}

// 対象オブジェクトを設定し、書式を変える
// @access  private only this file
// @return  boolean
function spmDynamicStyle(mode)
{
	var dsTarget     = p2GetElementById(spmBlockID);
	var dsFontSize   = dsTarget.style.fontSize;
	var dsLineHeight = dsTarget.style.lineHeight;
	var isAutoMona   = false;
	if (dsTarget.hasAttribute("class") && dsTarget.getAttribute("class") == "AutoMona") {
		isAutoMona   = true;
	}
	var isPopUp      = false;
	if (spmBlockID.charAt(0) == "q") {
		isPopUp      = true;
	}
	//再設定
	if (dsFontSize.length   < 1) {
		if (isAutoMona) {
			dsFontSize = "14px";
		} else if (isPopUp) {
			dsFontSize = am_respop_fontSize;
		} else {
			dsFontSize = am_read_fontSize;
		}
	}
	if (dsLineHeight.length < 1) {
		if (isAutoMona) {
			dsLineHeight = "100%";
		} else if (isPopUp) {
			dsLineHeight = am_respop_lineHeight;
		} else {
			dsLineHeight = am_read_lineHeight;
		}
	}
	//分岐
	switch (mode) {
		//アクティブモナー
		case "16px":
		case "14px":
		case "12px":
			activeMona(spmBlockID, mode);
			return true;
		//元のフォントサイズに戻す
		case "rewind":
			if (isQuoteBlock) {
				dsTarget.style.fontSize   = am_respop_fontSize;
				dsTarget.style.lineHeight = am_respop_lineHeight;
			} else {
				dsTarget.style.fontSize   = am_read_fontSize;
				dsTarget.style.lineHeight = am_read_lineHeight;
			}
			//引き続き標準フォントにする
		//標準フォントにする
		case "normal":
			dsTarget.style.fontFamily = am_fontFamily;
			dsTarget.style.whiteSpace = "normal";
			return true;
		//等幅フォントにする
		case "monospace":
			dsTarget.style.fontFamily = "monospace";
			dsTarget.style.whiteSpace = "pre";
			return true;
		//フォントサイズを変える
		case "larger":
		case "smaller":
			var newFontSize    = new Number;
			var curFontSize    = new Number(dsFontSize.match(/^\d+/));
			var FontSizeUnit   = new String(dsFontSize.match(/\D+$/));
			var newLineHeight  = new Number;
			var curLineHeight  = new Number(dsLineHeight.match(/^\d+/));
			var LineHeightUnit = new String(dsLineHeight.match(/\D+$/));
			if (mode == "larger") {
				newFontSize   = curFontSize   * 1.25;
				newLineHeight = curLineHeight * 1.25;
			} else {
				newFontSize   = curFontSize   * 0.8;
				newLineHeight = curLineHeight * 0.8;
			}
			if (LineHeightUnit == "%") {
				newLineHeight = curLineHeight;
			}
			dsTarget.style.fontSize   = newFontSize.toString()   + FontSizeUnit;
			dsTarget.style.lineHeight = newLineHeight.toString() + LineHeightUnit;
			return true;
		//...
		default:
			return false;
	}
}
