<?php
/* vim: set fileencoding=cp932 autoindent noexpandtab ts=4 sw=4 sts=0: */
/* mi: charset=Shift_JIS */

// p2 - レス書き込みフォームの追加機能読み込み

/*
// {{{ 本文が空のときやsageてないときに送信しようとすると注意する

$onsubmit_ht = '';

if (!$_conf['ktai']) {
    if ($_exconf['editor']['check_message'] || $_exconf['editor']['check_sage']) {
        $_check_message = (int) $_exconf['editor']['check_message'];
        $_check_sage = (int) $_exconf['editor']['check_sage'];
        $onsubmit_ht = " onsubmit=\"return validateAll({$_check_message},{$_check_sage})\"";
    }
}

// }}}
*/

?>
