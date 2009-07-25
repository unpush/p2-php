<?php
/*
ReplaceImageURL(url)        メイン関数
save(array)                 データを保存
load()                      データを読み込んで返す(自動的に実行される)
clear()                     データを削除
autoLoad()                  loadされていなければ実行
*/

require_once P2_LIB_DIR . '/FileCtl.php';

class ReplaceImageURLCtl
{
    var $filename = "p2_replace_imageurl.txt";
    var $data = array();
    var $isLoaded = false;

    // replaceの結果を外部ファイルにキャッシュする
    // とりあえず外部リクエストの発生する$EXTRACT入りの場合のみ対象
    // 500系のエラーだった場合は、キャッシュしない
    var $cacheFilename = "p2_replace_imageurl_cache.txt";
    var $cacheData = array();
    var $cacheIsLoaded = false;

    // 全エラーをキャッシュして無視する(永続化はしないので今回リクエストのみ）
    var $extractErrors = array();

    function clear() {
        global $_conf;
        $path = $_conf['pref_dir'] . '/' . $this->filename;

        return @unlink($path);
    }

    function autoLoad() {
        if (!$this->isLoaded) $this->load();
        if (!$this->cacheIsLoaded) $this->load_cache();
    }

    function load() {
        global $_conf;

        $lines = array();
        $path = $_conf['pref_dir'].'/'.$this->filename;
        if ($lines = @file($path)) {
            foreach ($lines as $l) {
                $lar = explode("\t", trim($l));
                if (strlen($lar[0]) == 0 || count($lar) < 2) {
                    continue;
                }
                $ar = array(
                    'match'   => $lar[0], // 対象文字列
                    'replace' => $lar[1], // 置換文字列
                    'referer' => $lar[2], // 置換文字列
                    'extract' => $lar[3], // 置換文字列
                    'source'  => $lar[4], // 置換文字列
                );

                $this->data[] = $ar;
            }
        }
        $this->isLoaded = true;
        return $this->data;
    }

    /**
     * saveReplaceImageURL
     * $data[$i]['match']       Match
     * $data[$i]['replace']     Replace
     * $data[$i]['del']         削除
     */
    function save($data)
    {
        global $_conf;

        $path = $_conf['pref_dir'] . '/' . $this->filename;

        $newdata = '';

        foreach ($data as $na_info) {
            $a[0] = strtr(trim($na_info['match']  , "\t\r\n"), "\t\r\n", "   ");
            $a[1] = strtr(trim($na_info['replace'], "\t\r\n"), "\t\r\n", "   ");
            $a[2] = strtr(trim($na_info['referer'], "\t\r\n"), "\t\r\n", "   ");
            $a[3] = strtr(trim($na_info['extract'], "\t\r\n"), "\t\r\n", "   ");
            $a[4] = strtr(trim($na_info['source'] , "\t\r\n"), "\t\r\n", "   ");
            if ($na_info['del'] || ($a[0] === '' || $a[1] === '')) {
                continue;
            }
            $newdata .= implode("\t", $a) . "\n";
        }
        return FileCtl::file_write_contents($path, $newdata);
    }


    function load_cache() {
        global $_conf;
        $lines = array();
        $path = $_conf['pref_dir'].'/'.$this->cacheFilename;
        FileCtl::make_datafile($path);
        if ($lines = @file($path)) {
            foreach ($lines as $l) {
                list($key, $data) = explode("\t", trim($l));
                if (strlen($key) == 0 || strlen($data) == 0) continue;
                $this->cacheData[$key] = unserialize($data);
            }
        }
        $this->cacheIsLoaded = true;
        return $this->cacheData;
    }

    function addCache($key, $data) {
        global $_conf;

        if ($this->cacheData[$key]) {
            return false;   // ここはあまり通過しないでしょう
        }
        $this->cacheData[$key] = $data;
        return FileCtl::file_write_contents(
            $_conf['pref_dir'] . '/' . $this->cacheFilename,
            implode("\t", array($key,
                serialize(ReplaceImageURLCtl::sanitizeForCache($data)))
            ) . "\n",
            FILE_APPEND
        );
    }

