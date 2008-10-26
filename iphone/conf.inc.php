<?php
/*
    rep2 - iPhone用基本設定ファイル

	>>11氏のを参考に作成。11氏敬礼。
*/
define('P2_IPHONE_LIB_DIR', './iphone');

$_conf['ktai']           = true;
$_conf['subject_php']    = "subject_i.php";
$_conf['read_php']       = "read_i.php";
$_conf['read_new_k_php'] = 'read_new_i.php';

$_conf['menuKIni'] = array(
    'recent_shinchaku'  => array(
        'subject_i.php?spmode=recent&sb_view=shinchaku',
        '最近読んだスレの新着'
    ),
    'recent'            => array(
        'subject_i.php?spmode=recent&norefresh=1',
        '最近読んだスレの全て'
    ),
    'fav_shinchaku'     => array(
        'subject_i.php?spmode=fav&sb_view=shinchaku',
        'お気にスレの新着'
    ),
    'fav'               => array(
        'subject_i.php?spmode=fav&norefresh=1',
        'お気にスレの全て'
    ),
    'favita'            => array(
        'menu_i.php?view=favita',
        'お気に板'
    ),
    'cate'              => array(
        'menu_i.php?view=cate',
        '板リスト'
    ),
    'res_hist'          => array(
        'subject_i.php?spmode=res_hist',
        '書込履歴'
    ),
    'palace'            => array(
        'subject_i.php?spmode=palace&norefresh=1',
        'スレの殿堂'
    ),
    'setting'           => array(
        'setting.php?dummy=1',
        'ログイン管理'
    ),
    'editpref'          => array(
        'editpref_i.php?dummy=1',
        '設定管理'
    )
);
