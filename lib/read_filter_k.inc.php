<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

// 検索クエリ
$_conf['filter_q'] = '?host=' . $aThread->host . $bbs_q . $key_q . $offline_q;
$_conf['filter_q'] .= '&amp;word=' . rawurlencode($_GET['word']);
foreach ($res_filter as $key => $value) {
    $_conf['filter_q'] .= "&amp;{$key}={$value}";
}
$_conf['filter_q'] .= '&amp;ls=all&amp;page=';

/**
 * ヘッダ変数を書き換える
 */
function resetReadNaviHeaderK()
{
    $GLOBALS['prev_st'] = '前*';
    $GLOBALS['next_st'] = '次*';
    $GLOBALS['read_navi_previous'] = '';
    $GLOBALS['read_navi_next'] = '';
}

/**
 * フッタ変数を書き換える
 */
function resetReadNaviFooterK()
{
    global $_conf;
    global $prev_st, $read_navi_previous_btm;
    global $next_st, $read_navi_next_btm;
    global $read_footer_navi_new_btm;
    global $filter_range, $filter_hits, $page;

    if ($page > 1) {
        $read_navi_previous_url = $_conf['read_php'] . $_conf['filter_q'] . ($page - 1);
        $read_navi_previous_btm = "<a {$_conf['accesskey']}=\"{$_conf['k_accesskey']['prev']}\" href=\"{$read_navi_previous_url}\">{$_conf['k_accesskey']['prev']}.{$prev_st}</a>";
    }

    if ($filter_range['to'] < $filter_hits) {
        $read_navi_next_url = $_conf['read_php'] . $_conf['filter_q'] . ($page + 1);
        $read_navi_next_btm = "<a {$_conf['accesskey']}=\"{$_conf['k_accesskey']['next']}\" href=\"{$read_navi_next_url}\">{$_conf['k_accesskey']['next']}.{$next_st}</a>";
    }

    $read_footer_navi_new_btm = str_replace(" {$_conf['accesskey']}=\"{$_conf['k_accesskey']['next']}\"", '', $read_footer_navi_new_btm);
    $read_footer_navi_new_btm = str_replace(">{$_conf['k_accesskey']['next']}.", '>', $read_footer_navi_new_btm);
}

?>
