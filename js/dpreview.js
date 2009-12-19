/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

// 編集中の投稿内容を動的プレビューするための関数群

var dp_prepared    = false;
var dp_is_explorer = false;
var dp_is_opera    = false;
var dp_is_safari   = false;

if (navigator.userAgent.indexOf('AppleWebKit') != -1) {
	dp_is_safari = true;
} else if (navigator.userAgent.indexOf('Opera') != -1) {
	dp_is_opera = true;
} else if (navigator.userAgent.indexOf('MSIE') != -1) {
	dp_is_explorer = true;
}

var dp_box, dp_msg, dp_empty, dp_mona, f_name, f_mail, f_sage, f_msg, f_src;

// 初期化
function DPInit()
{
	if (!dpreview_use || dp_prepared) {
		return;
	}
	if (!document.getElementById || !document.getElementById('dpreview')) {
		dpreview_use = false;
		dpreview_on = false;
		return;
	} else {
		dp_box = document.getElementById('dpreview');
		dp_msg = document.getElementById('dp_msg');
		//dp_empty = document.getElementById('dp_empty');
		if (document.getElementById('dp_mona')) {
			dp_mona = document.getElementById('dp_mona');
		}
		f_name = document.getElementById('FROM');
		f_mail = document.getElementById('mail');
		f_sage = document.getElementById('sage');
		f_msg = document.getElementById('MESSAGE');
		if (document.getElementById('fix_source')) {
			f_src = document.getElementById('fix_source');
		}
		f_msg.toHTML = function() {
			return this.value.decodeEntity().htmlspecialchars().nl2br();
		}
		f_msg.toHTML2 = function() {
			return this.value.htmlspecialchars2().nl2br2();
		}
		f_msg.toHTMLsrc = function() {
			return this.value.htmlspecialchars().nl2br().replace(/[\r\n]/g, '').replace(/ /g, '&nbsp;');
		}
	}
	// 名前欄の更新イベントハンドラを設定
	f_name.onkeyup = DPSetName;
	f_name.onchange = DPSetName;
	// メール欄の更新イベントハンドラを設定
	f_mail.onkeyup = DPSetMail;
	f_mail.onchange = DPSetMail;
	// sageチェックボックスの更新イベントハンドラを設定
	f_sage.onclick = DPSetSage;
	// メッセージ欄の更新イベントハンドラを設定
	//f_msg.onkeyup = DPSetMsg;
	f_msg.onchange = DPSetMsg;
	// ソースコード補正チェックボックスの更新イベントハンドラを設定
	if (f_src) { f_src.onclick = DPChangeStyle; }
	// アスキーアート補正チェックボックスの更新イベントハンドラを設定
	if (dp_mona) { dp_mona.onclick = DPChangeStyle; }
	// 準備完了
	dp_prepared = true;
}


// プレビュー表示の on/off を切り替える
function DPShowHide(boolOnOff)
{
	if (!dpreview_use) {
		return;
	}
	if (!dp_prepared) {
		DPInit();
	}
	if (boolOnOff) {
		dpreview_on = true;
		DPSetName();
		DPSetMail();
		DPSetMsg();
		DPSetDate();
		if (dp_mona) {
			dp_mona.disabled = false;
		}
		DPChangeStyle();
		dp_box.style.display = 'block';
		dp_box.style.visibility = 'visible';
	} else {
		dpreview_on = false;
		if (dp_mona) {
			dp_mona.disabled = true;
		}
		if (dpreview_hide) {
			dp_box.style.visibility = 'hidden';
		} else {
			dp_box.style.display = 'none';
		}
	}
}


// 内容のテキストを置換する
function DPReplaceInnerText(elem, cont)
{
	if (typeof elem == 'string') {
		elem = document.getElementById(elem);
	}
	elem.innerHTML = escapeHTML(cont);
}


