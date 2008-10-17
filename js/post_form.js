/* p2 - 書き込みフォーム用JavaScript */

// クッキーから書きかけのフォーム内容を復活する
// @return  void
function hukkatuPostForm(host, bbs, key) {
	var chost = getCookie('post_host');
	var cbbs = getCookie('post_bbs');
	var ckey = getCookie('post_key');
	if (host == chost && bbs == cbbs && key == ckey) {
		var message = getCookie('post_msg');
		if (!message) {
			return;
		}
		var obj = document.getElementById('MESSAGE');
		obj.value = message;
	}
}

// @return  string
function getCookie(cn) {
   get_data = document.cookie;
   cv = new Array();
   gd = get_data.split(";");
   for (i in gd) {
      a = gd[i].split("=");
      a[0] = a[0].replace(" ","");
      cv[a[0]] = a[1];
   }
	if (cv[cn]) {
		return unescape(cv[cn]);
	} else {
		return "";
	}
}

// @return  void
function setCookie(cn, val, sec) {
	
	// クッキーの有効時間
	if (!sec) {
		sec = 1000*60*60*24*30; // 30日間
	}
	
	val = escape(val);

	ex = new Date();
	ex = new Date(ex.getTime() + (1000 * sec));
	y = ex.getYear(); if (y < 1900) y += 1900;
	hms = ex.getHours() + ":" + ex.getMinutes() + ":" + ex.getSeconds();
	p = String(ex).split(" ");
	ex = p[0] + ", " + p[2] + "-" + p[1] + "-" + y + " " + hms + " GMT;";
	document.cookie = cn + "=" + val +"; expires=" + ex;
}

// @return  object
function getDataPostForm(host, bbs, key)
{
	var from = document.getElementById('FROM').value;
	var mail = document.getElementById('mail').value;
	var message = document.getElementById('MESSAGE').value;
	var subject; if (subject = document.getElementById('subject')) { subject = subject.value; }
	var data = {'host':host, 'bbs':bbs, 'key':key, 'from':from, 'mail':mail, 'message':message};
	return data;
}

// 書き込みフォームの内容を自動保存する
// @return  true|null
g_coming_auto_save_post_form = false;	// 連続動作抑制のための動作中フラグ
g_timer_auto_save_post_form = null;

function autoSavePostForm(host, bbs, key)
{
	var timer_micro = 1.5 * 1000;	// 連続動作を抑制する時間（マイクロ秒）
	
	if (g_coming_auto_save_post_form) {
		if (g_timer_auto_save_post_form) {
			clearTimeout(g_timer_auto_save_post_form);
		}
	} else {
		var message = document.getElementById('MESSAGE').value;
		if (!message || message.length > 1900) {
			return null;
		}
		//comingAutoSavePostForm(host, bbs, key);
		g_coming_auto_save_post_form = true;
	}
	g_timer_auto_save_post_form = setTimeout("comingAutoSavePostForm('" + host + "', '" + bbs + "', '" + key + "')", timer_micro);
	
	return true;
}

// autoSavePostForm の連続動作を抑制しながら実行を行う
//
// @return  void
function comingAutoSavePostForm(host, bbs, key)
{
	g_coming_auto_save_post_form = false;
	autoSavePostFormCookie(host, bbs, key);
}

// @return  true|null
function autoSavePostFormCookie(host, bbs, key)
{
	var data = getDataPostForm(host, bbs, key);
	if (!data['message']) {
		return null
	}
	setCookie('post_msg', data['message']);
	setCookie('post_host', data['host']);
	setCookie('post_bbs', data['bbs']);
	setCookie('post_key', data['key']);
	//blinkStatusPostForm('save cokkie');
	return true;
}

