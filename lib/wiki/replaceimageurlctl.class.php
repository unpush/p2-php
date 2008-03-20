<?php
/*
ReplaceImageURL(url)        メイン関数
save(array)                 データを保存
load()                      データを読み込んで返す(自動的に実行される)
clear()                     データを削除
autoLoad()                  loadされていなければ実行
*/

require_once P2_LIB_DIR . '/filectl.class.php';

class ReplaceImageURLCtl
{
    var $filename = "p2_replace_imageurl.txt";
    var $data = array();
    var $isLoaded = false;

    function clear() {
        global $_conf;
        $path = $_conf['pref_dir'] . '/' . $this->filename;

        return @unlink($path);
    }

    function autoLoad() {
        if (!$this->isLoaded) $this->load();
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
                    preg_match_all('{' . $v['source'] . '}', P2Util::getWebPage($url, $errmsg), $extracted);
                    foreach ($extracted[1] as $i => $extract) {
                        $return[$i]['url']     = str_replace('$EXTRACT', $extract, $replaced);
                        $return[$i]['referer'] = str_replace('$EXTRACT', $extract, $referer);
                    }
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
