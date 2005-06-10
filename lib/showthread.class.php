<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */
/*
    p2 - スレッドを表示する クラス
*/

require_once (P2_LIBRARY_DIR . '/filectl.class.php');
require_once (P2_LIBRARY_DIR . '/ngabornchk.class.php');

$GLOBALS['bbs_ymd_offset'] = array();
$_bbs_ymd_offset_txt = 'conf/bbs_ymd_offset.txt';
if (file_exists($_bbs_ymd_offset_txt)) {
    $_bbs_ymd_offset = file($_bbs_ymd_offset_txt);
    foreach ($_bbs_ymd_offset as $_line) {
        list($_name, $_offset) = explode('<>', $_line);
        $GLOBALS['bbs_ymd_offset'][$_name] = (int)$_offset;
    }
    unset($_bbs_ymd_offset, $_line, $_name, $_offset);
}
unset($_bbs_ymd_offset_txt);

// {{{ class ShowThread

class ShowThread
{
    // {{{ properties

    // スレッドオブジェクト
    var $thread;

    // あぼーん/NGチェッカ
    var $checker;

    // パース済みdatの互換性チェック文字列
    var $pDatCompat = 'Compatible:2.1.0';

    // パース済みdatを格納する配列
    var $pDatLines;

    // レス数
    var $pDatCount;

    // 暦
    var $custom_year;

    // リンクすべき文字列の正規表現
    var $str_to_link_regex; 

    // URLを処理する関数・メソッド名などを格納する配列
    var $url_handlers;

    // "read.php?host=xxx&bbs=yyy&key=zzz"
    var $read_url_base;

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ (PHP4 style)
     */
    function ShowThread(&$aThread)
    {
        $this->__construct($aThread);
    }

    /**
     * コンストラクタ (PHP5 style)
     */
    function __construct(&$aThread)
    {
        global $_conf;

        // スレッドオブジェクトを登録
        $this->thread = &$aThread;

        // 基本URL設定
        $read_url_base = $_conf['read_php'] . '?host=' . rawurlencode($this->thread->host);
        $read_url_base .= '&bbs=' . $this->thread->bbs . '&key=' . $this->thread->key;
        $this->read_url_base = htmlspecialchars($read_url_base);

        // Dat読み込み
        if (!isset($this->thread->datlines)) {
            $this->thread->readDat($this->thread->keydat);
        }

        // 自動リンク設定
        $this->str_to_link_regex = '{'
            . '(?P<link>(<[Aa] .+?>)(.*?)(?:</[Aa]>))' // リンク（PCREの特性上、必ずこのパターンを最初に試行する）
            . '|'
            . '(?:'
            .   '(?P<quote>' // 引用
            .       '((?:&gt;|＞){1,2} ?)' // 引用符
            .       '('
            .           '(?:[1-9]\\d{0,3})' // 1つ目の番号
            .           '(?:'
            .               '(?: ?(?:[,=]|、) ?[1-9]\\d{0,3})+' // 連続
            .               '|'
            .               '-(?:[1-9]\\d{0,3})?' // 範囲
            .           ')?'
            .       ')'
            .       '(?=\\D|$)'
            .   ')' // 引用ここまで
            . '|'
            .   '(?P<url>'
            .       '(ftp|h?t?tps?)://([0-9A-Za-z][\\w/\\#~:;.,?+=&%@!\\-]+?)' // URL
            .       '(?=[^\\w/\\#~:;.,?+=&%@!\\-]|$)' // 無効な文字か行末の先読み
            .   ')'
            . '|'
            .   '(?P<id>ID: ?([0-9A-Za-z/.+]{8,11}(?:[,.0O]|†)?)(?=[^0-9A-Za-z/.+]|†|$))' // ID（8,10桁 +PC/携帯識別フラグ）
            . ')'
            . '}';
        $this->url_handlers = array();

        // 看板ポップアップで取得したSETTING.TXTがあれば暦設定を読み込む
        $this->loadYmdFromSettingTxt();

        // datをパース＆キャッシュ
        $this->thread->idcount = array();
        $this->parseDat();
        unset($this->thread->datlines);

        // あぼーん/NGチェッカ
        $this->checker = &new NgAbornChk($aThread, $this->pDatLines);
    }

