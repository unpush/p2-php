<?php
/**
 * rep2expack - iPhone用ユーザ初期設定
 */

// {{{ subject-i (スレ一覧)

// 勢いを示すインジケーターを表示 (する:1, しない:0)
$conf_user_def['iphone.subject.indicate-speed'] = 0; // (0)
$conf_user_rad['iphone.subject.indicate-speed'] = array('1' => 'する', '0' => 'しない');

// インジケーターの幅 (pixels)
$conf_user_def['iphone.subject.speed.width'] = 10; // (10)
$conf_user_rules['iphone.subject.speed.width'] = array('notIntExceptMinusToDef');

// インジケーターの色 (1レス/日未満)
$conf_user_def['iphone.subject.speed.0rpd'] = "#eeeeee"; // ("#eeeeee")
$conf_user_rules['iphone.subject.speed.0rpd'] = array('notCssColorToDef');

// インジケーターの色 (1レス/日以上)
$conf_user_def['iphone.subject.speed.1rpd'] = "#ffcccc"; // ("#ffcccc")
$conf_user_rules['iphone.subject.speed.1rpd'] = array('notCssColorToDef');

// インジケーターの色 (10レス/日以上)
$conf_user_def['iphone.subject.speed.10rpd'] = "#ff9999"; // ("#ff9999")
$conf_user_rules['iphone.subject.speed.10rpd'] = array('notCssColorToDef');

// インジケーターの色 (100レス/日以上)
$conf_user_def['iphone.subject.speed.100rpd'] = "#ff6666"; // ("#ff6666")
$conf_user_rules['iphone.subject.speed.100rpd'] = array('notCssColorToDef');

// インジケーターの色 (1000レス/日以上)
$conf_user_def['iphone.subject.speed.1000rpd'] = "#ff3333"; // ("#ff3333")
$conf_user_rules['iphone.subject.speed.1000rpd'] = array('notCssColorToDef');

// インジケーターの色 (10000レス/日以上)
$conf_user_def['iphone.subject.speed.10000rpd'] = "#ff0000"; // ("#ff0000")
$conf_user_rules['iphone.subject.speed.10000rpd'] = array('notCssColorToDef');

// }}}
// {{{ read-i (スレ内容)


// }}}

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
