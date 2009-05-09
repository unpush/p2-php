<?php
// p2 -  サブジェクト -  ツールバー表示（携帯）
// for subject.php

//========================================================================
// 関数
//========================================================================
/**
 * 新着まとめ読み <a>
 *
 * @return  string  HTML
 */
function getShinchakuMatomeATag($aThreadList, $shinchaku_num)
{
    global $_conf;
    static $upper_toolbar_done_;
    
    $shinchaku_matome_atag = '';
    
    // 倉庫なら新着まとめのリンクはなし
    if ($aThreadList->spmode == 'soko') {
        return $shinchaku_matome_atag = '';
    }
    
    $attrs = array();
    
    if (UA::isIPhoneGroup()) {
        $attrs['class'] = 'button';
    }
    
    // 上下あるツールバーの下だけにアクセスキーをつける
    if (!empty($upper_toolbar_done_)) {
        $attrs[$_conf['accesskey_for_k']] = $_conf['k_accesskey']['matome'];
    }
    $upper_toolbar_done_ = true;
    
    $qs = array(
        'host'   => $aThreadList->host,
        'bbs'    => $aThreadList->bbs,
        'spmode' => $aThreadList->spmode,
        'nt'     => date('gis'),
        UA::getQueryKey() => UA::getQueryValue()
    );
    $label = "{$_conf['k_accesskey']['matome']}.新まとめ";
    
    if ($shinchaku_num) {
        $shinchaku_matome_atag = P2View::tagA(
            P2Util::buildQueryUri(
                $_conf['read_new_k_php'],
                array_merge($qs, array('norefresh' => '1'))
            ),
            hs("$label({$shinchaku_num})"),
            $attrs
        );
    
    } else {
        $shinchaku_matome_atag = P2View::tagA(
            P2Util::buildQueryUri(
                $_conf['read_new_k_php'],
                $qs
            ),
            hs($label),
            $attrs
        );
    }
    
    return $shinchaku_matome_atag;
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