    // }}}
    // {{{ datToHtml()

    /**
     * DatをHTML変換する
     */
    function datToHtml()
    {
        return '';
    }

    // }}}
    // {{{ getDatToHtml()

    /**
     * DatをHTML変換したものを取得する
     */
    function getDatToHtml()
    {
        ob_start();
        $this->datToHtml();
        $html = ob_get_clean();

        return $html;
    }

    // }}}
    // {{{ parseDat()

    /**
     * datをパースする
     */

    function parseDat()
    {
        $uptodate = FALSE;
        $fopen = TRUE;

        // {{{ parseDat - 更新モード判定

        if ($this->thread->onthefly || P2Util::isHostNoCacheData($this->thread->host)) {
            $this->pDatLines = array();
            $fopen = FALSE;
        } else {
            // パース済みdat(pdat)のバージョンチェック
            if (file_exists($this->thread->pdat)) {
                $this->pDatLines = file($this->thread->pdat);
                list($compat, ) = explode('<>', rtrim($this->pDatLines[0]));
                if ($compat == $this->pDatCompat) {
                    $uptodate = TRUE;
                }
            }

            // pdatの更新モード設定
            if ($uptodate) {
                $pdl = count($this->pDatLines);
                $kdl = count($this->thread->datlines);
                // 記録しているレス数が同じ
                if ($pdl == $kdl + 1) {
                    $fopen = FALSE;
                // なぜかdatよりレス数が多い
                } elseif ($pdl > $kdl) {
                    $mode = 'wb';
                // 新着あり
                } else {
                    $mode = 'ab';
                }
            } else {
                $mode = 'wb';
            }

            // 上書きモードのときは初期化する
            if ($mode == 'wb') {
                FileCtl::make_datafile($this->thread->pdat, $_conf['dat_perm']);
                $this->pDatLines = array();
            }
        }

        // }}}
        // {{{ parseDat - パース＆キャッシュ

        $this->pDatLines[0] = implode('<>', array($this->pDatCompat, 
            $this->thread->host, $this->thread->bbs, $this->thread->key, $this->thread->ttitle));

        // pdatを更新しない
        if (!$fopen) {
            $i = 1;
            while (isset($this->thread->datlines[$i-1])) {
                $this->parseDatLine($i);
                $i++;
            }
            $this->pDatCount = $i - 1;

        // pdatを更新する
        } else {
            $fp = fopen($this->thread->pdat, $mode) or die("Error: cannot write file. ({$this->thread->pdat})");
            @flock($fp, LOCK_EX);
            if ($mode == 'wb') {
                fputs($fp, $this->pDatLines[0]);
                fputs($fp, "\n");
            }
            $i = 1;
            while (isset($this->thread->datlines[$i-1])) {
                $isNewLine = $this->parseDatLine($i);
                // 新着ならキャッシュする
                if ($isNewLine) {
                    $newdata = serialize($this->pDatLines[$i]);
                    fputs($fp, $newdata);
                    fputs($fp, "\n");
                }
                $i++;
            }
            $this->pDatCount = $i - 1;
            @flock($fp, LOCK_UN);
            fclose($fp);
        }

        // }}}
    }

    // }}}
    // {{{ parseDatLine()

