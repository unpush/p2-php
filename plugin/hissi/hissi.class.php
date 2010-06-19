<?php
/*
使用例:
$hissi = new hissi();
$hissi->host = $host; // 指定しない場合は2chとみなす
$hissi->bbs  = $bbs;
if ($hissi->isEnable()) {
    // bbsの指定が必要
    echo $hissi->getBoardURL();
    $hissi->date = $date;
    // bbs, dateの指定が必要
    echo $hissi->getBoardDateURL();
    $hissi->id   = $id;
    // bbs, date, idの指定が必要
    echo $hissi->getIDURL();
}
*/
class Hissi
{
    var $boards;    // array
    var $host;      // 板のホスト
    var $bbs;       // 板のディレクトリ名
    var $id;        // ID
    var $date;      // 日付をyyyymmddで指定
    var $enabled;   // isEnable

    /**
     * 必死チェッカー対応板を読み込む
     * 自動で読み込まれるので通常は実行する必要はない
     */
    function load()
    {
        global $_conf;
        // include_once P2_LIB_DIR . '/p2util.class.php';
        $url  = 'http://hissi.org/menu.html';
        $path = P2Util::cacheFileForDL($url);
        // メニューのキャッシュ時間の10倍キャッシュ
        P2UtilWiki::cacheDownload($url, $path, $_conf['menu_dl_interval'] * 36000);
        $file = @file_get_contents($path);
        preg_match_all('{<a href=http://hissi\.org/read\.php/(\w+?)/>.+?</a><br>}',$file, $boards);
        $this->boards = $boards[1];
    }

    /**
     * 必死チェッカーに対応しているか調べる
     * $boardがなければloadも実行される
     */
    function isEnable()
    {
        if ($this->host) {
            if (!P2Util::isHost2chs($this->host)) return false;
        }
        
        if (!isset($this->boards)) $this->load();
        $this->enabled = in_array($this->bbs, $this->boards) ? true : false;
        return $this->enabled;
    }

    /**
     * IDのURLを取得する
     * $all = trueで全てのスレッドを表示
     * isEnable() == falseでも取得できるので注意
     */
    function getIDURL($all = false, $page = 0)
    {
        $id_en = rtrim(base64_encode($this->id), '=');
        $query = $all ? '?thread=all' : '';
        if($page)  $query = $query ? "{query}&p={$page}" : "?p={page}";
        return "http://hissi.org/read.php/{$this->bbs}/{$this->date}/{$id_en}.html{$query}";
    }

    /**
     * 板のURLを設定する
     * isEnable() == falseでも取得できるので注意
     */
    function getBoardURL()
    {
        return "http://hissi.org/read.php/{$this->bbs}/";
    }

    /**
     * 板のその日付のURLを設定する
     * isEnable() == falseでも取得できるので注意
     */
    function getBoardDateURL()
    {
        return "http://hissi.org/read.php/{$this->bbs}/{$this->date}/";
    }
}
