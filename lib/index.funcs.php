<?php
// index用関数

/**
 * @access  public
 * @return  array
 */
function getIndexMenuKIni()
{
    global $_conf;
    
    // 2008/11/15 旧 $_conf['menuKIni']
    $indexMenuKIni = array(
        'recent_shinchaku'  => array(
            $_conf['subject_php'] . '?spmode=recent&sb_view=shinchaku',
            '最近読んだスレの新着'
        ),
        'recent'            => array(
            $_conf['subject_php'] . '?spmode=recent&norefresh=1',
            '最近読んだスレの全て'
        ),
        'fav_shinchaku'     => array(
            $_conf['subject_php'] . '?spmode=fav&sb_view=shinchaku',
            'お気にスレの新着'
        ),
        'fav'               => array(
            $_conf['subject_php'] . '?spmode=fav&norefresh=1',
            'お気にスレの全て'
        ),
        'favita'            => array(
            $_conf['menu_k_php'] . '?view=favita',
            'お気に板'
        ),
        'cate'              => array(
            $_conf['menu_k_php'] . '?view=cate',
            '板リスト'
        ),
        'res_hist'          => array(
            $_conf['subject_php'] . '?spmode=res_hist',
            '書込履歴'
        ),
        'palace'            => array(
            $_conf['subject_php'] . '?spmode=palace&norefresh=1',
            'スレの殿堂'
        ),
        'setting'           => array(
            'setting.php?dummy=1',
            'ログイン管理'
        ),
        'editpref'          => array(
            $_conf['editpref_php'] . '?dummy=1',
            '設定管理'
        )
    );
    
    // 携帯なら半角に変換
    if (UA::isK()) {
        foreach ($indexMenuKIni as $k => $v) {
            $indexMenuKIni[$k][1] = mb_convert_kana($indexMenuKIni[$k][1], 'rnsk');
        }
    }
    
    return $indexMenuKIni;
}

/**
 * indexメニュー項目のリンクHTML配列を取得する
 *
 * @access  public
 * @param   array   $menuKIni  メニュー項目 標準設定
 * @return  array
 */
function getIndexMenuKLinkHtmls($menuKIni, $noLink = false)
{
    global $_conf;
    
    $menuLinkHtmls = array();

    // ユーザ設定順序でメニューHTMLを取得
    foreach ($_conf['index_menu_k'] as $code) {
        if (isset($menuKIni[$code])) {
            if ($html = _getMenuKLinkHtml($code, $menuKIni, $noLink)) {
                $menuLinkHtmls[$code] = $html;
                unset($menuKIni[$code]);
            }
        }
    }
    if ($menuKIni) {
        foreach ($menuKIni as $code => $menu) {
            if ($html = _getMenuKLinkHtml($code, $menuKIni, $noLink)) {
                $menuLinkHtmls[$code] = $html;
                unset($menuKIni[$code]);
            }
        }
    }
    return $menuLinkHtmls;
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
