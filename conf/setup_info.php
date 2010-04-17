<?php
/**
 * rep2expack - 環境チェックで使用する変数
 */

// 必須バージョン
$p2_required_version_5_2 = '5.2.8';
$p2_required_version_5_3 = '5.3.0';

// 推奨バージョン
$p2_recommended_version_5_2 = '5.2.12';
$p2_recommended_version_5_3 = '5.3.1';

// 必須拡張モジュール
$p2_required_extensions = array(
    'dom',
    'json',
    'libxml',
    'mbstring',
    'pcre',
    'pdo',
    'pdo_sqlite',
    'session',
    'spl',
    //'xsl',
    'zlib',
);

// 有効だと動作しないphp.iniディレクティブ
$p2_incompatible_ini_directives = array(
    'safe_mode',
    'register_globals',
    'magic_quotes_gpc',
    'mbstring.encoding_translation',
    'session.auto_start',
);

// 移行スクリプトの実行が必要な変更のあったバージョン番号の配列
// 値は "yymmdd.hhmm" 形式でユニークかつ昇順にソートされていなければならない
$p2_changed_versions = array(
    '100113.1300',
    '100120.0700',
);

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