/* ajaxはやめてcookieを利用することにした

// @return  boolean|null
function autoSavePostFormAjax(host, bbs, key)
{
	var data = getDataPostForm(host, bbs, key);
	if (!data['message']) {
		return null
	}
	
	var postdata = 'hint=' + encodeURI('美乳') + '&cmd=auto_save_post_form';
	for (var k in data) {
		postdata += '&' + k + '=' + encodeURI(data[k]);
	}

	var xmlHttpObj = getXmlHttp();
	if (!xmlHttpObj) {
		return false;
	}

	var url = 'httpcmd.php';
	var now = new Date();
	//url = url + '&' + 'nc' + '=' + now.getTime(); // キャッシュ回避用
	xmlHttpObj.open('POST', url, true);
	if (isSafari()) {
	    xmlHttpObj.onload = function(){ checkResultAutoSavePostForm(xmlHttpObj); }
	} else {
	    xmlHttpObj.onreadystatechange = function() {
	        if (xmlHttpObj.readyState == 4 && xmlHttpObj.status == 200) {
	            checkResultAutoSavePostForm(xmlHttpObj);
	        }
	    }
	}
	xmlHttpObj.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");
	xmlHttpObj.send(postdata);

	return true;
}


// @return  void
function checkResultAutoSavePostForm(xmlHttpObj)
{
	var res = xmlHttpObj.responseText.replace(/^<\?xml .+?\?>\n?/, '');
	blinkStatusPostForm(res);
}
*/

// @return  void
function blinkStatusPostForm(str)
{
	var timer_micro = 0.3*1000;
	setStatusPostForm(str);
	var timer_id = setTimeout("setStatusPostForm('')", timer_micro);
}

// @return  void
function setStatusPostForm(str)
{
	var status = document.getElementById('status_post_form');
	status.innerHTML = str;
}

// textareaの高さをライブ調節する
// @return  void
g_coming_adjust_textarea_rows = new Array();	// 連続動作抑制のための動作中フラグ
g_adjust_textarea_objs = new Array();
g_adjust_textarea_orgs = new Array();
g_adjust_textarea_timers = new Array();
function adjustTextareaRows(obj, plus)
{
	var limit_rows = 40;		// 動作可能な最大行数
	var timer_micro = 1 * 1000;	// 連続動作を抑制する時間（マイクロ秒）

	if (obj.rows > limit_rows) {
		return;
	}
	
	g_adjust_textarea_objs[obj.id] = obj;
	if (!g_adjust_textarea_orgs[obj.id]) {
		g_adjust_textarea_orgs[obj.id] = obj.rows;
	}
	if (g_coming_adjust_textarea_rows[obj.id]) {
		if (g_adjust_textarea_timers[obj.id]) {
			clearTimeout(g_adjust_textarea_timers[obj.id]);
		}
	} else {
		comingAdjustTextareaRows(obj.id, plus);
		g_coming_adjust_textarea_rows[obj.id] = true;
	}
	
	g_adjust_textarea_timers[obj.id] = setTimeout("comingAdjustTextareaRows('" + obj.id + "', " + plus + ")", timer_micro);
}

// doAdjustTextareaRows を実行する
// @return  void
function comingAdjustTextareaRows(id, plus)
{
	g_coming_adjust_textarea_rows[id] = false;
	var obj = g_adjust_textarea_objs[id];
	doAdjustTextareaRows(obj, plus);
	//g_adjust_textarea_objs[id] = null;
	//blinkStatusPostForm('adjust');
}

// textareaの高さをライブ調節する。実処理部分。
// @return  void
function doAdjustTextareaRows(obj, plus)
{
	var do_scroll = false; // 変な動きをしやすい？

	var brlen = null;
	if (obj.wrap) {
		if (obj.wrap == 'virtual' || obj.wrap == 'soft') {
			brlen = obj.cols;
		}
	}
	var my_len = countLines(obj.value, brlen);
	var my_rows = my_len + plus;
	var move_height = 0;
	var scroll_rows = 14;
	
	//blinkStatusPostForm(obj.rows + ' ' + my_rows);
	
	if (obj.rows < my_rows) {
		move_height = (my_rows - obj.rows) * scroll_rows;
	} else if (obj.rows > my_rows) {
		move_height = (my_rows - obj.rows) * -scroll_rows;
	}
	if (move_height != 0) {
		if (do_scroll && move_height < 0) {
			window.scrollBy(0, move_height);
		}
		if (my_rows > g_adjust_textarea_orgs[obj.id]) {
			obj.rows = my_rows;
		} else {
			obj.rows = g_adjust_textarea_orgs[obj.id];
		}
		if (do_scroll && move_height > 0) {
			window.scrollBy(0, move_height);
		}
	}
}