    /**
     * datの各行をパースする
     *
     * @return boolean 新着レス（キャッシュされていないデータ）か否か
     */
    function parseDatLine($i)
    {
        global $_conf, $_exconf, $aborn_words, $ng_words;

        // datに無いとき
        if (!isset($this->thread->datlines[$i-1])) {
            return FALSE;
        }

        // 既にキャッシュされているとき
        if (isset($this->pDatLines[$i])) {
            $this->pDatLines[$i] = unserialize(rtrim($this->pDatLines[$i]));
            // ID出現回数をカウント
            if ($_exconf['flex']['idpopup'] &&
                !empty($this->pDatLines[$i]['p_dateid']['id']) &&
                !strstr($this->pDatLines[$i]['p_dateid']['id'], '???')
            ) {
                $id = $this->pDatLines[$i]['p_dateid']['id'];
                if (!isset($this->thread->idcount[$id])) {
                    $this->thread->idcount[$id] = 1;
                } else {
                    $this->thread->idcount[$id]++;
                }
            }
            return FALSE;
        }

        // {{{ parseDatLine - パース

        // 2ch仕様では<>のみ実体参照になるので、&"も実体参照にする
        $line = preg_replace('/(&(?!#?\w+;)|")(?![^<]*>)/e', 'htmlspecialchars("$1")', $this->thread->datlines[$i-1]);

        // 分割
        $resar = $this->thread->explodeDatLine($line);

        // 配列を初期化
        $this->pDatLines[$i] = array(
            'name'      => $resar[0],
            'mail'      => $resar[1],
            'date_id'   => $resar[2],
            'p_dateid'  => $this->parseDateId($resar[2]),
            'timestamp' => NULL,
            'msg'       => $resar[3],
            'lines'     => 0,
            'refs'      => array(),
            'refr'      => array(),
            'parent'    => 0,
            'depth'     => 0,
        );

        // ID出現回数をカウント
        if ($_exconf['flex']['idpopup'] &&
            !empty($this->pDatLines[$i]['p_dateid']['id']) &&
            !strstr($this->pDatLines[$i]['p_dateid']['id'], '???')
        ) {
            $id = $this->pDatLines[$i]['p_dateid']['id'];
            if (!isset($this->thread->idcount[$id])) {
                $this->thread->idcount[$id] = 1;
            } else {
                $this->thread->idcount[$id]++;
            }
        }

        // 投稿時刻のUNIXタイムスタンプ
        $this->pDatLines[$i]['timestamp'] = &$this->pDatLines[$i]['p_dateid']['epoch'];

        // 行数カウント
        $this->pDatLines[$i]['lines'] = preg_match_all('/<br[^>]*?>/i', $this->pDatLines[$i]['msg'], $breakes) + 1;

        // }}}
        // {{{ parseDatLine - ツリー解析

        // 参照レスチェックの準備
        $refChecker = str_replace(
            array('＞', '、'),
            array('&gt;', ','),
            strip_tags($this->pDatLines[$i]['msg'], '<br>')
        );
        if (preg_match('/^(?:&gt;|＞)*([1-9]\\d{0,3})$/', trim($this->pDatLines[$i]['matching']['name']), $numInName)) {
            $refChecker .= ' &gt;&gt;' . $numInName[1];
        }

        // 参照レス検索パターン（>>1 >>2-10 >>2,5,8 >>200- >>108=198=216 などにマッチする）
        $ref_reg_expr = '/(?<=(?:&gt;))([1-9]\\d{0,3})((?: ?[,=] ?[1-9]\\d{0,3})+|-(?:[1-9]\\d{0,3})?)?(?=\\D|$)/';

        if (preg_match_all($ref_reg_expr, $refChecker, $matches, PREG_SET_ORDER)) {
            // 参照しているレス番号のうち最初に出てくるものを親レスとする
            $parent = 0;
            $refs = array();
            $refr = array();
            $done = array();
            foreach ($matches as $ref) {
                if (isset($done[$ref[0]])) {
                    continue;
                }
                if (isset($ref[2])) {
                    switch (substr($ref[2], 0, 1)) {
                        case ' ':
                        case ',':
                        case '=':
                            $refs = array_merge($refs, preg_split('/\\D+/', $ref[0]));
                            break;
                        case '-':
                            $from = (int)$ref[1];
                            $to   = (int)substr($ref[2], 1);
                            if (!$parent) {
                                $parent = $from;
                            }
                            if (!$to) {
                                $refr[] = array('from' => $from);
                            } elseif ($from < $to) {
                                $refr[] = array('from' => $from, 'to' => $to);
                            } else {
                                $refs[] = $from;
                            }
                            break;
                    }
                } else {
                    if (!$parent) {
                        $parent = (int)$ref[0];
                    }
                    $refs[] = $ref[0];
                }
                $done[$ref[0]] = TRUE;
            }
            if ($refs) {
                // 連鎖検出に使うarray_intersect()が型を考慮した比較をするので一括で整数型にキャスト
                $refs = array_map('intval', $refs);
                $refs = array_unique($refs);
                sort($refs);
                $this->pDatLines[$i]['refs'] = $refs;
            }
            if ($refr) {
                $this->pDatLines[$i]['refr'] = $refr;
            }
            // 自分よりレス番号が小さいレスを参照しているときだけ親子関係を築く
            if (0 < $parent && $parent < $i) {
                $this->pDatLines[$i]['depth'] = $this->pDatLines[$parent]['depth'] + 1;
                $this->pDatLines[$i]['parent'] = $parent;
            }
        }
        // 親レスが無く、前のレスが同じIDの投稿のときは、そのレスを親レスとする。
        if (!$this->pDatLines[$i]['parent'] &&
            !empty($this->pDatLines[$i]['p_dateid']['id']) &&
            !strstr($this->pDatLines[$i]['p_dateid']['id'], '???')
        ) {
            $parent = 0;
            for ($j = $i - 1; $j > 0; $j--) {
                //下のコメント二つを外せば間に異なるIDを挟む同じIDを探せるようになるけど
                //ツリー表示の時にレスのつながりが分かりにくくなるので却下した。
                //if ($this->pDatLines[$j]['p_dateid']['date'] == $this->pDatLines[$i]['p_dateid']['date']) {
                    if ($this->pDatLines[$j]['p_dateid']['id'] == $this->pDatLines[$i]['p_dateid']['id']) {
                        $parent = $j;
                //  }
                } else {
                    break;
                }
            }
            if ($parent) {
                $this->pDatLines[$i]['depth'] = $this->pDatLines[$parent]['depth'] + 1;
                $this->pDatLines[$i]['parent'] = $parent;
            }
        }


        // }}}

        return TRUE;
    }

