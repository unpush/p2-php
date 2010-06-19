<?php
/*
    rep2+Wiki - ユーザ設定 デフォルト
    
    このファイルはデフォルト値の設定なので、特に変更する必要はありません
*/

// {{{ ■画像置換URL

// 画像置換URLのEXTRACTキャッシュ制御(初回キャッシュを使用:1, 指定のものは確認:2, 置換成功したURLは次回も確認:3, 毎回確認:0)
$conf_user_def['wiki.replaceimageurl.extract_cache'] = 1; // (1)
$conf_user_sel['wiki.replaceimageurl.extract_cache'] = array(
    '1' => '初回キャッシュを使用',
    '2' => '指定のものは確認',
    '3' => '置換成功したURLは次回も確認',
    '0' => '毎回確認',
);

// }}}

// {{{ ■IDサーチ

// スマートポップアップメニューでみみずんID検索をするか
$conf_user_def['wiki.idsearch.spm.mimizun.enabled'] = 1; // (1)
$conf_user_rad['wiki.idsearch.spm.mimizun.enabled'] = array('1' => 'する', '0' => 'しない');

// スマートポップアップメニューで必死チェッカーID検索をするか
$conf_user_def['wiki.idsearch.spm.hissi.enabled'] = 1; // (1)
$conf_user_rad['wiki.idsearch.spm.hissi.enabled'] = array('1' => 'する', '0' => 'しない');

// スマートポップアップメニューでIDストーカーID検索をするか
$conf_user_def['wiki.idsearch.spm.stalker.enabled'] = 0; // (0)
$conf_user_rad['wiki.idsearch.spm.stalker.enabled'] = array('1' => 'する', '0' => 'しない');

// }}}

// {{{ ■samba

// sambaタイマーを利用 (する:1, しない:0)
$conf_user_def['wiki.samba_timer'] = 0; // (0)
$conf_user_rad['wiki.samba_timer'] = array('1' => 'する', '0' => 'しない');
// sambaのキャッシュ時間
$conf_user_def['wiki.samba_cache'] = 24; // (24)
$conf_user_rules['wiki.samba_cache'] = array('emptyToDef', 'notIntExceptMinusToDef');

// }}}

// {{{ ■samba

// NGスレッドを有効にする (する:1, しない:0)
$conf_user_def['wiki.ng_thread'] = 0; // (0)
$conf_user_rad['wiki.ng_thread'] = array('1' => 'する', '0' => 'しない');
// 携帯閲覧時、レス番号にSPMをつける (つける:1, つけない:0)
$conf_user_def['wiki.spm.mobile'] = 0; // (0)
$conf_user_rad['wiki.spm.mobile'] = array('1' => 'する', '0' => 'しない');

// }}}
