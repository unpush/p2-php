<?php

require_once P2_LIBRARY_DIR . '/filectl.class.php';

/**
 * p2 - ボードメニュークラス for menu.php
 */
class BrdMenu{

    var $categories;    // クラス BrdMenuCate のオブジェクトを格納する配列
    var $num;           // 格納された BrdMenuCate オブジェクトの数
    var $format;        // html形式か、brd形式か("html", "brd")
    var $cate_match;    // カテゴリーマッチ形式
    var $ita_match;     // 板マッチ形式

    function BrdMenu()
    {
        $this->num = 0;
    }

    /**
     * カテゴリーを追加する
     */
    function addBrdMenuCate(&$aBrdMenuCate)
    {
        $this->categories[] =& $aBrdMenuCate;
        $this->num++;
    }

    /**
    * パターンマッチの形式を登録する
    */
    function setBrdMatch($brdName)
    {
        // html形式
        if (preg_match("/(html?|cgi)$/", $brdName)) {
            $this->format = "html";
            $this->cate_match = "/<B>(.+)<\/B><BR>.*$/i";
            $this->ita_match = "/^<A HREF=\"?(http:\/\/(.+)\/([^\/]+)\/([^\/]+\.html?)?)\"?( target=\"?_blank\"?)?>(.+)<\/A>(<br>)?$/i";
        // brd形式
        } else {
            $this->format = "brd";
            $this->cate_match = "/^(.+)\t([0-9])$/";
            $this->ita_match = "/^\t?(.+)\t(.+)\t(.+)$/";
        }
    }

    /**
    * データを読み込んで、カテゴリと板を登録する
    */
    function setBrdList($data)
    {
        global $_conf;

        if (empty($data)) { return false; }

        // 除外URLリスト
        $not_bbs_list = array("http://members.tripod.co.jp/Backy/del_2ch/");

        foreach ($data as $v) {
            $v = rtrim($v);

            // カテゴリを探す
            if (preg_match($this->cate_match, $v, $matches)) {
                $aBrdMenuCate =& new BrdMenuCate($matches[1]);
                if ($this->format == 'brd') {
                    $aBrdMenuCate->is_open = $matches[2];
                }
                $this->addBrdMenuCate($aBrdMenuCate);

            // 板を探す
            } elseif (preg_match($this->ita_match, $v, $matches)) {
                // html形式なら除外URLを外す
                if ($this->format == 'html') {
                    foreach ($not_bbs_list as $not_a_bbs) {
                        if ($not_a_bbs == $matches[1]) { continue 2; }
                    }
                }
                $aBrdMenuIta =& new BrdMenuIta();
                // html形式
                if ($this->format == 'html') {
                    $aBrdMenuIta->host = $matches[2];
                    $aBrdMenuIta->bbs = $matches[3];
                    $itaj_match = $matches[6];
                // brd形式
                } else {
                    $aBrdMenuIta->host = $matches[1];
                    $aBrdMenuIta->bbs = $matches[2];
                    $itaj_match = $matches[3];
                }
                $aBrdMenuIta->setItaj(rtrim($itaj_match));

                // {{{ 板検索マッチ

                // and検索
                if ($GLOBALS['words_fm']) {

                    $no_match = false;

                    foreach ($GLOBALS['words_fm'] as $word_fm_ao) {
                        $target = $aBrdMenuIta->itaj."\t".$aBrdMenuIta->bbs;
                        if (!StrCtl::filterMatch($word_fm_ao, $target)) {
                            $no_match = true;
                        }
                    }

                    if (!$no_match) {
                        $this->categories[$this->num-1]->ita_match_num++;
                        $GLOBALS['ita_mikke']['num']++;
                        $GLOBALS['ita_mikke']['host'] = $aBrdMenuIta->host;
                        $GLOBALS['ita_mikke']['bbs'] = $aBrdMenuIta->bbs;
                        $GLOBALS['ita_mikke']['itaj_en'] = $aBrdMenuIta->itaj_en;

                        // マーキング
                        if ($_conf['ktai'] && is_string($_conf['k_filter_marker'])) {
                            $aBrdMenuIta->itaj_ht = StrCtl::filterMarking($GLOBALS['word_fm'], $aBrdMenuIta->itaj, $_conf['k_filter_marker']);
                        } else {
                            $aBrdMenuIta->itaj_ht = StrCtl::filterMarking($GLOBALS['word_fm'], $aBrdMenuIta->itaj);
                        }

                        // マッチマーキングなければ（bbsでマッチしたとき）、全部マーキング
                        if ($aBrdMenuIta->itaj_ht == $aBrdMenuIta->itaj) {
                            $aBrdMenuIta->itaj_ht = '<b class="filtering">'.$aBrdMenuIta->itaj_ht.'</b>';
                        }

                    // 検索が見つからなくて、さらに携帯の時
                    } else {
                        if ($_conf['ktai']) {
                            continue;
                        }
                    }
                }

                // }}}

                if ($this->num) {
                    $this->categories[$this->num-1]->addBrdMenuIta($aBrdMenuIta);
                }
            }
        }
    }

