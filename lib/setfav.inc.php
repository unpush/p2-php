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
 * お気にスレをセットする
 *
 * $set は、0(解除), 1(追加), top, up, down, bottom
 */
function setFav($host, $bbs, $key, $setfav)
{
    global $_conf;

    //==================================================================
    // key.idx
    //==================================================================
    // idxfileのパスを求めて
    $idx_host_dir = P2Util::idxDirOfHost($host);
    $idxfile = $idx_host_dir.'/'.$bbs.'/'.$key.'.idx';

    // 板ディレクトリが無ければ作る
    // FileCtl::mkdir_for($idxfile);

    // 既にidxデータがあるなら読み込む
    if ($lines = @file($idxfile)) {
        $l = rtrim($lines[0]);
        $data = explode('<>', $l);
    }

    // {{{ スレッド.idx 記録
    if ($setfav == '0' or $setfav == '1') {
        // お気にスレから外した結果、idxの意味がなくなれば削除する
        if ($setfav == '0' and (!$data[3] && !$data[4] && $data[9] <= 1)) {
            @unlink($idxfile);
        } else {
            $sar = array($data[0], $key, $data[2], $data[3], $data[4],
                        $data[5], $setfav, $data[7], $data[8], $data[9],
                        $data[10], $data[11], $data[12]);
            P2Util::recKeyIdx($idxfile, $sar);
        }
    }
    // }}}

    //==================================================================
    // favlist.idx
    //==================================================================
    // favlistファイルがなければ生成
    FileCtl::make_datafile($_conf['favlist_file'], $_conf['favlist_perm']);

    // favlist読み込み
    $favlines = @file($_conf['favlist_file']);

    //================================================
    // 処理
    //================================================
    $neolines = array();
    $before_line_num = 0;

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
            $cont .= $l."\n";
        }
    }

    // 書き込む
    if (FileCtl::file_write_contents($_conf['favlist_file'], $cont) === false) {
        die('Error: cannot write file.');
    }


    //================================================
    // お気にスレ共有
    //================================================
    if ($_conf['join_favrank']) {
        if ($setfav == "0") {
            $act = "out";
        } elseif ($setfav == "1") {
            $act = "add";
        } else {
            return;
        }
        $itaj = P2Util::getItaName($host, $bbs);
        $post = array("host" => $host, "bbs" => $bbs, "key" => $key, "ttitle" => $data[0], "ita" => $itaj, "act" => $act);
        postFavRank($post);
    }

    return true;
}

/**
 * お気にスレ共有でポストする
 */
function postFavRank($post)
{
    global $_conf;

    $method = "POST";
    $httpua_fmt = "Monazilla/1.00 (%s/%s; expack-%s)";
    $httpua = sprintf($httpua_fmt, $_conf['p2name'], $_conf['p2version'], $_conf['p2expack']);

    $URL = parse_url($_conf['favrank_url']); // URL分解
    if (isset($URL['query'])) { // クエリー
        $URL['query'] = "?".$URL['query'];
    } else {
        $URL['query'] = "";
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

    if (!$send_port) {$send_port = 80;} // デフォルトを80

    $request = $method." ".$send_path." HTTP/1.0\r\n";
    $request .= "Host: ".$URL['host']."\r\n";
    $request .= "User-Agent: ".$httpua."\r\n";
    $request .= "Connection: Close\r\n";

    /* POSTの時はヘッダを追加して末尾にURLエンコードしたデータを添付 */
    if (strtoupper($method) == "POST") {
        while (list($name, $value) = each($post)) {
            $POST[] = $name."=".urlencode($value);
        }
        $postdata = implode("&", $POST);
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-Length: ".strlen($postdata)."\r\n";
        $request .= "\r\n";
        $request .= $postdata;
    } else {
        $request .= "\r\n";
    }

    /* WEBサーバへ接続 */
    $fp = fsockopen($send_host, $send_port, $errno, $errstr, 3);
    if (!$fp) {
        //echo "サーバ接続エラー: $errstr ($errno)<br>\n";
        //echo "p2 info: {$_conf['favrank_url']} に接続できませんでした。<br>";
        return false;
    } else {
        fputs($fp, $request);
        /*
        while (!feof($fp)){
            if($start_here){
                echo $body = fread($fp,512000);
            }else{
                $l = fgets($fp,128000);
                if($l=="\r\n"){
                    $start_here=true;
                }
            }
        }
        */
        fclose ($fp);
        return true;
        //return $body;
    }
}

?>