// \n を改行として行数を数える
// @param integer brlen 改行する文字数。無指定なら文字数で改行しない
function countLines(str, brlen)
{
	var lines = str.split("\n");
	var count = lines.length;
	var aLen = 0;
	for (var i = 0; i < lines.length; i++) {
		aLen = jstrlen(lines[i]);
		if (brlen) {
			var adjust =  1.15; // 単語単位の折り返しに対応していないのでアバウト調整
			if ((aLen * adjust) > brlen) {
				count = count + Math.floor((aLen * adjust) / brlen);
			}
		}
	}
	return count;
}

// 文字列をバイト数で数える
// @return  integer
function jstrlen(str) {
	var len = 0;
	str = escape(str);
	for (var i = 0; i < str.length; i++, len++) {
		if (str.charAt(i) == "%") {
			if (str.charAt(++i) == "u") {
				i += 3;
				len++;
			}
			i++;
		}
	}
	return len;
}

// (対象がdisableでなければ) フォーカスを合わせる
function setFocus(ID) {
	var obj = document.getElementById(ID);
	if (obj) {
		if (obj.disabled != true) {
			obj.focus();
		}
	}
}

// sageチェックに合わせて、メール欄の内容を書き換える
function mailSage() {
	var cbsage = document.getElementById('sage');
	if (cbsage) {
		var mailran = document.getElementById('mail');
		if (mailran) {
			if (cbsage.checked == true) {
				mailran.value = "sage";
			} else {
				if (mailran.value == "sage") {
					mailran.value = "";
				}
			}
		}
	}
}

// メール欄の内容に応じて、sageチェックをON OFFする
function checkSage(){
	var mailran = document.getElementById('mail');
	if (mailran) {
		var cbsage = document.getElementById('sage');
		if (cbsage) {
			if (mailran.value == "sage") {
				cbsage.checked = true;
			} else {
				cbsage.checked = false;
			}
		}
	}
}

/*
// 自動で読み込むことにしたので、使わない

// 前回の書き込み内容を復帰する
function loadLastPosted(from, mail, message) {
	var fromObj = document.getElementById('FROM');
	if (fromObj) {
		fromObj.value = from;
	}
	var mailObj = document.getElementById('mail');
	if (mailObj) {
		mailObj.value = mail;
	}
    var messageObj = document.getElementById('MESSAGE');
	if (messageObj) {
		messageObj.value = message;
	}
	checkSage();
}
*/

// @return  void
function inputConstant(obj) {
	var msg = p2GetElementById('MESSAGE')
	if (msg) {
		cur = msg.value;
		add = obj.options[obj.selectedIndex].value;
		obj.options[0].selected = true;
		msg.value = cur+add;
		msg.focus();
	}
}

// return  boolean
function isNetFront() {
  var ua = navigator.userAgent;
  if (ua.indexOf("NetFront") != -1 || ua.indexOf("AVEFront/") != -1 || ua.indexOf("AVE-Front/") != -1) {
    return true;
  } else {
    return false;
  }
}

// @thanks  naoya <http://d.hatena.ne.jp/naoya/20050804/1123152230>
// @return  void
function disableSubmit(form)
{
  // 2006/02/15 NetFrontとは相性が悪く固まるらしいので抜ける
  if (isNetFront()) {
    return;
  }

  var elements = form.elements;
  for (var i = 0; i < elements.length; i++) {
    if (elements[i].type == 'submit') {
      elements[i].disabled = true;
    }
  }
}

// @return  void
function setHiddenValue(button)
{
  // 2006/02/15 NetFrontとは相性が悪く固まるらしいので抜ける
  if (isNetFront()) {
    return;
  }

  if (button.name) {
    var q = document.createElement('input');
    q.type = 'hidden';
    q.name = button.name;
    q.value = button.value;
    button.form.appendChild(q);
  }
}
