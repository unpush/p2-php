<?php
/*
	p2 - サブジェクト - フッタ表示
	for subject.php
*/

$aTags = array();

// dat倉庫 <a>
$datSokoATag = _getDatSokoATag($aThreadList);
$datSokoATag and $aTags[] = $datSokoATag;

// あぼーん中のスレッド <a>
$tabornATag = _getTabornATag($aThreadList);
$tabornATag and $aTags[] = $tabornATag;

// 新規スレッド作成 <a>
$buildnewthreadATag = _getBuildnewthreadATag($aThreadList);
$buildnewthreadATag and $aTags[] = $buildnewthreadATag;

//================================================================
// HTMLプリント
//================================================================

?></table><?php

// チェックフォーム
echo $check_form_ht;

// フォームフッタ
?>
		<input type="hidden" name="host" value="<?php eh($aThreadList->host); ?>">
		<input type="hidden" name="bbs" value="<?php eh($aThreadList->bbs); ?>">
		<input type="hidden" name="spmode" value="<?php eh($aThreadList->spmode); ?>">
	</form>
<?php
	
// sbject ツールバー
include P2_LIB_DIR . '/sb_toolbar.inc.php';

?><p><?php
echo implode(' | ', $aTags);
?></p><?php

// スペシャルモードでなければフォーム入力を補完
$ini_url_text = '';
if (!$aThreadList->spmode) {
    // したらば
	if (P2Util::isHostJbbsShitaraba($aThreadList->host)) {
		$ini_url_text = "http://{$aThreadList->host}/bbs/read.cgi?BBS={$aThreadList->bbs}&KEY=";
    // まちBBS
	} elseif (P2Util::isHostMachiBbs($aThreadList->host)) {
		$ini_url_text = "http://{$aThreadList->host}/bbs/read.cgi?BBS={$aThreadList->bbs}&KEY=";
    // まちビねっと
	} elseif (P2Util::isHostMachiBbsNet($aThreadList->host)) {
		$ini_url_text = "http://{$aThreadList->host}/test/read.cgi?bbs={$aThreadList->bbs}&key=";
	} else {
		$ini_url_text = "http://{$aThreadList->host}/test/read.cgi/{$aThreadList->bbs}/";
	}
}

// if (!$aThreadList->spmode || $aThreadList->spmode=="fav" || $aThreadList->spmode=="recent" || $aThreadList->spmode=="res_hist") {

$onClick_ht = <<<EOP
var url_v=document.forms["urlform"].elements["url_text"].value;
if (url_v=="" || url_v=="{$ini_url_text}") {
	alert("見たいスレッドのURLを入力して下さい。 例：http://pc.2ch.net/test/read.cgi/mac/1034199997/");
	return false;
}
EOP;

echo <<<EOP
	<form id="urlform" method="GET" action="{$_conf['read_php']}" target="read">
			2chのスレURLを直接指定
			<input id="url_text" type="text" value="{$ini_url_text}" name="url" size="62">
			<input type="submit" name="btnG" value="表示" onClick='{$onClick_ht}'>
	</form>\n
EOP;

//}

?>
</body></html>
<?php


//====================================================================
// 関数（このファイル内でのみ利用）
//====================================================================
/**
 * dat倉庫 <a>
 *
 * @return  string  HTML
 */
function _getDatSokoATag($aThreadList)
{
    global $_conf;
    
    $datSokoATag = '';
    // スペシャルモードでなければ、またはあぼーんリストなら
    if (!$aThreadList->spmode or $aThreadList->spmode == 'taborn') {
        $datSokoATag = P2View::tagA(
            P2Util::buildQueryUri(
                $_conf['subject_php'],
                array(
                    'host'   => $aThreadList->host,
                    'bbs'    => $aThreadList->bbs,
                    'norefresh' => '1',
                    'spmode' => 'soko',
                    UA::getQueryKey() => UA::getQueryValue()
                )
            ),
            'dat倉庫',
            array(
                'target' => '_self',
                'title'  => 'dat落ちしたスレ'
            )
        );
    }
    return $datSokoATag;
}

/**
 * あぼーん中のスレッド <a>
 *
 * @return  string  HTML
 */
function _getTabornATag($aThreadList)
{
    global $_conf, $ta_num;
    
    $taborn_link_atag = '';
    if (!empty($ta_num)) {
        $taborn_link_atag = P2View::tagA(
            P2Util::buildQueryUri(
                $_conf['subject_php'],
                array(
                    'host'   => $aThreadList->host,
                    'bbs'    => $aThreadList->bbs,
                    'norefresh' => '1',
                    'spmode' => 'taborn',
                    UA::getQueryKey() => UA::getQueryValue()
                )
            ),
            "あぼーん中のスレッド ({$ta_num})",
            array(
                'target' => '_self'
            )
        );
    }
    return $taborn_link_atag;
}

/**
 * 新規スレッド作成 <a>
 *
 * @return  string  HTML
 */
function _getBuildnewthreadATag($aThreadList)
{
    global $STYLE;
    
    $buildnewthreadATag = '';
    if (!$aThreadList->spmode and !P2Util::isHostKossoriEnq($aThreadList->host)) {
        $qs = array(
            'host'   => $aThreadList->host,
            'bbs'    => $aThreadList->bbs,
            'newthread' => '1',
            UA::getQueryKey() => UA::getQueryValue()
        );
        if (defined('SID') && strlen(SID)) {
            $qs[session_name()] = session_id();
        }
        $onClickUri = P2Util::buildQueryUri('post_form.php', array_merge($qs, array('popup' => '1')));
        $buildnewthreadATag = P2View::tagA(
            P2Util::buildQueryUri('post_form.php', $qs),
            '新規スレッド作成',
            array(
                'onClick' => sprintf(
                    "return !openSubWin('%s',%s,1,0)",
                    str_replace("'", "\\'", $onClickUri), $STYLE['post_pop_size']
                ),
                'target' => '_self'
            )
        );
    }
    return $buildnewthreadATag;
}
