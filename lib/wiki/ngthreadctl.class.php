<?php
/*
replaceLinkToHTML(url, src) メイン関数
save(array)                 データを保存
load()                      データを読み込んで返す(自動的に実行される)
clear()                     データを削除
autoLoad()                  loadされていなければ実行

基本構造
データの登録方法
・$this->dataを更新する事による登録
・一括で登録
・一行だけの登録
データ構造
word    ignorecase  regex     bbs lasttime    hits
*/

require_once P2_LIB_DIR . '/FileCtl.php';

class NgThreadCtl
{
    var $filename = "p2_aborn_thread.txt";
    var $data = array();
    var $hits = 0;
    var $isLoaded = false;
    var $date = 'Y/m/d G:i';

    /*
    データをクリア
    */
    function clear() {
        global $_conf;
        $path = $_conf['pref_dir'] . '/' . $this->filename;

        return @unlink($path);
    }

    /*
    自動的に読み込む
    */
    function autoLoad() {
        if (!$this->isLoaded) $this->load();
    }

    /*
    データを読み込んで返す
     */
    function load()
    {
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
                    'word'       => $lar[0], // 対象文字列
                    'ignorecase' => $lar[1], // 大文字小文字を無視
                    'regex'      => $lar[2], // 正規表現
                    'bbs'        => $lar[3], // 板縛り
                    'lasttime'   => $lar[4] == '--' ? '' : $lar[4], // 最後にHITした時間
                    'hits'       => (int) $lar[5], // HIT回数
                );

                $this->data[] = $ar;
            }

        }
        $this->isLoaded = TRUE;
        return $this->data;

    }

    /*
    保存
    引数が指定されてる⇒そのデータで保存
    $this->dataがない⇒保存しない
    */
    function save($data)
    {
        global $_conf;

        if ($data) {
            $new_data = TRUE;
            $this->data = $data;
        } else if (!$this->isLoaded) {
            return;
        } else {
            $new_data = FALSE;
        }
        // HITした時のみ更新する
        if ($this->hits > 0 || $new_data) {
            $cont = '';

            foreach ($this->data as $v) {
                if ($v['del']) continue;

                // 必要ならここで古いデータはスキップ（削除）する
                if (!empty($v['lasttime']) && $_conf['ngaborn_daylimit']) {
                    if (strtotime($v['lasttime']) < time() - 60 * 60 * 24 * $_conf['ngaborn_daylimit']) {
                        continue;
                    }
                }

                $a['word'] = strtr(trim($v['word'], "\t\r\n"), "\t\r\n", "   ");
                $a['ignorecase'] = $v['ignorecase'];
                $a['regex'] = $v['regex'];
                $a['bbs'] = strtr(trim($v['bbs'], "\t\r\n"), "\t\r\n", "   ");
                $a['lasttime'] = $v['lasttime'];
                $a['hits'] = $v['hits'];

                // lasttimeが設定されていなかったら現在時間を設定(本来なら登録時にするべき)
                if (empty($v['lasttime'])) $v['lasttime'] = date($this->date);

                $cont .= implode("\t", $v) . "\n";
            }

            return FileCtl::file_write_contents($_conf['pref_dir'].'/'.$this->filename);
        }
    }

    /*
    あぼーんチェック
    あぼーん対象⇒TRUE
    */
    function check($aThread)
    {

        $this->autoLoad();

        if ($aThreadList->spmode != "taborn" && isset($this->data) && is_array($this->data)) {
            foreach ($this->data as $k => $v) {
                // 板チェック
                if (!in_array($aThread->bbs, explode(',', $v['bbs']))) {
                    continue;
                }
                // 正規表現
                if (!empty($v['regex'])) {
                    if ($v['ignorecase']) {
                        $match = '{' . $v['word'] . '}i';
                    } else {
                        $match = '{' . $v['word'] . '}';
                    }
                    if (preg_match('{' . $v['word'] . '}i', $aThread->ttitle_hc)) {
                        $this->update($k);
                        return TRUE;
                    }
                // 大文字小文字を無視
                } elseif (!empty($v['ignorecase'])) {
                    if(stristr($aThread->ttitle_hc,$v['word'])){
                        $this->update($k);
                        return TRUE;
                    }
                // 単純に文字列が含まれるかどうかをチェック
                }else {
                    if (strstr($aThread->ttitle_hc,$v['word'])) {
                        $this->update($k);
                        return TRUE;
                    }
                }
            }
        }

        return FALSE;
    }

    /*
    そのデータのあぼーん情報を更新
    */
    function update($k) {
        $this->hits++;
        if (isset($this->data[$k])) {
            $v =& $this->data[$k];
            $v['lasttime'] = date($this->date); // HIT時間を更新
            if (empty($v['hits'])) {
                $v['hits'] = 1; // 初HIT
            } else {
                $v['hits']++; // HIT回数を更新
            }
        }
    }

}