    static function sanitizeForCache($data) {
        if (is_array($data)) {
            foreach(array_keys($data) as $k) {
                $data[$k] = ReplaceImageURLCtl::sanitizeForCache($data[$k]);
            }
            return $data;
        } else {
            return str_replace(array("\t", "\r", "\n"), '', $data);
        }
    }

    /**
     * replaceImageURL
     * リンクプラグインを実行
     * return array
     *      $ret[$i]['url']     $i番目のURL
     *      $ret[$i]['referer'] $i番目のリファラ
     */
    function replaceImageURL($url) {
        // http://janestyle.s11.xrea.com/help/first/ImageViewURLReplace.html
        $this->autoLoad();
        $src = FALSE;

        if ($this->cacheData[$url]) {
            // キャッシュがあればそれを返す
            return $this->cacheData[$url]['data'];
        }
        foreach ($this->data as $v) {
            if (preg_match('{^'.$v['match'].'$}', $url)) {
                $v['replace'] = str_replace('$&', '$0', $v['replace']);
                $v['referer'] = str_replace('$&', '$0', $v['referer']);
                // 第一置換(Match→Replace, Match→Referer)
                $replaced = @preg_replace ('{'.$v['match'].'}', $v['replace'], $url);
                $referer =  @preg_replace ('{'.$v['match'].'}', $v['referer'], $url);
                // $EXTRACTがある場合
                // 注:$COOKIE, $COOKIE={URL}, $EXTRACT={URL}には未対応
                // $EXTRACT={URL}の実装は容易
                if (strstr($v['extract'], '$EXTRACT')){
                    $v['source'] =  @preg_replace ('{'.$v['match'].'}', $v['source'], $url);
                    $get_url = $referer;
                    if ($this->extractErrors[$get_url]) {
                        break;  // 今回リクエストでエラーだった場合スキップ
                    }
                    $page = P2Util::getWebPage($get_url, $errmsg);
                    if ($errmsg) {
                        // 今回リクエストでのエラーを一時キャッシュ
                        $this->extractErrors[$get_url] = $errmsg;
                        if ($errmsg < 500) {
                            // サーバエラー以外なら永続キャッシュに保存
                            $this->addCache($url,
                                array('code' => $errmsg, 'data' => array()));
                        }
                        break;
                    }
                    preg_match_all('{' . $v['source'] . '}i', $page, $extracted, PREG_SET_ORDER);
                    foreach ($extracted as $i => $extract) {
                        $_url = $replaced; $_referer = $referer;
                        foreach ($extract as $j => $part) {
                            if ($j < 1) continue;
                            $_url       = str_replace('$EXTRACT'.$j, $part, $_url);
                            $_referer   = str_replace('$EXTRACT'.$j, $part, $_referer);
                        }
                        if ($extract[1]) {
                            $_url       = str_replace('$EXTRACT', $part, $_url);
                            $_referer   = str_replace('$EXTRACT', $part, $_referer);
                        }
                        $return[$i]['url']      = $_url;
                        $return[$i]['referer']  = $_referer;
                    }
                    // 結果を永続キャッシュに保存
                    $this->addCache($url, array('code' => $errmsg, 'data' => $return));
                } else {
                    $return[0]['url']     = $replaced;
                    $return[0]['referer'] = $referer;
                }
                break;
            }
        }
        /* plugin_imageCache2で処理させるためコメントアウト
        // ヒットしなかった場合
        if (!$return[0]) {
            // 画像っぽいURLの場合
            if (preg_match('{^https?://.+?\\.(jpe?g|gif|png)$}i', $url)) {
                $return[0]['url']     = $url;
                $return[0]['referer'] = '';
            }
        }
        */
        return $return;
    }

}
