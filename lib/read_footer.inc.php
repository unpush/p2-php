<?php
/*
    p2 -  スレッド表示 -  フッタ部分 -  for read.php
*/

require_once P2_LIBRARY_DIR . '/dataphp.class.php';

//=====================================================================
// ■フッタ
//=====================================================================

if ($_conf['bottom_res_form']) {

    $bbs = $aThread->bbs;
    $key = $aThread->key;
    $host = $aThread->host;
    $rescount = $aThread->rescount;
    $ttitle_en = base64_encode($aThread->ttitle);

    $submit_value = '書き込む';

    $key_idx = $aThread->keyidx;

    // フォームのオプション読み込み
    include_once P2_LIBRARY_DIR . '/post_options_loader.inc.php';

// +live スタイル変更
    $htm['resform_ttitle'] = <<<EOP
<h3 class="thread_title">{$aThread->ttitle_hd}</h3>
EOP;

    include_once P2_LIBRARY_DIR . '/post_form.inc.php';

    // フォーム
    $res_form_ht = <<<EOP
<div id="kakiko">
{$htm['dpreview']}
{$htm['post_form']}
{$htm['dpreview2']}
</div>\n
EOP;

    $onmouse_showform_ht = <<<EOP
 onMouseover="document.getElementById('kakiko').style.display = 'block';"
EOP;

}

// ============================================================
$sid_q = (defined('SID')) ? '&amp;'.strip_tags(SID) : '';

if ($aThread->rescount or ($_GET['one'] && !$aThread->diedat)) { // and (!$_GET['renzokupop'])

    if (!$aThread->diedat) {
        if (!empty($_conf['disable_res'])) {
            $htm['dores'] = <<<EOP
<a href="{$motothre_url}" target="_blank">{$dores_st}</a>
EOP;
        } else {
			// +live リンク切替
			// スレ立てからの日数による処理
			$thr_birth = date("U", $aThread->key);
			
			if ($_conf['live.time_lag'] != 0) {
				$thr_time_lag = $_conf['live.time_lag'] * 86400;
			} else {
				$thr_time_lag = 365 * 86400;
			}
			
			if (!preg_match("({$aThread->bbs})", $_conf['live.default_reload'])
			&& (preg_match("({$aThread->bbs}|{$aThread->host})", $_conf['live.reload']) || $_conf['live.reload'] == all)
			&& (date("U") < $thr_birth + $thr_time_lag)) {
				$htm['dores'] = <<<LIVE
				<a href="live_post_form.php?host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}" target='livepost'>{$dores_st}</a>
LIVE;
			} else {
            $htm['dores'] = <<<EOP
<a href="post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}" target="_self" onClick="return OpenSubWin('post_form.php?host={$aThread->host}{$bbs_q}{$key_q}&amp;rescount={$aThread->rescount}{$ttitle_en_q}&amp;popup=1{$sid_q}',{$STYLE['post_pop_size']},1,0)"{$onmouse_showform_ht}>{$dores_st}</a>
EOP;
			}
        }

        $res_form_ht_pb = $res_form_ht;
    }

    if ($res1['body']) {
        $q_ichi = $res1['body']." | ";
    }

    // レスのすばやさ
    $htm['spd'] = '';
    if ($spd_st = $aThread->getTimePerRes() and $spd_st != '-') {
        $htm['spd'] = '<span class="spd" title="すばやさ＝時間/レス">' . $spd_st . '</span>';
    }

    // datサイズ
    $htm['dsize'] = '';
    if ($dsize_ht = @filesize($aThread->keydat)) {
        $htm['dsize'] = sprintf('<span class="spd" title="%s">%01.1fKB</span> |', 'datサイズ', $dsize_ht / 1024);
    }

    // レス番指定移動
    $htm['goto'] = <<<GOTO
            <form method="get" action="{$_conf['read_php']}" class="inline-form">
                <input type="hidden" name="host" value="{$aThread->host}">
                <input type="hidden" name="bbs" value="{$aThread->bbs}">
                <input type="hidden" name="key" value="{$aThread->key}">
                <input type="text" size="5" name="ls" value="{$aThread->ls}">
                <input type="submit" value="go">
            </form>
GOTO;

    // {{{ フィルタヒットがあった場合、次Xと続きを読むを更新
    /*
    //if (!$read_navi_next_isInvisible) {
    $read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";
    //}

    $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$aThread->resrange['to']}-{$offline_q}\" accesskey=\"r\">{$tuduki_st}</a>";
    */

    if (!empty($GLOBALS['last_hit_resnum'])) {
        $read_navi_next_anchor = "";
        if ($GLOBALS['last_hit_resnum'] == $aThread->rescount) {
            $read_navi_next_anchor = "#r{$aThread->rescount}";
        }
        $after_rnum = $GLOBALS['last_hit_resnum'] + $rnum_range;
        $read_navi_next = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$GLOBALS['last_hit_resnum']}-{$after_rnum}{$offline_range_q}&amp;nt={$newtime}{$read_navi_next_anchor}\">{$next_st}{$rnum_range}</a>";

        // 「続きを読む」
        $read_footer_navi_new = "<a href=\"{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls={$GLOBALS['last_hit_resnum']}-{$offline_q}\" accesskey=\"r\">{$tuduki_st}</a>";
    }
    // }}}

    // ■プリント
    echo <<<EOP
<hr>
<table id="footer" width="100%" style="padding:0px 10px 0px 0px;">
    <tr>
        <td align="left">
            {$q_ichi}
            <a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=all">{$all_st}</a>
            {$read_navi_previous}
            {$read_navi_next}
            <a href="{$_conf['read_php']}?host={$aThread->host}{$bbs_q}{$key_q}&amp;ls=l{$latest_show_res_num}">{$latest_st}{$latest_show_res_num}</a>
            {$htm['goto']}
            | {$read_footer_navi_new}
            | {$htm['dores']}
            {$htm['dsize']}
            {$htm['spd']}
        </td>
        <td align="right">
            {$htm['p2frame']}
            {$toolbar_right_ht}
        </td>
        <td align="right">
            <a href="#header">▲</a>
        </td>
    </tr>
</table>
{$res_form_ht_pb}
EOP;

    if ($diedat_msg) {
        echo "<hr>";
        echo $diedat_msg;
        echo "<p>";
        echo $motothre_ht;
        echo "</p>";
    }
}

if (!empty($_GET['showres'])) {
    echo <<<EOP
    <script type="text/javascript">
    <!--
    document.getElementById('kakiko').style.display = 'block';
    //-->
    </script>\n
EOP;
}

// +live 表示切替スクリプト
if (!preg_match("({$aThread->bbs})", $_conf['live.default_reload'])
&& (preg_match("({$aThread->bbs}|{$aThread->host})", $_conf['live.reload']) || $_conf['live.reload'] == all)) {
	if ($_GET['live'] && !$_GET['word']) {
		echo "";
	} else {
		echo <<<LIVE
		<script type="text/javascript">
		<!--
		function startlive() {
			window.location.replace("./live_read.php?host={$aThread->host}&bbs={$aThread->bbs}&key={$aThread->key}&live=1");
		}
		
		parent.livecontrol.liveoff();
		//-->
		</script>\n
LIVE;
	}
}

// ====
echo '</body></html>';

?>
