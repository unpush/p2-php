<?php
/*
	+live - スレッド表示に関する共通処理 ../showthreadpc.class.php より読み込まれる
*/

// スレ立てからの日数による処理
$thr_birth = date("U", $this->thread->key);

if ($_conf['live.time_lag'] != 0) {
	$thr_time_lag = $_conf['live.time_lag'] * 86400;
} else {
	$thr_time_lag = 365 * 86400;
}

// オートリロード設定値+日数
if (!preg_match("({$this->thread->bbs})", $_conf['live.default_reload'])
&& (preg_match("({$this->thread->bbs}|{$this->thread->host})", $_conf['live.reload']) || $_conf['live.reload'] == all)
&& (date("U") < $thr_birth + $thr_time_lag)) {
	$nldr_ylr_d = true;
} else {
	$nldr_ylr_d = false;
}

// ハイライト表示タイプ
if ($_conf['live.highlight_area'] == 1) {
	if ($isHighlightMsg || $isHighlightName || $isHighlightMail || $isHighlightId) {
		$highlight_res = "background-color: {$STYLE['live_highlight']}";
	} elseif ($isHighlightChain) {
		$highlight_res = "background-color: {$STYLE['live_highlight_chain']}";
	}
}

// 名前
// デフォルトの名無しの表示
// showthreadpc.class.phpにて

// 日付とID
// IDフィルタ
if ($_conf['flex_idpopup'] == 1 && $id && $this->thread->idcount[$id] > 1) {
	$date_id = preg_replace_callback('|ID: ?([0-9A-Za-z/.+]{8,11})|', array($this, 'idfilter_callback'), $date_id);
}
// 携帯ID 公式p2ID フルブラウザID の強調
if ($_conf['live.id_b']) {
	if (!preg_match("(ID:)", $date_id)) { // ID無し末尾表示有りの板
		$date_id = preg_replace('(((O|P|Q)$)(?![^<]*>))', '<b class="mail">$1</b>', $date_id);
	} else {
		$date_id = preg_replace('((ID: ?)([0-9A-Za-z/.+]{10}|[0-9A-Za-z/.+]{8}|\\?\\?\\?)?(O|P|Q)(?=[^0-9A-Za-z/.+]|$)(?![^<]*>))', '$1$2<b class="mail">$3</b>', $date_id);
	}
}
// 日付の短縮
if (preg_match("([0-2][0-9]{3}/[0-1][0-9]/[0-3][0-9])", $date_id)) {
	if ($nldr_ylr_d) { // オートリロード/スクロールの場合日付を全削除
		$date_id = preg_replace("([0-2][0-9]{3}/[0-1][0-9]/[0-3][0-9]\(..\))", "", $date_id);
	} else { // 上記以外は年を下2桁に
		if (preg_match("(class=\"ngword)", $date_id)) { // NGIDの時
			$date_id = preg_replace("(([0-2][0-9])([0-9]{2}/[0-1][0-9]/[0-3][0-9]\(..\)))", "$2", $date_id);
		} else {
			$date_id = preg_replace("(([0-2][0-9])([0-9]{2}/[0-1][0-9]/[0-3][0-9]\(..\)))", "$2", $date_id);
		}
	}
}

// メール
if ($mail) {
	// オートリロード/スクロールの場合
	if ($_conf['live.mail_sage'] 
	&& ($nldr_ylr_d)) {
		// sage を ▼ に
		if (preg_match("(^(\s|　)*sage(\s|　)*$)", $mail)) {
			if ($STYLE['read_mail_sage_color']) {
				$mail = "<span class=\"sage\" title=\"{$mail}\">▼</span>";
			} elseif ($STYLE['read_mail_color']) {
				$mail = "<span class=\"mail\" title=\"{$mail}\">▼</span>";
			} else {
				$mail = "<span title=\"{$mail}\">▼</span>";
			}
		// sage 以外を ● に
		} else {
			$mail = "<span class=\"mail\" title=\"{$mail}\">●</span>";
		}
	// ノーマル処理
	} elseif (preg_match("(^(\s|　)*sage(\s|　)*$)", $mail)
	&& $STYLE['read_mail_sage_color']) {
		$mail = "<span class=\"sage\">{$mail}</span>";
	} elseif ($STYLE['read_mail_color']) {
		$mail = "<span class=\"mail\">{$mail}</span>";
	} else {
		$mail = "{$mail}";
	}
}

// 被参照レスポップアップ
if ($_conf['live.ref_res']) {
	$url_res = "read.php?bbs={$this->thread->bbs}&key={$this->thread->key}&host={$this->thread->host}&ls=all&offline=1&field=msg&word=%28%3E%7C%81%84%29%28%5Cd%2B%2C%29*{$i}%5CD&method=regex&match=on&submit_filter=%83t%83B%83%8B%83%5E%95%5C%8E%A6";
	$ref_res_pp ="<a href=\"{$url_res}\" onmouseover=\"showHtmlPopUp('{$url_res},renzokupop=true',event,1)\" onmouseout=\"offHtmlPopUp()\" title=\"{$i} へのレスを表示\"><img src=\"img/pop.png\" alt=\"P\" width=\"12\" height=\"12\"></a>&nbsp;";
}

// レスの方法
if ($nldr_ylr_d) {
	$ttitle_en_q ="&amp;ttitle_en=".rawurlencode(base64_encode($this->thread->ttitle));
	// 内容をダブルクリック
	if ($_conf['live.res_button'] >= 1) {
		$res_dblclc = "ondblclick=\"window.parent.livepost.location.href='live_post_form.php?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;resnum={$i}{$ttitle_en_q}&amp;inyou=1'\" title=\"{$i} にレス (double click)\"";
	}
	// レスボタン
	if ($_conf['live.res_button'] <= 1) {
		$res_button = "<a href=\"live_post_form.php?host={$this->thread->host}&amp;bbs={$this->thread->bbs}&amp;key={$this->thread->key}&amp;resnum={$i}{$ttitle_en_q}&amp;inyou=1\" target=\"livepost\" title=\"{$i} にレス\"><img src =\"./img/re.png\" alt=\"Re:\" width=\"22\" height=\"12\"></a>";
	} 
}

// 内容
// 表示切詰め処理
if ($_conf['live.msg_a']
&& (preg_match("(<br>)", $msg))){
	$msg = mb_ereg_replace("(^\s((\s|　)*<br>(\s|　)*)+|((\s|　)*<br>(\s|　)*)+\s$)", " ", $msg);	// 文頭、文末の全改行を消去
	$msg = mb_ereg_replace("(((\s|　)*<br>(\s|　)*){3,})", " <br>  <br> ", $msg);					// 3連以上の改行を2連に
	if (mb_ereg_match("(((\s|　)*.(\s|　)*<br>){2,})", $msg)) {
		$msg = mb_ereg_replace("((\s|　)*<br>(\s|　)*)", " ", $msg);								// 3行以上の1文字置きの改行文から改行を削除
	}
}
// オートリロードの場合の表示切詰め処理
if ($_conf['live.msg_b']
&& ($nldr_ylr_d)) {
	$msg = mb_convert_kana($msg, 'rnas');								// 全角の英数、記号、スペースを半角に
	if (!preg_match ("(tp:/|ps:/|res/)", $msg)) {
		$msg = mb_ereg_replace("((\s|　)*<br>(\s|　)*)", " ", $msg);	// 全改行を消去し半角スペースに。内容に外部リンクや板別勢い一覧を含む場合は対象外
	}
	$msg = mb_ereg_replace("(\s{2,})", " ", $msg);						// 連続スペースを1つに
}

?>