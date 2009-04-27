<?php
/**
 * フィルタ用にヘッダ変数を書き換える
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
 * フィルタ用に書き換えたフッタ変数を取得する
 * 
 * @return  array  array(read_navi_previous_btm, read_navi_next_btm, read_footer_navi_new_btm)
 */
function getResetReadNaviFooterK($aThread, $params)
{
    global $_conf;
    global $_filter_hits;

    // $prev_st, $next_st, $filter_range, $filter_page, $res_filter
    extract($params);
    
    // {{{ 検索クエリ

    $filter_qs = array(
        'detect_hint' => '◎◇',
        'host' => $aThread->host,
        'bbs'  => $aThread->bbs,
        'key'  => $aThread->key,
        'offline' => 1,
        'word' => $GLOBALS['word'],
        'ls'   => 'all',
        UA::getQueryKey() => UA::getQueryValue()
    );

    foreach ($res_filter as $key => $value) {
        $filter_qs[$key] = $value;
    }

    // }}}

    if ($filter_page > 1) {
        $qs = array_merge(
            $filter_qs,
            array('filter_page' => $filter_page - 1)
        );
        $read_navi_previous_url = P2Util::buildQueryUri($_conf['read_php'], $qs);
        $read_navi_previous_btm = sprintf(
            '<a %1$s="%2$s" href="%3$s">%2$s.%4$s</a>',
            hs($_conf['accesskey_for_k']),
            hs($_conf['k_accesskey']['prev']),
            hs($read_navi_previous_url),
            hs($prev_st)
        );
    }

    if ($filter_range['to'] < $_filter_hits) {
        $qs = array_merge(
            $filter_qs,
            array('filter_page' => $filter_page + 1)
        );
        $read_navi_next_url = P2Util::buildQueryUri($_conf['read_php'], $qs);
        $read_navi_next_btm = sprintf(
            '<a %1$s="%2$s" href="%3$s">%2$s.%4$s</a>',
            hs($_conf['accesskey_for_k']),
            hs($_conf['k_accesskey']['next']),
            hs($read_navi_next_url),
            hs($next_st)
        );
    }

    $read_footer_navi_new_btm_ht = '';
    /*
    // ※メモ：「6.新着」（新着レスの表示）と「3.新20」（最新N件）は異なる。
    $read_footer_navi_new_btm_ht = str_replace(
        " {$_conf['accesskey_for_k']}=\"{$_conf['k_accesskey']['next']}\"", '', $read_footer_navi_new_btm_ht
    );
    $read_footer_navi_new_btm_ht = str_replace(">{$_conf['k_accesskey']['next']}.", '>', $read_footer_navi_new_btm_ht);
    */
    
    return array(
        'read_navi_previous_btm'   => $read_navi_previous_btm,
        'read_navi_next_btm'       => $read_navi_next_btm,
        'read_footer_navi_new_btm' => $read_footer_navi_new_btm_ht
    );
}


/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
