// textareaの高さをライブ調節する
function adjustTextareaRows(obj, org, plus) {
	var brlen = null;
	if (obj.wrap) {
		if (obj.wrap == 'virtual' || obj.wrap == 'soft') {
			brlen = obj.cols;
		}
	}
	var aLen = countLines(obj.value, brlen);
	var aRows = aLen + plus;
	var move = 0;
	var scroll = 14;
	if (org) {
		if (Math.max(aRows, obj.rows) > org) {
			move = Math.abs((aRows - obj.rows) * scroll);
			if (move) {
				obj.rows = Math.max(org, aRows);
				window.scrollBy(0, move);
			}
		}
		/*
		if (aRows > org + plus) {
			if (obj.rows < aRows) {
				move = (aRows - obj.rows) * scroll;
			} else if (obj.rows > aRows) {
				move = (aRows - obj.rows) * -scroll;
			}
			if (move != 0) {
				if (move < 0) {
					window.scrollBy(0, move);
				}
				obj.rows = aRows;
				if (move > 0) {
					window.scrollBy(0, move);
				}
			}
		}
		*/
	} else if (obj.rows < aRows) {
		move = (aRows - obj.rows) * scroll;
		obj.rows = aRows;
		window.scrollBy(0, move);
	}
}

/**
 * \n を改行として行数を数える
 *
 * @param integer brlen 改行する文字数。無指定なら文字数で改行しない
 */
function countLines(str, brlen) {
	var lines = str.split("\n");
	var count = lines.length;
	var aLen = 0;
	for (var i = 0; i < lines.length; i++) {
		aLen = jstrlen(lines[i]);
		if (brlen) {
			if (aLen > brlen) {
				count = count + Math.ceil(aLen / brlen);
			}
		}
	}
	return count;
}

// 文字列をバイト数で数える
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
function setFocus(ID){
	if (obj = document.getElementById(ID)) {
		if (obj.disabled != true) {
			obj.focus();
		}
	}
}

// sageチェックに合わせて、メール欄の内容を書き換える
function mailSage(){
	if (cbsage = document.getElementById('sage')) {
		if (mailran = document.getElementById('mail')) {
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
	if (mailran = document.getElementById('mail')) {
		if (cbsage = document.getElementById('sage')) {
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
function loadLastPosted(from, mail, message){
	if (fromran = document.getElementById('FROM')) {
		fromran.value = from;
	}
	if (mailran = document.getElementById('mail')) {
		mailran.value = mail;
	}
	if (messageran = document.getElementById('MESSAGE')) {
		messageran.value = message;
	}
	checkSage();
}
*/

// メール欄の内容に応じて、sageチェックをON OFFする
function checkSage() {
	if (mailran = document.getElementById('mail')) {
		if (cbsage = document.getElementById('sage')) {
			if (mailran.value == "sage") {
				cbsage.checked = true;
			} else {
				cbsage.checked = false;
			}
		}
	}
}

// 定型文を挿入する
function inputConstant(obj) {
	var msg = document.getElementById('MESSAGE');
	msg.value = msg.value + obj.options[obj.selectedIndex].value;
	msg.focus();
	obj.options[0].selected = true;
}

// 書き込み内容を検証する
function validateAll(doValidateMsg, doValidateSage) {
	if (document.getElementById('block_submit') && document.getElementById('block_submit').checked) {
		alert('書き込みブロック中');
		return false;
	}
	if (doValidateMsg && !validateMsg()) {
		return false;
	}
	if (doValidateSage && !validateSage()) {
		return false;
	}
	return true;
}

// 本文が空でないか検証する
function validateMsg() {
	if (document.getElementById('MESSAGE').value.length == 0) {
		alert('本文がありません。');
		return false;
	}
	return true;
}

// sageているか検証する
function validateSage() {
	if (document.getElementById('mail').value.indexOf('sage') == -1) {
		if (window.confirm('sageてませんよ？')) {
			return true;
		} else {
			return false;
		}
	}
	return true;
}