    // }}}
    // {{{ parseDateId()

    /**
     * 日付・IDをパースする
     *
     * 第二引数を真にすると投稿日時からUNIXタイムスタンプを計算する
     */
    function parseDateId($date_id)
    {
        $orig_date_id = $date_id;

        // IDを解析するための正規表現etc
        $idchars = '0-9A-Za-z\/.+';
        $idregex = '/^(['.$idchars.']{8,11}|\?\?\?)([,.0O]|†)?(.*)$/';

        // したらばIDを2ch形式に
        $date_id = preg_replace('/\[ (['.$idchars.']{8,11}|\?\?\?) \]/', 'ID:$1', $date_id);

        // 最新のBeIDパターン（lvBは予備で、使われていない）
        $beregex = '/<(BE:(?P<id>\d+):((?P<lvA>\d+)|(?P<lvB>#+)))>/';
        // 最初期のBeIDパターン（直書き型と関数型）
        $belink_pattern = array(
            '/<a href=(["\'])?.*?http:\/\/be\.2ch\.net\/test\/p\.php\?i=(\d+)&.*?(?(1)\1)>\?(#*)<\/a>/',
            '/<a href=(["\'])?javascript:be\((\d+)\);(?(1)\1)>\?(#*)<\/a>/'
        );

        // BeIDの例外処理
        if (preg_match($beregex, $date_id, $matches)) {
            $hidden_be['be'] = $matches[1];
            $hidden_be['beid'] = $matches['id'];
            $hidden_be['belv'] = 0;
            if (isset($matches['lvA'])) {
                $belv = (int)$matches['lvA'];
            } elseif (isset($matches['lvB'])) {
                $hidden_be['belv'] = strlen($matches['lvB']);
            }
            $date_id = preg_replace($beregex, '', $date_id);
        } else {
            $date_id = preg_replace($belink_pattern, 'BE:$1-$2', $date_id);
        }

        // 板によってIDとBEの順序が逆だったりするので分解する
        $p_dateid = preg_split('/\s+(ID|BE): ?/', trim($date_id), -1, PREG_SPLIT_DELIM_CAPTURE);
        $c_dateid = count($p_dateid);

        for ($i = 0; $i < $c_dateid; $i++) {
            $elem = $p_dateid[$i];
            if ($i == 0) {
                // 投稿日時欄をスペースで分割
                if (P2_MBREGEX_AVAILABLE) {
                    $date_time = mb_split('\s+', $elem, 2);
                } else {
                    $date_time = preg_split('/\s+/u', mb_convert_encoding($elem, 'UTF-8', 'SJIS-win'), 2);
                    mb_convert_variables('SJIS-win', 'UTF-8', $date_time);
                }
                // 最初の要素を日付と仮定する
                $p_dateid['date'] = $date_time[0];
                $p_dateid['time'] = $date_time[1];
                $p_dateid['epoch'] = NULL;
            } elseif ($elem == 'ID' || $elem == 'BE') {
                $dateid_key = strtolower($elem);
            } else {
                switch ($dateid_key) {
                    case 'id':
                        if (preg_match($idregex, $elem, $matches)) {
                            $p_dateid['id'] = $matches[1] . $matches[2];
                            $p_dateid['idopt'] = $matches[3];
                        } else {
                            $p_dateid['id'] = $elem;
                        }
                        break;
                    case 'be':
                        if (preg_match('/^(\d+)-(#*)$/', $elem, $matches)) {
                            $p_dateid['be'] = $elem;
                            $p_dateid['beid'] = $matches[1];
                            $p_dateid['belv'] = strlen($matches[2]);
                        }
                        break;
                }
                $dateid_key = NULL;
            }
        }

        if (isset($hidden_be)){
            $p_dateid = array_merge($p_dateid, $hidden_be);
        }

        $p_dateid['orig'] = $orig_date_id;

        return $p_dateid;
    }

