<?php
/*
    rep2expack - 拡張パック機能の On/Off とユーザ設定編集ページから変更させない設定

    このファイルの設定は、必要に応じて変更してください
*/

// ----------------------------------------------------------------------
// {{{ 全般

// ImageCache2 等でファイルをリモートから取得する際の User-Agent
$_conf['expack.user_agent'] = ""; // ("")

// }}}
// ----------------------------------------------------------------------
// {{{ ImageCache2

/*
 * この機能を使うにはPHPのGD機能拡張またはImageMagickと
 * SQLite, PostgreSQL, MySQLのいずれかが必要。
 * 利用に当たっては doc/ImageCache2/README.txt と doc/ImageCache2/INSTALL.txt を
 * よく読んで、それに従ってください。
 */

// ImageCache2（off:0, PCのみ:1, 携帯のみ:2, 両方:3）
$_conf['expack.ic2.enabled'] = 1; // (0)

// }}}
