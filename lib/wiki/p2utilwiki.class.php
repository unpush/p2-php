<?php

class P2UtilWiki {

    /**
     * +Wiki:プロフィールIDからBEIDを計算する
     *
     * @return integer|0 成功したらBEIDを返す。失敗したら0を返す。
     */
    function calcBeId($prof_id) {
        $found = false;
        for ($y = 2; $y <= 9 && !$found; $y++) {
            for ($x = 2; $x <= 9 && !$found; $x++) {
                $id = (($prof_id - $x*10.0 - $y)/100.0 + $x - $y - 5.0)/(3.0 * $x * $y);
                if ($id == floor($id)) $found = true;
            }
        }
        return ($found ? $id : 0);
    }

    /**
     * Wiki:そのURLにアクセスできるか確認する
     */
    function isURLAccessible($url, $timeout = 7)
    {
        $code = P2UtilWiki::getResponseCode($url);
        return ($code == 200 || $code == 206) ? true : false;
    }

    /**
     * URLがイメピタならtrueを返す
     */
    function isUrlImepita($url)
    {
        return preg_match('{^http://imepita\.jp/}', $url);
    }

    function getResponseCode($url) {
        require_once 'HTTP/Client.php';
        $client = &new HTTP_Client;
        $client->setRequestParameter('timeout', $timeout);
        $client->setDefaultHeader('User-Agent', 'Monazilla/1.00');
        if (!empty($_conf['proxy_use'])) {
            $client->setRequestParameter('proxy_host', $_conf['proxy_host']);
            $client->setRequestParameter('proxy_port', $_conf['proxy_port']);
        }
        return $client->head($url);
    }

    /**
     * Wiki:Last-Modifiedをチェックしてキャッシュする
     * time:チェックしない期間(unixtime)
     */
    function cacheDownload($url, $path, $time = 0)
    {
        global $_conf;
        $filetime = @filemtime($path);
        
        // キャッシュ有効期間ならチェックしない
        if ($filetime > 0 && $filetime > time() - $time) return;
        
        if (!class_exists('HTTP_Request', false)) {
            require 'HTTP/Request.php';
        }
        $req = & new HTTP_Request($url, array('timeout' => $_conf['fsockopen_time_limit']));
        $req->setMethod('HEAD');
        $now = time();
        $req->sendRequest();
        $unixtime = strtotime($req->getResponseHeader('Last-Modified'));

        // 新しければ取得
        if($unixtime > $filetime){ 
            P2Util::fileDownload($url, $path);
            // 最終更新日時を設定
            // touch($path, $unixtime);
        } else {
            // touch($path, $now);
        }
        touch($path, $now);
    }

}