    // }}}
    // {{{ datetimeToEpoch()

    /**
     * 日付からUNIXタイムスタンプを計算する
     */
    function datetimeToEpoch($date, $time)
    {
        if (preg_match('/((\D+)?(\d+))[\/\-]([01]?\d)[\/\-]([0-3]?\d)/', $date, $d)) {
            if ($d[2] || !preg_match('/(?:19)?9[89]|(?:20)?0[0-9]/', $d[2])) {
                $d[3] = $this->adjustYMDOffset($d[1], $d[3]);
            }
            if (preg_match('/[0-2]?\d:[0-5]?\d(:[0-5]?\d)?/', $time, $t)) {
                $a = $t[0];
            } else {
                $a = '';
            }
            $date_format = sprintf('%s-%s-%s %s JST', $d[3], $d[4], $d[5], $a);
            $epoch = strtotime($date_format); // 失敗したときは -1 が代入される
        } else {
            $epoch = -1;
        }

        return $epoch;
    }

    // }}}
    // {{{ loadYmdFromSettingTxt()

    /**
     * SETTING.TXTから暦設定を読み込む
     */

    function loadYmdFromSettingTxt()
    {
        $setting_txt = dirname($this->thread->keydat) . '/SETTING.TXT';
        $settings = array();
        if (file_exists($setting_txt)) {
            $setting_lines = array_map('rtrim', file($setting_txt));
            $setting_ymd = preg_grep('/^BBS_YMD_/', $setting_lines);
            foreach ($setting_ymd as $line) {
                list($key, $value) = @explode('=', $line, 2);
                $settings[$key] = $value;
            }
            if (isset($settings['BBS_YMD_NAME']) && isset($settings['BBS_YMD_OFFSET'])) {
                $this->custom_year = array('name'   => $settings['BBS_YMD_NAME'],
                                           'offset' => (int)$settings['BBS_YMD_OFFSET']);
            }
        }
    }

    // }}}
    // {{{ adjustYMDOffset()

    /**
     * BBS_YMD_OFFSETを補正する
     */
    function adjustYMDOffset($koyomi, $year)
    {
        if (isset($this->custom_year)) {
            $name   = $this->custom_year['name'];
            $offset = $this->custom_year['offset'];

            $namepart = substr($koyomi, 0, strlen($name));
            $yearpart = substr($koyomi, strlen($name));
            $yf = strlen($yearpart);
            if ($namepart == $name && is_numeric($yearpart) && ($yf == 2 || $yf == 4)) {
                return $this->adjustYear($yearpart, $offset);
            }
        }

        global $bbs_ymd_offset;

        foreach ($bbs_ymd_offset as $name => $offset) {
            $namepart = substr($koyomi, 0, strlen($name));
            $yearpart = substr($koyomi, strlen($name));
            $yf = strlen($yearpart);
            if ($namepart == $name && is_numeric($yearpart) && ($yf == 2 || $yf == 4)) {
                return $this->adjustYear($yearpart, $offset);
            }
        }

        return $year;
    }

    // }}}
    // {{{ adjustYear()

