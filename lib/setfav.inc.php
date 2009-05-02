<?php
/*
    p2 - お気にスレ関係の処理スクリプト

    お気にスレの追加削除や、順序変更で呼ばれる
    
    2005/03/10
    スレッド表示時の負荷軽減を目的として、（たいした負荷ではない気もするが）
    スレッド.idxでもお気にスレ情報を持つこととする。
    subjectでお気にスレ一覧表示 → favlist.idx を参照
    スレッド表示時のお気にスレ表示 → スレッド.idx を参照
*/

require_once P2_LIB_DIR . '/FileCtl.php';
require_once P2_LIB_DIR . '/P2Validate.php';

/**
 * お気にスレをセットする関数
 *
 * $setfav は、'0'(解除), '1'(追加), 'top', 'up', 'down', 'bottom'
 *
 * @access  public
 * @return  boolean
 */
function setFav($host, $bbs, $key, $setfav)
{
    global $_conf;

    if (P2Validate::host($host) || P2Validate::bbs($bbs) || P2Validate::key($key)) {
        return false;
    }

    // スレッド.idx 記録
    $data = _setFavToKeyIdx($host, $bbs, $key, $setfav);

    $newlines = array();
    $before_line_num = 0;

    if (false === FileCtl::make_datafile($_conf['favlist_file'], $_conf['favlist_perm'])) {
        return false;
    }

    if (false === $favlines = file($_conf['favlist_file'])) {
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
            } elseif (P2Validate::host($lar[10]) || P2Validate::bbs($lar[11]) || P2Validate::key($lar[1])) {
                continue;
            } else {
                $newlines[] = $line;
            }
        }
    }

    if (!empty($GLOBALS['brazil'])) {
        //$newlines = _removeLargeFavlistData($newlines);
    }
    
    // 記録データ設定
    if ($setfav) {
        $newdata = implode('<>', array(
            geti($data[0]), $key, geti($data[2]), geti($data[3]), geti($data[4]), geti($data[5]),
            1, geti($data[7]), geti($data[8]), geti($data[9]), $host, $bbs
        ));
        require_once P2_LIB_DIR . '/getSetPosLines.func.php';
        $rec_lines = getSetPosLines($newlines, $newdata, $before_line_num, $setfav);
    } else {
        $rec_lines = $newlines;
    }
    
    if (false === file_put_contents(
            $_conf['favlist_file'],
            $rec_lines ? implode("\n", $rec_lines) . "\n" : '',
            LOCK_EX
        )
    ) {
        trigger_error(
            sprintf('file_put_contents(%s)', $_conf['favlist_file']),
            E_USER_WARNING
        );
        return false;
    }

    // お気にスレ共有
    _setFavRank($host, $bbs, $key, $setfav, geti($data[0]));
    
    return true;
}

/**
 * お気にスレの登録数超過データを削除
 *
 * @return  void
 */
function _removeLargeFavlistData($newlines, $max = 500)
{
    if ($removelines = array_slice($newlines, $max)) {
        for ($i = 0; $i < count($removelines); $i++) {
            $d = explode('<>', $removelines[$i]);
            _setFavToKeyIdx($d[10], $d[11], $d[1], '0');
        }
        
        // 調査ログ用
        if (count($removelines) > 1) {
            trigger_error(
                sprintf("%s() %d", __FUNCTION__, count($newlines)),
                E_USER_WARNING
            );
        }
    }
    
    return array_slice($newlines, 0, $max);
}

/**
 * @return  array  読み込んだkeyデータ
 */
function _setFavToKeyIdx($host, $bbs, $key, $setfav)
{
    $idxfile = P2Util::getKeyIdxFilePath($host, $bbs, $key);
    
    // FileCtl::mkdirFor($idxfile);

    $data = array();
    
    // 既にidxデータがあるなら読み込む
    if (file_exists($idxfile) and $lines = file($idxfile)) {
        $l = rtrim($lines[0]);
        $data = explode('<>', $l);
    }

    // スレッド.idx 記録
    if ($setfav == '0' || $setfav == '1') {
    
        // お気にスレから外した結果、idxの意味がなくなれば削除する
        if ($setfav == '0' and (empty($data[3]) && empty($data[4]) && geti($data[9]) <= 1)) {
            @unlink($idxfile);
        } else {
            P2Util::recKeyIdx($idxfile, array(
                geti($data[0]), $key, geti($data[2]), geti($data[3]), geti($data[4]),
                geti($data[5]), $setfav, geti($data[7]), geti($data[8]), geti($data[9]),
                $host, $bbs, geti($data[12])
            ));
        }
    }
    
    return $data;
}

/**
 * お気にスレ共有
 *
 * @return  boolean|null
 */
function _setFavRank($host, $bbs, $key, $setfav, $ttitle)
{
    global $_conf;
    
    if (!$_conf['join_favrank']) {
        return null;
    }
    
    if ($setfav == '0') {
        $act = 'out';
    } elseif ($setfav == '1') {
        $act = 'add';
    } else {
        return null;
    }
    
    return _postFavRank(array(
        'host' => $host, 'bbs' => $bbs, 'key' => $key,
        'ttitle' => $ttitle,
        'ita' => P2Util::getItaName($host, $bbs),
        'act' => $act
    ));
}

/**
 * お気にスレ共有でポストする関数
 *
 * @return  boolean
 */
function _postFavRank($post)
{
    global $_conf;

    $method = "POST";
    $httpua = "Monazilla/1.00 (" . $_conf['p2uaname'] . "/" . $_conf['p2version'] . ")";
    
    $URL = parse_url($_conf['favrank_url']);
    if (isset($URL['query'])) {
        $URL['query'] = "?" . $URL['query'];
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
    
    if (!$send_port) { $send_port = 80; }
    
    $request = "$method $send_path HTTP/1.0" . "\r\n";
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

    $fp = fsockopen($send_host, $send_port, $errno, $errstr, 3);
    if (!$fp) {
        //echo "サーバ接続エラー: $errstr ($errno)<br>\n";
        //echo "p2 info: " . hs($_conf['favrank_url']) . " に接続できませんでした。<br>";
        return false;
    }

    fputs($fp, $request);
    fclose($fp);
    
    return true;
    //return $body;
}
