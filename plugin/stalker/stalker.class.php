<?php
/*
使用例:
$stalker = new stalker();
$stalker->host = $host; // 指定しない場合は2chとみなす
$stalker->bbs  = $bbs;
if ($stalker->isEnable()) {
    // bbs, date, idの指定が必要
    echo $stalker->getIDURL();
}
*/

class stalker
{
    var $host;      // 板のホスト
    var $bbs;       // 板のディレクトリ名
    var $id;        // ID
    var $enabled;   // isEnable

    /**
     * IDストーカーに対応しているか調べる
     * $boardがなければloadも実行される
     */
    function isEnable()
    {
        if ($this->host) {
            if (!P2Util::isHost2chs($this->host)) return false;
        }
        return preg_match('/plus$/', $this->bbs);
    }

    /**
     * IDのURLを取得する
     */
    function getIDURL()
    {
        return "http://stick.newsplus.jp/id.cgi?bbs={$this->bbs}&word=" . URLencode($this->id);
    }
}