<?php
/*
    p2 - お気にスレ関係の処理スクリプト

    お気にスレの追加削除や、順序変更で呼ばれる

    2005/03/10 以前
    スレ個別idxでのお気に入りフラグは、現在は使用（機能）していない。
    お気にスレ情報は、favlist.idxでまとめて受け持つ。
    ↓
    2005/03/10
    スレッド表示時の負荷軽減を目的として、スレッド.idxでもお気にスレ情報を持つこととする。
    subjectでお気にスレ一覧表示 → favlist.idx を参照
    スレッド表示時のお気にスレ表示 → スレッド.idx を参照
*/

require_once P2_LIBRARY_DIR . '/filectl.class.php';

/**
 * お気にスレをセットする関数
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 *
 * @access  public
 * @return  boolean  実行成否
 */
function setFav($host, $bbs, $key, $setfav, $setnum = null)
{
    global $_conf, $__conf;

    //==================================================================
    // key.idx
    //==================================================================
    // idxfileのパスを求めて
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $idxfile = $idx_host_dir . '/' . $bbs . '/'.$key . '.idx';

    // 板ディレクトリが無ければ作る
    // FileCtl::mkdir_for($idxfile);

    // 既にidxデータがあるなら読み込む
    if (file_exists($idxfile) and $lines = file($idxfile)) {
        $l = rtrim($lines[0]);
        $data = explode('<>', $l);
    }

    /*
    // readnum
    if (!isset($data[4])) {
        $data[4] = 0;
    }
    if (!isset($data[9])) {
        $data[9] = $data[4] + 1; // $newlineは廃止予定だが、旧互換用に念のため
    }
    */

    // セット番号を検証
    if (!is_null($setnum) && $_conf['favlist_set_num'] > 0) {
        $setnum = (int)$setnum;
        if ($setnum < 0 || $_conf['favlist_set_num'] < $setnum) {
            return false;
        }
    } else {
        $setnum = 0;
    }

    // {{{ スレッド.idx 記録

    if ($setfav == '0' || $setfav == '1') {
        $favflag = ((int)$data[6] & PHP_INT_MAX);
        if ($setfav == '0') {
            $favflag &= ~(1 << $setnum);
        } else {
            $favflag |= (1 << $setnum);
        }
        $data[6] = (string)$favflag;
        // お気にスレから外した結果、idxの意味がなくなれば削除する
        if ($favflag == 0 and (!$data[3] && !$data[4] && $data[9] <= 1)) {
            @unlink($idxfile);
        } else {
            P2Util::recKeyIdx($idxfile, $data);
        }
    }

    // }}}

    //================================================
    // 処理
    //================================================
    $neolines = array();
    $before_line_num = 0;

    if ($setnum == 0) {
        $favlist_file = $__conf['favlist_file'];
    } else {
        $favlist_file = $_conf['pref_dir'] . sprintf('/p2_favlist%d.idx', $setnum);
    }

    // favlistファイルがなければ生成
    FileCtl::make_datafile($favlist_file, $_conf['favlist_perm']);

    // favlist読み込み
    $favlines = file($favlist_file);
    if ($favlines === false) {
        return false;
    }

    // 最初に重複要素を削除しておく
    if (!empty($favlines)) {
        $i = -1;
        foreach ($favlines as $line) {
            $i++;
            $line = rtrim($line);
            $lar = explode('<>', $line);
            // 重複回避
            if ($lar[1] == $key && $lar[11] == $bbs) {
                $before_line_num = $i; // 移動前の行番号をセット
                continue;
            // keyのないものは不正データなのでスキップ
            } elseif (!$lar[1]) {
                continue;
            } else {
                $neolines[] = $line;
            }
        }
    }

    // 記録データ設定
    if ($setfav) {
        $newdata = "$data[0]<>{$key}<>$data[2]<>$data[3]<>$data[4]<>$data[5]<>1<>$data[7]<>$data[8]<>$data[9]<>{$host}<>{$bbs}";
        include_once P2_LIBRARY_DIR . '/getsetposlines.inc.php';
        $rec_lines = getSetPosLines($neolines, $newdata, $before_line_num, $setfav);
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
    if (file_put_contents($_conf['favlist_file'], $cont, LOCK_EX) === false) {
        trigger_error("file_put_contents(" . $_conf['favlist_file'] . ")", E_USER_WARNING);
        die('Error: cannot write file.');
        return false;
    }


    // お気にスレ共有
    if ($_conf['join_favrank'] && $_conf['favlist_file'] == $__conf['favlist_file']) {
        $act = '';
        if ($setfav == "0") {
            $act = "out";
        } elseif ($setfav == "1") {
            $act = "add";
        }
        if ($act) {
            $itaj = P2Util::getItaName($host, $bbs);
            $post = array("host" => $host, "bbs" => $bbs, "key" => $key, "ttitle" => $data[0], "ita" => $itaj, "act" => $act);
            postFavRank($post);
        }
    }

    return true;
}

/**
 * お気にスレ共有でポストする関数
 *
 * @return  boolean  実行成否
 */
function postFavRank($post)
{
    global $_conf;

    $method = "POST";
    $httpua_fmt = "Monazilla/1.00 (%s/%s; expack-%s)";
    $httpua = sprintf($httpua_fmt, $_conf['p2name'], $_conf['p2version'], $_conf['p2expack']);

    $URL = parse_url($_conf['favrank_url']);
    if (isset($URL['query'])) {
        $URL['query'] = "?" . $URL['query'];
    } else {
        $URL['query'] = '';
    }

    // プロキシ
    if ($_conf['proxy_use']) {
        $send_host = $_conf['proxy_host'];
        $send_port = $_conf['proxy_port'];
        $send_path = $url;
    } else {
        $send_host = $URL['host'];
        $send_port = $URL['port'];
        $send_path = $URL['path'].$URL['query'];
    }

    if (!$send_port) {$send_port = 80;}

    $request = $method . " " . $send_path . " HTTP/1.0\r\n";
    $request .= "Host: " . $URL['host'] . "\r\n";
    $request .= "User-Agent: " . $httpua . "\r\n";
    $request .= "Connection: Close\r\n";

    // POSTの時はヘッダを追加して末尾にURLエンコードしたデータを添付
    if (strtoupper($method) == "POST") {
        while (list($name, $value) = each($post)) {
            $POST[] = $name . "=" . urlencode($value);
        }
        $postdata = implode("&", $POST);
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-Length: " . strlen($postdata) . "\r\n";
        $request .= "\r\n";
        $request .= $postdata;
    } else {
        $request .= "\r\n";
    }

    // WEBサーバへ接続
    $fp = fsockopen($send_host, $send_port, $errno, $errstr, 3);
    if (!$fp) {
        //echo "サーバ接続エラー: $errstr ($errno)<br>\n";
        //echo "p2 info: {$_conf['favrank_url']} に接続できませんでした。<br>";
        return false;
    }

    fputs($fp, $request);
    fclose($fp);

    return true;
    //return $body;
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
