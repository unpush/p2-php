<?php
/**
 * rep2 - ホスト単位でのアクセス許可/拒否の設定ファイル
 *
 * このファイルの設定は、必要に応じて変更してください
 */

$GLOBALS['_HOSTCHKCONF'] = array();

// ホストごとの設定 (0:拒否; 1:許可;)
// $_conf['secure']['auth_host'] == 0 のとき、当然ながら無効。
// $_conf['secure']['auth_host'] == 1 のとき、値が1（真）のホストのみ許可。
// $_conf['secure']['auth_host'] == 2 のとき、値が0（偽）のホストのみ拒否。
$GLOBALS['_HOSTCHKCONF']['host_type'] = array(
    // p2が動作しているマシン
    'localhost' => 1,
    // クラスA-Cのプライベートアドレス
    'private'   => 1,
    // NTT docomo iモード
    'docomo'    => 0,
    // au EZweb
    'au'        => 0,
    // SoftBank Mobile
    'softbank'  => 0,
    // WILLCOM AIR-EDGE
    'willcom'   => 0,
    // EMOBILE
    'emobile'   => 0,
    // iPhone 3G
    'iphone'    => 0,
    // jigブラウザ・jigブラウザWEB・jigアプリ・jigWEB
    'jig'       => 0,
    // ibisBrowserDX
    'ibis'      => 0,
    // ユーザー設定
    'custom'    => 0,
    // ユーザー設定 (IPv6)
    'custom_v6' => 0,
);

// 各携帯キャリアのIPアドレス帯域検証に失敗した際、
// 正規表現でリモートホストの検証をする。
$GLOBALS['_HOSTCHKCONF']['mobile_use_regex'] = false;

// アクセスを許可するIPアドレス帯域
// “IPアドレス => マスク”形式の連想配列
// $_conf['secure']['auth_host'] == 1 かつ
// $GLOBALS['_HOSTCHKCONF']['host_type']['custom'] = 1 のとき使われる
$GLOBALS['_HOSTCHKCONF']['custom_allowed_host'] = array(
    //'192.168.0.0' => 24,
    //'210.143.108.0' => 24, // jig
    //'117.55.0.0' => 17,   // emb? @link http://pc11.2ch.net/test/read.cgi/software/1216565984/531
    //'60.254.192.0' => 18, // 同上
    //'119.72.0.0' => 16,   // 同上
);
$GLOBALS['_HOSTCHKCONF']['custom_allowed_host_v6'] = null;

// アクセスを許可するリモートホストの正規表現
// preg_match()関数の第一引数として正しい文字列であること
// 使用しない場合はnull
// $_conf['secure']['auth_host'] == 1 かつ
// $GLOBALS['_HOSTCHKCONF']['host_type']['custom'] = 1 のとき使われる
$GLOBALS['_HOSTCHKCONF']['custom_allowed_host_regex'] = null;

// アクセスを拒否するIPアドレス帯域
// “IPアドレス => マスク”形式の連想配列
// $_conf['secure']['auth_host'] == 2 かつ
// $GLOBALS['_HOSTCHKCONF']['host_type']['custom'] = 0 のとき使われる
$GLOBALS['_HOSTCHKCONF']['custom_denied_host'] = array(
    //'192.168.0.0' => 24,
);
$GLOBALS['_HOSTCHKCONF']['custom_denied_host_v6'] = null;

// アクセスを拒否するリモートホストの正規表現
// preg_match()関数の第一引数として正しい文字列であること
// 使用しない場合はnull
// $_conf['secure']['auth_host'] == 2 かつ
// $GLOBALS['_HOSTCHKCONF']['host_type']['custom'] = 0 のとき使われる
$GLOBALS['_HOSTCHKCONF']['custom_denied_host_regex'] = null;

// gethostbyaddr(), gethostbyname() キャッシュの有効期限 (秒数で指定、0なら毎回確認)
$GLOBALS['_HOSTCHKCONF']['gethostby_lifetime'] = 3600;

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
