<?php
/*
replaceLinkToHTML(url, src) メイン関数
save(array)                 データを保存
load()                      データを読み込んで返す(自動的に実行される)
clear()                     データを削除
autoLoad()                  loadされていなければ実行
*/

require_once P2_LIB_DIR . '/FileCtl.php';

class LinkPluginCtl
{
    var $filename = "p2_plugin_link.txt";
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
                if (strlen($lar[0]) == 0) {
                    continue;
                }
                $ar = array(
                    'match'   => $lar[0], // 対象文字列
                    'replace' => $lar[1], // 置換文字列
                );

                $this->data[] = $ar;
            }
        }

        $this->isLoaded = true;
        return $this->data;
    }

    /*
    $data[$i]['match']       Match
    $data[$i]['replace']     Replace
    $data[$i]['del']         削除
    */
    function save($data) {
        global $_conf;
        $path = $_conf['pref_dir'] . '/' . $this->filename;

        $newdata = '';

        foreach ($data as $na_info) {
            $a[0] = strtr(trim($na_info['match'], "\t\r\n"), "\t\r\n", "   ");
            $a[1] = strtr(trim($na_info['replace'], "\t\r\n"), "\t\r\n", "   ");
            if ($na_info['del'] || ($a[0] === '' || $a[1] === '')) {
                continue;
            }
            $newdata .= implode("\t", $a) . "\n";
        }

        return FileCtl::file_write_contents($path, $newdata);
    }

    function replaceLinkToHTML($url, $str) {
        $this->autoLoad();
        $src = FALSE;
        foreach ($this->data as $v) {
            if (preg_match('{'.$v['match'].'}', $url)) {
                $src = @preg_replace ('{'.$v['match'].'}', $v['replace'], $url);
                if (strstr($v['replace'], '$ime_url')) {
                    $src = str_replace('$ime_url', P2Util::throughIme($url), $src);
                }
                if (strstr($v['replace'], '$str')) {
                    $src = str_replace('$str', $str, $src);
                }
                break;
            }
        }
        return $src;
    }

}
