/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

/* p2 - AA補正JavaScriptファイル */

// HTMLソースのクリーンアップ用正規表現
var amhtre = new Array(7);
var amhtrp = new Array(7);
amhtre[0] = /<br( .*?)?>/ig;
amhtrp[0] = "\n";
amhtre[1] = /<.*?>/g;
amhtrp[1] = "";
amhtre[2] = /\s+$/g;
amhtrp[2] = "";
amhtre[3] = /&gt;/g;
amhtrp[3] = ">";
amhtre[4] = /&lt;/g;
amhtrp[4] = "<";
amhtre[5] = /&quot;/g;
amhtrp[5] = '"';
amhtre[6] = /&amp;/g;
amhtrp[6] = "&";

// AA によく使われるパディングと
// Latin-1,全角スペースと句読点,ひらがな,カタカナ,半角・全角形 以外の同じ文字が3つ連続するパターン
/* Firefox では期待通りに動作するが、Safari は全角文字をうまく扱えないっぽい... orz */
var amaare = new Array(2);
amaare[0] = /\u3000{4}|(\x20\u3000){2}/;
amaare[1] = /([^\x00-\x7F\u2010-\u203B\u3000-\u3002\u3040-\u309F\u30A0-\u30FF\uFF00-\uFFEF])\1\1/;
// Unicode Note: 一般句読点 = \u2000-\u206F, CJKの記号および句読点 = \u3000-\u303F, CJK統合漢字 = \u4E00-\u9FFF

// activeMona -- AA自動判定
function detectAA(blockId)
{
	var amTargetObj = document.getElementById(blockId);
	if (!amTargetObj) {
		return false;
	}
	var amTargetSrc = amTargetObj.innerHTML.replace(amhtre[0], amhtrp[0]).replace(amhtre[1], amhtrp[1]).replace(amhtre[2], amhtrp[2]).replace(amhtre[3], amhtrp[3]).replace(amhtre[4], amhtrp[4]).replace(amhtre[5], amhtrp[5]).replace(amhtre[6], amhtrp[6]);
	// 改行が3つ以上あり、AAパターンにマッチしたら真を返す
	if (amTargetSrc.split("\n").length > 3 && (amTargetSrc.search(amaare[0]) != -1 || amTargetSrc.search(amaare[1]) != -1)) {
		//window.alert(amTargetSrc);
		return true;
	}
	return false;
}

// activeMona -- モナーフォントに切り替え、行の高さも縮める
function activeMona(blockId)
{
	var amTargetObj = document.getElementById(blockId);
	if (!amTargetObj) {
		return;
	}
	if (amTargetObj.className.search(/\bActiveMona\b/) != -1) {
		amTargetObj.className = amTargetObj.className.replace(/ ?ActiveMona/, '');
	} else {
		amTargetObj.className += ' ActiveMona';
	}
}

// activeMonaForm -- アクティブモナー on フォーム
function activeMonaForm(size)
{
	var message, mail;
	if (size == "") {
		return;
	}
	if (dpreview_ok) {
		var dp = document.getElementById("dpreview");
		if (dp) {
			if (dp.style.display == "none") {
				DPInit();
				dp.style.display = "block";
			}
			activeMona("dp_msg", size);
			return;
		} else {
			message = document.getElementById("MESSAGE");
			mail = document.getElementById("mail");
		}
	} else {
		message = document.getElementById("MESSAGE");
		mail = document.getElementById("mail");
	}
	if (!message || !mail) {
		return;
	}
	if (size == "normal") {
		message.style.fontFamily = mail.style.fontFamily;
		message.style.fontSize = mail.style.fontSize;
	} else {
		message.style.fontFamily = am_aa_fontFamily;
		message.style.fontSize = size;
	}
}
