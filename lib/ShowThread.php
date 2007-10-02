<?php
/**
 * スレッドを表示する クラス
 */
class ShowThread
{
    var $thread; // スレッドオブジェクトの参照
    
    var $str_to_link_regex; // リンクすべき文字列の正規表現
    var $str_to_link_limit = 30; // 一つのレスにおけるリンク変換の制限回数（荒らし対策）
    
    // URLを処理する関数・メソッド名などを格納する配列（デフォルト）
    var $url_handlers       = array();

    // URLを処理する関数・メソッド名などを格納する配列（ユーザ定義、デフォルトのものより優先）
    var $user_url_handlers  = array();

    /**
     * @constructor
     */
    function ShowThread(&$aThread)
    {
        // スレッドオブジェクトの参照を登録
        $this->thread = &$aThread;
        
        $this->str_to_link_regex = '{'
            . '(?P<link>(<[Aa] .+?>)(.*?)(</[Aa]>))' // リンク（PCREの特性上、必ずこのパターンを最初に試行する）
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
            .       '(ftp|h?t?tps?)://([0-9A-Za-z][\\w!#%&+*,\\-./:;=?@\\[\\]^~]+)' // URL
            .   ')'
            . '|'
            .   '(?P<id>ID: ?([0-9A-Za-z/.+]{8,11})(?=[^0-9A-Za-z/.+]|$))' // ID（8,10桁 +PC/携帯識別フラグ）
            . ')'
            . '}';

        if (empty($GLOBALS['_P2_NGABORN_LOADED'])) {
            NgAbornCtl::loadNgAborns();
        }
    }
    
    /**
     * DatをHTML変換して表示する
     * （継承先クラスで実装）
     *
     * @access  public
     * @return  boolean
     */
    function datToHtml()
    {
    }
    
    /**
     * DatをHTML変換したものを取得する
     *
     * @access  public
     * @return  string
     */
    function getDatToHtml()
    {
        ob_start();
        $this->datToHtml();
        $html = ob_get_clean();
        
        return $html;
    }

    /**
     * BEプロファイルリンク変換
     *
     * @access  protected
     * @param   string     $data_id  2006/10/20(金) 11:46:08 ID:YS696rnVP BE:32616498-DIA(30003)
     * @param   integer    $i        レス番号
     * @return  string
     */
    function replaceBeId($date_id, $i)
    {
        global $_conf;
        
        $beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/test/read.cgi/{$this->thread->bbs}/{$this->thread->key}/{$i}\"{$_conf['ext_win_target_at']}>Lv.\$2</a>";
        
        // <BE:23457986:1>
        $be_match = '|<BE:(\d+):(\d+)>|i';
        if (preg_match($be_match, $date_id)) {
            $date_id = preg_replace($be_match, $beid_replace, $date_id);
        
        // 2006/10/20(金) 11:46:08 ID:YS696rnVP BE:32616498-DIA(30003)
        } else {
            $beid_replace = "<a href=\"http://be.2ch.net/test/p.php?i=\$1&u=d:http://{$this->thread->host}/test/read.cgi/{$this->thread->bbs}/{$this->thread->key}/{$i}\"{$_conf['ext_win_target_at']}>?\$2</a>";
            $date_id = preg_replace('|BE: ?(\d+)-(#*)|i', $beid_replace, $date_id);
        }
        
        return $date_id;
    }
    
    /**
     * レスのあぼーんをまとめてチェックする（名前、メール、日付、メッセージ）
     *
     * @access  protected
     * @return  string|false  マッチしたらマッチ文字列。マッチしなければfalse
     */
    function checkAborns($name, $mail, $data_id, $msg)
    {
        return NgAbornCtl::checkAborns($name, $mail, $data_id, $msg, $this->thread->bbs, $this->thread->ttitle_hc);
    }
    
    /**
     * NGあぼーんをチェックする
     *
     * @access  protected
     * @return  string|false  マッチしたらマッチ文字列。マッチしなければfalse
     */
    function ngAbornCheck($ngcode, $subject)
    {
        return NgAbornCtl::ngAbornCheck($ngcode, $subject, $this->thread->bbs, $this->thread->ttitle_hc);
    }
    
