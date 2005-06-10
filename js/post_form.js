/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

function setFocus(ID) {
	document.getElementById(ID).focus();
}

// sageチェックに合わせて、メール欄の内容を書き換える
function mailSage() {
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