    /**
     * 暦を補正する
     */
    function adjustYear($yearpart, $offset)
    {
        $year = (int)$yearpart - $offset;
        if ($year < 10) {
            $year = sprintf('%02d', $year);
        }
        return (string)$year;
    }

    // }}}
    // {{{ filterMatch()

    /**
     * レスフィルタリング
     */
    function filterMatch($resnum)
    {
        global $_conf, $res_filter, $word_fm, $words_fm;
        global $filter_hits, $last_hit_resnum, $filter_range;

        if (!$word_fm) {
            return FALSE;
        }

        // {{{ filterMatch - ターゲットの設定

        switch ($res_filter['field']) {
            case 'msg':
                $target = $this->pDatLines[$resnum]['msg'];
                break;
            case 'name':
                $target = strip_tags($this->pDatLines[$resnum]['name']);
                break;
            case 'mail':
                $target = $this->pDatLines[$resnum]['mail'];
                break;
            case 'date':
                $target = $this->pDatLines[$resnum]['p_dateid']['date'] . ' ' . $this->pDatLines[$resnum]['p_dateid']['time'];
                break;
            /*case 'epoch':
                if (!isset($this->pDatLines[$resnum]['p_dateid']['epoch']) || 
                    $this->pDatLines[$resnum]['p_dateid']['epoch'] == -1
                ) {
                    return FALSE;
                }
                $target = $this->pDatLines[$resnum]['p_dateid']['epoch'];
                break;*/
            case 'id':
                if (!isset($this->pDatLines[$resnum]['p_dateid']['id'])) {
                    return FALSE;
                }
                $target = $this->pDatLines[$resnum]['p_dateid']['id'];
                break;
            /*case 'beid':
                if (!isset($this->pDatLines[$resnum]['p_dateid']['beid'])) {
                    return FALSE;
                }
                $target = $this->pDatLines[$resnum]['p_dateid']['beid'];
                break;*/
            case 'belv':
                if (!isset($this->pDatLines[$resnum]['p_dateid']['belv'])) {
                    return FALSE;
                }
                $target = $this->pDatLines[$resnum]['p_dateid']['belv'];
                break;
            default: // hole
                $target = implode(
                    '<>',
                    array($i,
                        $this->pDatLines[$resnum]['name'],
                        $this->pDatLines[$resnum]['mail'],
                        $this->pDatLines[$resnum]['p_dateid']['original'],
                        $this->pDatLines[$resnum]['msg']
                    )
                );
        }

        // }}}
        // {{{ filterMatch - マッチング

        // マッチしないレスを表示 -> マッチング結果が真なら失敗 -> $failed = TRUE;
        $failed = ($res_filter['match'] == 'off') ? TRUE : FALSE;

        // BE のポイントでフィルタリング
        if ($res_filter['field'] == 'belv') {
            if (is_numeric($word_fm)) {
                if ((intval($word_fm) >= $target) == $failed) {
                    return FALSE;
                }
            } else {
                if ((strlen(preg_replace('/[^#]/', '', $word_fm)) >= $target) == $failed) {
                    return FALSE;
                }
            }

        // すべてのワードにマッチする/しない
        } elseif ($res_filter['method'] == 'and') {
            $words_fm_hit = 0;
            foreach ($words_fm as $word_fm_ao) {
                if (StrCtl::filterMatch($word_fm_ao, $target) == $failed) {
                    if ($res_filter['match'] == 'on') {
                        return FALSE;
                    } else {
                        $words_fm_hit++;
                    }
                }
            }
            if ($words_fm_hit == count($words_fm)) {
                return FALSE;
            }

        // その他
        } elseif (StrCtl::filterMatch($word_fm, $target) == $failed) {
            return FALSE;
        }

        $filter_hits++;
        if ($_conf['filtering'] && !empty($filter_range) &&
            ($filter_hits < $filter_range['start'] || $filter_hits > $filter_range['to'])
        ) {
            return FALSE;
        }
        $last_hit_resnum = $resnum;

        if (strtolower(get_class($this)) != 'showthreadk') {
            echo <<<EOP
<script type="text/javascript">
filterCount({$filter_hits});
</script>\n
EOP;
        }

        // }}}

        return TRUE;
    }

    // }}}
}

// }}}

?>
