<?php
/*
rep2+wiki
sambaタイマーをコントロールするクラス。
メイン:
getSamba($host, $bbs)       残り時間(秒)を取得(post_options_loader.inc.phpで使う)
setWriteTime($host, $bbs)   書き込んだ時刻(=現在時刻)をセット(post.phpで使う
save()                      sambaリストを保存(setSambaの後に実行)
createTimer($time)          $time秒のsambaタイマーを生成して返す
その他(通常明示的に実行する必要はないもの:
load()                      sambaリストを読み込む(自動的に実行される)
getSambaTime($host, $bbs)   板のsambaを取得
*/

class samba
{
    var $data = array();
    /* データ構造
    bbs=bbs名
    $data[bbs][bbs]    bbs名(saveで利用)
    $data[bbs][samba]  sambaの時間(秒)
    $data[bbs][write]  書き込んだ時間(timer関数)
    $data[bbs][get]    sambaを取得した時間(timer)
    */
    var $filename = 'p2_samba.txt';
    var $isLoaded = false;

    function load() {
        global $_conf;
        $lines = array();
        $path = $_conf['pref_dir'].'/'.$this->filename;
        if ($lines = @file($path)) {
            foreach ($lines as $l) {
                $lar = explode("\t", trim($l));
                if (strlen($lar[0]) == 0 || ($lar[1] === 0 && $lar[2] === 0)) {
                    continue;
                }
                $ar = array(
                    'bbs'   => $lar[0],  // bbs
                    'samba' => $lar[1],  // sambaの時間
                    'write'  => $lar[2], // 書き込んだ時間
                    'get'   => $lar[3],  // 取得時間
                );

                $array[$lar[0]] = $ar;
            }

        }
        $this->data = $array;
        $this->isLoaded = true;
        return $array;
    }

    function save() {
        global $_conf;
        
        $file = '';
        foreach($this->data as $l) {
            $file .= "{$l['bbs']}\t{$l['samba']}\t{$l['write']}\t{$l['get']}\n";
        }
        $path = $_conf['pref_dir'].'/'.$this->filename;
        $fh = fopen($path, "w");
        fwrite($fh, $file);
        fclose($fh);
    }

    function getSambaTime($host, $bbs) {
        if (!P2Util::isHost2chs($host)) return false;
        // sambaを取得
        $url = "http://{$host}/{$bbs}/index.html";
        $src = P2Util::getWebPage($url, $errmsg);
        $match = '{<a href="http://www.2ch.net/">２ちゃんねる</a> BBS\.CGI - .*?\+Samba24=(\d+)}';
        preg_match($match, $src, $samba);
        if(!$this->isLoaded) $this->load();
        $this->data[$bbs]['bbs']   = $bbs;
        $this->data[$bbs]['get']   = time();
        $this->data[$bbs]['samba'] = $samba[1];

        return $samba[1];
    }

    /**
     * 書き込んだ時刻をセット
     */
    function setWriteTime($host, $bbs) {
        global $_conf;

        if(!$this->isLoaded) $this->load();
        $this->data[$bbs]['write'] = time();
        // 最終取得からsamba_cache時間経過した場合
        if((time() - $this->data[$bbs]['get']) > $_conf['samba_cache'] * 3600){
            $this->getSambaTime($host, $bbs);
        }
    }

    // 残り時間を取得
    function getSamba($host, $bbs) {
        if (!P2Util::isHost2chs($host)) return -1;

        // 読み込んでいなければ読み込む
        if(!$this->isLoaded) $this->load();
        // 書き込んでいなければ残り0秒
        if($this->data[$bbs]['write'] <= 0) return 0;
        // 規制0秒なら計算するまでもなく残り0秒
        if($this->data[$bbs]['samba'] <= 0) return 0;
        // 残り時間
        $time = $this->data[$bbs]['write'] + $this->data[$bbs]['samba'] - time();
        return $time > 0 ? $time : 0;
    }

    /*
    $time秒のsambaタイマーを生成
    */
    function createTimer($time) {
        global $_conf;
        // getSambaのエラーなので生成できない
        if ($time < 0)      return;
        // 書き込める
        if ($time == 0)     return '書き込めるかも';

        // PC
        return <<<EOP
        <span id="sambaSecond">あと{$time}秒</span>
        <script type="text/JavaScript">
        <!--
        intSecond = {$time};
        timSamba = setInterval("sambaTimer()",1000);
        function sambaTimer(){
            intSecond -= 1;
            if (intSecond <= 0){
                clearInterval(timSamba);
                sambaSecond.innerHTML = "書き込めるかも";
            } else {
            sambaSecond.innerHTML = "あと" + intSecond + "秒";
            }
        }
        // -->
        </script>
EOP;
    }
}