// 名前欄を更新する
function DPSetName()
{
	if (!dpreview_on) {
		return;
	}
	var formval = f_name.value;
	var newname = '';
	if (formval.length == 0) {
		if (typeof noname_name == 'string') {
			newname = noname_name;
		}
	} else {
		var tp = formval.indexOf('#');
		if (tp != -1) {
			newname = formval.substr(0, tp);
			DPSetTrip(formval.substr(tp + 1));
		} else {
			newname = formval;
			DPReplaceInnerText('dp_trip', '');
		}
	}
	DPReplaceInnerText('dp_name', newname);
	DPSetDate();
}


// メール欄を更新する
function DPSetMail()
{
	checkSage();
	if (!dpreview_on) {
		return;
	}
	DPReplaceInnerText('dp_mail', f_mail.value);
	DPSetDate();
}


// sageチェック
function DPSetSage()
{
	mailSage();
	if (!dpreview_on) {
		return;
	}
	DPReplaceInnerText('dp_mail', f_mail.value);
	DPSetDate();
}

// 本文を更新する
function DPSetMsg()
{
	if (!dpreview_on) {
		return;
	}
//	if (f_msg.value.length == 0) {
//		dp_empty.style.display = 'block';
//		dp_msg.innerHTML = '';
//	} else {
//		dp_empty.style.display = 'none';
		if (f_src && f_src.checked) {
			dp_msg.innerHTML = f_msg.toHTMLsrc();
		} else {
			dp_msg.innerHTML = f_msg.toHTML2();
		}
//	}
	DPSetDate();
}


// 日付を更新する
function DPSetDate()
{
	if (!dpreview_on) {
		return;
	}
	var _now  = new Date();
	var _year = _now.getFullYear();
	var _mon  = _now.getMonth() + 1;
	var _date = _now.getDate();
	var _hour = _now.getHours();
	var _min  = _now.getMinutes();
	var _sec  = _now.getSeconds();
	var newdatetime = _year.toString()
		+ '/' + ((_mon < 10) ? '0' + _mon : _mon).toString()
		+ '/' + ((_date < 10) ? '0' + _date : _date).toString()
		+ ' ' + ((_hour < 10) ? '0' + _hour : _hour).toString()
		+ ':' + ((_min < 10) ? '0' + _min : _min).toString()
		+ ':' + ((_sec < 10) ? '0' + _sec : _sec).toString()
	DPReplaceInnerText('dp_date', newdatetime);
}


// XMLHttpRequestを用いてトリップを設定する
function DPSetTrip(tk)
{
	if (!dpreview_on) {
		return;
	}
	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		DPReplaceInnerText('dp_trip', '◆XMLHTTP Disabled.');
		return;
	}
	objHTTP.onreadystatechange = function() {
		if (objHTTP.readyState == 4) {
			DPReplaceInnerText('dp_trip', '◆' + objHTTP.responseText);
		}
	}
	var uri = 'tripper.php?tk=' + encodeURIComponent(tk);
	objHTTP.open('GET', uri, true);
	objHTTP.send(null);
}


// XMLHttpRequestを用いてトリップを取得する
function DPGetTrip(tk)
{
	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		return '◆XMLHTTP Disabled.';
	}
	var uri = 'tripper.php?tk=' + encodeURIComponent(tk);
	objHTTP.open('GET', uri, false);
	objHTTP.send(null);
	if ((objHTTP.status != 200 || objHTTP.readyState != 4) && !objHTTP.responseText) {
		return '◆XMLHTTP Failed.';
	}
	return '◆' + objHTTP.responseText;
}


// 本文のスタイルを切り替える
function DPChangeStyle()
{
	if (!dpreview_on) {
		return;
	}
	var new_class = 'prvw_msg';
	if (f_src && f_src.checked) {
		new_class += '_pre';
	}
	if (dp_mona && dp_mona.checked) {
		new_class += '_mona';
	}
	dp_msg.className = new_class;
	DPSetMsg();
}
