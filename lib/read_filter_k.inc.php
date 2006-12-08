<?php
/**
 * p2 - 携帯でレスフィルタリングしたときのページ遷移用パラメータを設定する
 */

/**
 * ページ遷移用の基本URL(エスケープ済み)を生成する
 *
 * @param   object Thread $aThread  スレッドオブジェクト
 * @param   array $res_filter       フィルタリングのパラメータ
 * @return  string  ページ遷移用の基本URL
 */
function setFilterQuery($aThread, $res_filter)
{
    global $filter_q;
    $filter_q = '?host=' . $aThread->host . $bbs_q . $key_q . $offline_q;
    $filter_q .= '&amp;word=' . rawurlencode($_GET['word']);
    foreach ($res_filter as $key => $value) {
        $filter_q .= '&amp;' . rawurlencode($key) . '= ' . rawurlencode($value);
    }
    $filter_q .= '&amp;ls=all&amp;page=';
    return $filter_q;
}

// 自動設定
if (isset($aThread) && isset($res_filter)) {
    $GLOBALS['filter_q'] = setFilterQuery($aThread, $res_filter);
}

/**
 * ヘッダに表示するナビゲーション用の変数を書き換える
 *
 * @return  void
 */
function resetReadNaviHeaderK()
{
    $GLOBALS['prev_st'] = '前*';
    $GLOBALS['next_st'] = '次*';
    $GLOBALS['read_navi_previous'] = '';
    $GLOBALS['read_navi_next'] = '';
}

/**
 * フッタに表示するナビゲーション用の変数を書き換える
 *
 * @return  void
 */
function resetReadNaviFooterK()
{
    global $_conf;
    global $prev_st, $read_navi_previous_btm;
    global $next_st, $read_navi_next_btm;
    global $read_footer_navi_new_btm;
    global $filter_range, $filter_hits, $filter_page, $filter_q;

    if ($filter_page > 1) {
        $read_navi_previous_url = $_conf['read_php'] . $filter_q . ($filter_page - 1) . $_conf['k_at_a'];
        $read_navi_previous_btm = "<a {$_conf['accesskey']}=\"{$_conf['k_accesskey']['prev']}\" href=\"{$read_navi_previous_url}\">{$_conf['k_accesskey']['prev']}.{$prev_st}</a>";
    }

    if ($filter_range['to'] < $filter_hits) {
        $read_navi_next_url = $_conf['read_php'] . $filter_q . ($filter_page + 1) . $_conf['k_at_a'];
        $read_navi_next_btm = "<a {$_conf['accesskey']}=\"{$_conf['k_accesskey']['next']}\" href=\"{$read_navi_next_url}\">{$_conf['k_accesskey']['next']}.{$next_st}</a>";
    }

    $read_footer_navi_new_btm = str_replace(" {$_conf['accesskey']}=\"{$_conf['k_accesskey']['next']}\"", '', $read_footer_navi_new_btm);
    $read_footer_navi_new_btm = str_replace(">{$_conf['k_accesskey']['next']}.", '>', $read_footer_navi_new_btm);
}

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
