<?php
/*
    rep2+Wiki - ユーザ設定 デフォルト
    
    このファイルはデフォルト値の設定なので、特に変更する必要はありません
*/

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
