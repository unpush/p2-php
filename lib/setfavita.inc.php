<?php
// p2 -  お気に板の処理

require_once (P2_LIBRARY_DIR . '/filectl.class.php');

/**
 * お気に板をセットする
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 */
function setFavIta()
{
    global $_conf, $_info_msg_ht;

    if (isset($_GET['setfavita'])) {
        $setfavita = $_GET['setfavita'];
    } elseif (isset($_POST['setfavita'])) {
        $setfavita = $_POST['setfavita'];
    }

    $host = isset($_GET['host']) ? $_GET['host'] : NULL;
    $bbs = isset($_GET['bbs']) ? $_GET['bbs'] : NULL;

    if ($_POST['url']) {
        if (preg_match("/http:\/\/(.+)\/([^\/]+)\/([^\/]+\.html?)?/", $_POST['url'], $matches)) {
            $host = $matches[1];
            $host = preg_replace('{/test/read\.cgi$}', '', $host);
            $bbs = $matches[2];
        } else {
            $_info_msg_ht .= "<p>p2 info: 「{$_POST['url']}」は板のURLとして無効です。</p>";
        }
    }
    
    $list = $_POST['list'];
    
    if (!$host && !$bbs and (!($setfavita == 'submit' && $list))) {
        $_info_msg_ht .= "<p>p2 info: 板の指定が変です</p>";
        return false;
    }

    if (isset($_POST['itaj'])) {
        $itaj = $_POST['itaj'];
    }
    if (!isset($itaj) && isset($_GET['itaj_en'])) {
        $itaj = base64_decode($_GET['itaj_en']);
    } 
    if (empty($itaj)) { $itaj = $bbs; }

    //================================================
    // 読み込み
    //================================================
    //favita_pathファイルがなければ生成
    FileCtl::make_datafile($_conf['favita_path'], $_conf['favita_perm']);

    //favita_path読み込み;
    $lines = @file($_conf['favita_path']);

    //================================================
    // 処理
    //================================================
    $neolines = array();
    $before_line_num = 0;
    
    // 最初に重複要素を消去
    if (!empty($lines)) {
        $i = -1;
        foreach ($lines as $l) {
            $i++;
            $l = rtrim($l);
        
            // {{{ 旧データ（ver0.6.0以下）移行措置
            if (!preg_match("/^\t/", $l)) {
                $l = "\t" . $l;
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
    if ($setfavita == "submit" && $list) {
        $rec_lines = array();
        foreach (explode(',', $list) as $aList) {
            list($host, $bbs, $itaj_en) = explode('@', $aList);
            $rec_lines[] = "\t{$host}\t{$bbs}\t" . base64_decode($itaj_en);
        }
        
    } elseif ($setfavita and $host && $bbs && $itaj) {
        $newdata = "\t{$host}\t{$bbs}\t{$itaj}";
        include_once (P2_LIBRARY_DIR . '/getsetposlines.inc.php');
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
    if (FileCtl::file_write_contents($_conf['favita_path'], $cont) === false) {
        die('Error: cannot write file.');
    }
    
    return true;
}
?>