    /**
     * 特定レスの透明あぼーんチェック
     *
     * @access  protected
     * @return  boolean
     */
    function abornResCheck($resnum)
    {
        $t = $this->thread;

        return NgAbornCtl::abornResCheck($t->host, $t->bbs, $t->key, $resnum);
    }

    /**
     * ユーザ定義URLハンドラ（メッセージ中のURLを書き換える関数）を追加する
     *
     * ハンドラは最初に追加されたものから順番に試行される
     * URLはハンドラの返り値（文字列）で置換される
     * FALSEを帰した場合は次のハンドラに処理が委ねられる
     *
     * ユーザ定義URLハンドラの引数は
     *  1. string $url  URL
     *  2. array  $purl URLをparse_url()したもの
     *  3. string $str  パターンにマッチした文字列、URLと同じことが多い
     *  4. object &$aShowThread 呼び出し元のオブジェクト
     * である
     * 常にFALSEを返し、内部で処理するだけの関数を登録してもよい
     *
     * @param   string|array $function  関数名か、array(string $classname, string $methodname)
     *                                  もしくは array(object $instance, string $methodname)
     * @return  void
     * @access  public
     * @todo    ユーザ定義URLハンドラのオートロード機能を実装
     */
    function addURLHandler($function)
    {
        $this->user_url_handlers[] = $function;
    }
    
    /**
     * レスフィルタリングのターゲット文字列を得る
     *
     * @access  protected
     * @return  string
     */
    function getFilterTarget($i, $name, $mail, $date_id, $msg)
    {
        switch ($GLOBALS['res_filter']['field']) {
            case 'name':
                $target = $name;
                break;
            case 'mail':
                $target = $mail;
                break;
            case 'date':
                $target = preg_replace('| ?ID:[0-9A-Za-z/.+?]+.*$|', '', $date_id);
                break;
            case 'id':
                if ($target = preg_replace('|^.*ID:([0-9A-Za-z/.+?]+).*$|', '$1', $date_id)) {
                    break;
                } else {
                    return '';
                }
            case 'msg':
                $target = $msg;
                break;
            default: // 'hole'
                // 省略前の文字列が入るので $ares はダメになった
                // $target = strval($i) . '<>' . $ares;
                $target = strval($i) . '<>' . $name . '<>' . $mail . '<>' . $date_id . '<>' . $msg;
        }

        // '<>' だけ許可
        $target = strip_tags($target, '<>');
        
        return $target;
    }

    /**
     * レスフィルタリングのマッチ判定
     *
     * @access  protected
     * @return  boolean     マッチしたらtrueを返す
     */
    function filterMatch($target, $resnum)
    {
        global $_conf;
        global $filter_hits, $filter_range;
        
        $failed = ($GLOBALS['res_filter']['match'] == 'off') ? TRUE : FALSE;
        
        if ($GLOBALS['res_filter']['method'] == 'and') {
            $words_fm_hit = 0;
            foreach ($GLOBALS['words_fm'] as $word_fm_ao) {
                if (StrCtl::filterMatch($word_fm_ao, $target) == $failed) {
                    if ($GLOBALS['res_filter']['match'] != 'off') {
                        return false;
                    } else {
                        $words_fm_hit++;
                    }
                }
            }
            if ($words_fm_hit == count($GLOBALS['words_fm'])) {
                return false;
            }
        } else {
            if (StrCtl::filterMatch($GLOBALS['word_fm'], $target) == $failed) {
                return false;
            }
        }
        
        $GLOBALS['filter_hits']++;
        
        // 表示範囲外なら偽判定とする
        if (isset($GLOBALS['word']) && !empty($filter_range) &&
            ($filter_hits < $filter_range['start'] || $filter_hits > $filter_range['to'])
        ) {
            return false;
        }
        
        $GLOBALS['last_hit_resnum'] = $resnum;

        if (!$_conf['ktai']) {
            echo <<<EOP
<script type="text/javascript">
<!--
filterCount({$GLOBALS['filter_hits']});
-->
</script>\n
EOP;
        }
        
        return true;
    }
}