    /**
    * brdファイルを生成する
    *
    * @return    string    brdファイルのパス
    */
    function makeBrdFile($cachefile)
    {
        global $_conf, $_info_msg_ht, $word;

        $p2brdfile = $cachefile.".p2.brd";
        FileCtl::make_datafile($p2brdfile, $_conf['p2_perm']);
        $data = @file($cachefile);
        $this->setBrdMatch($cachefile); // パターンマッチ形式を登録
        $this->setBrdList($data);       // カテゴリーと板をセット
        if ($this->categories) {
            foreach ($this->categories as $cate) {
                if ($cate->num > 0) {
                    $cont .= $cate->name."\t0\n";
                    foreach ($cate->menuitas as $mita) {
                        $cont .= "\t{$mita->host}\t{$mita->bbs}\t{$mita->itaj}\n";
                    }
                }
            }
        }

        if ($cont) {
            if (FileCtl::file_write_contents($p2brdfile, $cont) === false) {
                die("p2 error: {$p2brdfile} を更新できませんでした");
            }
            return $p2brdfile;
        } else {
            if (!$word) {
                $_info_msg_ht .=  "<p>p2 エラー: {$cachefile} から板メニューを生成することはできませんでした。</p>\n";
            }
            return false;
        }
    }

}

/**
* ボードメニューカテゴリークラス
*/
class BrdMenuCate{

    var $name;          // カテゴリーの名前
    var $menuitas;      // クラスBrdMenuItaのオブジェクトを格納する配列
    var $num;           // 格納されたBrdMenuItaオブジェクトの数
    var $is_open;       // 開閉状態(bool)
    var $ita_match_num; // 検索にヒットした板の数

    /**
    * コンストラクタ
    */
    function BrdMenuCate($name)
    {
        $this->num = 0;
        $this->menuitas = array();
        $this->ita_match_num = 0;

        $this->name = $name;
    }

    /**
     * 板を追加する
     */
    function addBrdMenuIta(&$aBrdMenuIta)
    {
        $this->menuitas[] =& $aBrdMenuIta;
        $this->num++;
    }

}

/**
* ボードメニュー板クラス
*/
class BrdMenuIta{
    var $host;
    var $bbs;
    var $itaj;    // 板名
    var $itaj_en;    // 板名をエンコードしたもの
    var $itaj_ht;    // HTMLで出力する板名（フィルタリングしたもの）

    function setItaj($itaj)
    {
        $this->itaj = $itaj;
        $this->itaj_en = rawurlencode(base64_encode($this->itaj));
        $this->itaj_ht = htmlspecialchars($this->itaj, ENT_QUOTES);
    }
}

?>
