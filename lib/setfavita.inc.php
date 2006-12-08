<?php
require_once P2_LIBRARY_DIR . '/filectl.class.php';

/**
 * お気に板をセットする関数
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 *
 * @access  public
 * @return  boolean  実行成否
 */
function setFavIta()
{
    global $_conf, $_info_msg_ht;

    // {{{ パラメータの設定

    if (isset($_GET['setfavita'])) {
        $setfavita = $_GET['setfavita'];
    } elseif (isset($_POST['setfavita'])) {
        $setfavita = $_POST['setfavita'];
    }

    $host = isset($_GET['host']) ? $_GET['host'] : NULL;
    $bbs  = isset($_GET['bbs'])  ? $_GET['bbs']  : NULL;

    if (!empty($_POST['url'])) {
        if (preg_match("/http:\/\/(.+)\/([^\/]+)\/([^\/]+\.html?)?/", $_POST['url'], $matches)) {
            $host = preg_replace('{/test/read\.cgi$}', '', $matches[1]);
            $bbs = $matches[2];
        } else {
            $_info_msg_ht .= "<p>p2 info: 「{$_POST['url']}」は板のURLとして無効です。</p>";
        }
    }

    $list = isset($_POST['list']) ? $_POST['list'] : '';

    // リストで並び替え
    if (!empty($_POST['submit_listfavita'])) {
        if (!$list) {
            P2Util::pushInfoMsgHtml("<p>p2 info: リストの指定が変です</p>");
            return false;
        }

    // 新規追加 or 一つずつ並び替え
    } elseif (!$host || !$bbs) {
        P2Util::pushInfoMsgHtml("<p>p2 info: 板の指定が変です</p>");
        return false;
    }

    $itaj = isset($_POST['itaj']) ? $_POST['itaj'] : '';

    if (!$itaj && isset($_GET['itaj_en'])) {
        $itaj = base64_decode($_GET['itaj_en']);
    }
    !$itaj and $itaj = $bbs;

    // }}}

    //================================================
    // 処理
    //================================================
    FileCtl::make_datafile($_conf['favita_path'], $_conf['favita_perm']);

    $lines = file($_conf['favita_path']);
    if ($lines === false) {
        return false;
    }

    $neolines = array();
    $before_line_num = 0;

    // 最初に重複要素を消去しておく
    if ($lines) {
        $i = -1;
        foreach ($lines as $l) {
            $i++;
            $l = rtrim($l);

            // {{{ 旧データ（ver0.6.0以下）移行措置
            if (!preg_match("/^\t/", $l)) {
                $l = "\t".$l;
            }
            // }}}

            $lar = explode("\t", $l);

            if ($lar[1] == $host and $lar[2] == $bbs) { // 重複回避
                $before_line_num = $i;
                continue;
            } elseif (!$lar[1] || !$lar[2]) { // 不正データ（host, bbsなし）もアウト
                continue;
            } else {
                $neolines[] = $l;
            }
        }
    }

    // 記録データ設定

    // リスト丸ごとポストして指定
    if (!empty($_POST['submit_listfavita']) && $list) {
        $rec_lines = array();
        foreach (explode(',', $list) as $aList) {
            list($host, $bbs, $itaj_en) = explode('@', $aList);
            $rec_lines[] = "\t{$host}\t{$bbs}\t" . base64_decode($itaj_en);
        }
        $_info_msg_ht .= "<script language=\"javascript\">
            if (parent.menu) { parent.menu.location.href='{$_conf['menu_php']}?nr=1'; }</script>";

    // 一つのデータを指定して操作
    } elseif ($setfavita and $host && $bbs && $itaj) {
        $newdata = "\t{$host}\t{$bbs}\t{$itaj}";
        include_once P2_LIBRARY_DIR . '/getsetposlines.inc.php';
        $rec_lines = getSetPosLines($neolines, $newdata, $before_line_num, $setfavita);

    // 解除
    } else {
        $rec_lines = $neolines;
    }

    $cont = '';
    if (!empty($rec_lines)) {
        foreach ($rec_lines as $l) {
            $cont .= $l . "\n";
        }
    }

    // 書き込む
    if (file_put_contents($_conf['favita_path'], $cont, LOCK_EX) === false) {
        trigger_error("file_put_contents(" . $_conf['favita_path'] . ")", E_USER_WARNING);
        die('Error: cannot write file.');
        return false;
    }

    return true;
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * mode: php
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
