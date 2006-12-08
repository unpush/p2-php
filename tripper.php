<?php
/* トリップ・メーカー */

include_once './conf/conf.inc.php';

$_login->authorize(); // ユーザ認証

echo P2Util::mkTrip($_GET['tk']);


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * mode: php
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
