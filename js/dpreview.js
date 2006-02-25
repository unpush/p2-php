/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

// 編集中の投稿内容を動的プレビューするための関数群

// 初期化
function DPInit()
{
	if (!dpreview_ok || !document.getElementById || !document.getElementById('dpreview').innerHTML) {
		dpreview_ok = 0;
		return;
	}
	DPSetName(document.getElementById('FROM').value);
	DPSetMail(document.getElementById('mail').value);
	DPSetMsg(document.getElementById('MESSAGE').value);
	DPSetDate();
}


// 名前欄を更新する
function DPSetName(_value)
{
	if (!dpreview_ok) { return; }
	var dpname = '';
	var dptrip = '';
	if (_value.length == 0) {
		dpname = '名無しさん＠お腹いっぱい';
	} else {
		tp = _value.indexOf('#');
		if (tp != -1) {
			dpname = _value.substr(0, tp);
			dptrip = DBGetTrip(_value.substr(tp + 1, 8));
		} else {
			dpname = _value;
		}
	}
	document.getElementById('dp_name').innerHTML = htmlspecialchars(dpname);
	document.getElementById('dp_trip').innerHTML = htmlspecialchars(dptrip);
	DPSetDate();
}


// メール欄を更新する
function DPSetMail(_value)
{
	if (!dpreview_ok) { return; }
	document.getElementById('dp_mail').innerHTML = htmlspecialchars(_value);
	DPSetDate();
}


// 本文を更新する
function DPSetMsg(_value)
{
	if (!dpreview_ok) { return; }
	document.getElementById('dp_msg').innerHTML = nl2br(htmlspecialchars(_value));
	DPSetDate();
}


// 日付を更新する
function DPSetDate()
{
	if (!dpreview_ok) { return; }
	var now  = new Date();
	var year = now.getFullYear();
	var mon  = now.getMonth() + 1;
	var date = now.getDate();
	var hour = now.getHours();
	var min  = now.getMinutes();
	var sec  = now.getSeconds();
	var timestamp = year
		+ '/' + ((mon < 10) ? '0' + mon : mon)
		+ '/' + ((date < 10) ? '0' + date : date)
		+ ' ' + ((hour < 10) ? '0' + hour : hour)
		+ ':' + ((min < 10) ? '0' + min : min)
		+ ':' + ((sec < 10) ? '0' + sec : sec)
	document.getElementById('dp_date').innerHTML = htmlspecialchars(timestamp);
}


// XMLHttpRequestを用いてトリップを取得する
function DBGetTrip(tk)
{
	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		return '◆XMLHTTP Disabled.';
	}
	var uri = 'tripper.php?tk=' + encodeURI(tk);
	objHTTP.open('GET', uri, false);
	objHTTP.send(null);
	if ((objHTTP.status != 200 || objHTTP.readyState != 4) && !objHTTP.responseText) {
		return '◆XMLHTTP Failed.';
	}
	return '◆' + objHTTP.responseText;
}
