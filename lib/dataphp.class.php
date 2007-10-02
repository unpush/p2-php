<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

/*
    2006/02/24 aki DataPhp形式はもう使わない方向。拡張子 .cgi を代替とする
    
    データファイルにWebから直接アクセスされても中をみられないようにphp形式のファイルでデータを取り扱うクラス
    インスタンスを作らずにスタティックメソッドで利用する。ファイルの保存形式は、以下のような感じ。
    
    ＜？php ／*
    データ
    *／ ？＞
*/
class DataPhp
{
    /**
     * @access  private
     * @return  string
     */
    function getPre()
    {
        return "<?php /*\n";
    }

    /**
     * @access  private
     * @return  string
     */
    function getHip()
    {
        return "\n*/ ?>";
    }

    /**
     * データphp形式のファイルを読み込む
     * 文字列のアンエスケープも行う
     *
     * @access  public
     * @return  string|false
     */
    function getDataPhpCont($data_php)
    {
        if (!$cont = @file_get_contents($data_php)) {
            // 読み込みエラーならfalse、空っぽなら""を返す
            return $cont;
            
        } else {
            $pre_quote = preg_quote(DataPhp::getPre());
            $hip_quote = preg_quote(DataPhp::getHip());
            // 先頭文と末文を削除
            if (preg_match("{".$pre_quote."(.*?)".$hip_quote.".*}s", $cont, $m)) {
                $cont = $m[1];
            } else {
                return false;
            }

            // アンエスケープする
            $cont = DataPhp::unescapeDataPhp($cont);

            return $cont;
        }
    }
    
    /**
     * データphp形式のファイルをラインで読み込む
     * 文字列のアンエスケープも行う
     *
     * @static
     * @access  public
     * @return  array|false
     */
    function fileDataPhp($data_php)
    {
        if (!$cont = DataPhp::getDataPhpCont($data_php)) {
            // 読み込みエラーならfalse、空っぽなら空配列を返す
            if ($cont === false) {
                return false;
            } else {
                return array();
            }
        } else {
            // 行データに変換
            $lines = array();
            
            $lines = explode("\n", $cont);
            $count = count($lines);
            
            $i = 1;
            foreach ($lines as $l) {
                if ($i != $count) {
                    $newlines[] = $l."\n";
                // 最終行なら
                } else {
                    // 空っぽでなければ追加
                    if ($l !== "") {
                        $newlines[] = $l;
                    }
                    break;
                }
                $i++;
            }
            
            /*
            if ($lines) {
                // 末尾の空行は特別に削除する
                $count = count($lines);
                if (rtrim($lines[$count-1]) == "") {
                    array_pop($lines);
                }
            }
            */
            
            return $newlines;
        }
    }

    /**
     * データphp形式のファイルにデータを記録する（モードはwb）
     * 文字列のエスケープも行う
     *
     * @static
     * @access  public
     * @param   srting   $cont  記録するデータ文字列
     * @return  boolean
     */
    function writeDataPhp($data_php, &$cont, $perm = 0606)
    {
        // &<>/ を &xxx; にエスケープして
        $new_cont = DataPhp::escapeDataPhp($cont);
        
        // 先頭文と末文を追加
        $new_cont = DataPhp::getPre() . $new_cont . DataPhp::getHip();
        
        if (false === FileCtl::make_datafile($data_php, $perm)) {
            return false;
        }
        
        // 書き込む
        if (!$fp = fopen($data_php, 'wb')) {
             trigger_error("fopen($data_php)", E_USER_WARNING);
             die("Error: ファイルを更新できませんでした");
             return false;
        }
        @flock($fp, LOCK_EX);
        $last = ignore_user_abort(1);
        ftruncate($fp, 0);
        fwrite($fp, $new_cont);
        ignore_user_abort($last);
        @flock($fp, LOCK_UN);
        fclose($fp);
        
        return true;
    }
    
    /**
     * データphp形式のファイルで、末尾にデータを追加する
     *
     * @static
     * @return  boolean
     */
    function putDataPhp($data_php, &$cont, $perm = 0606, $ncheck = false)
    {
        if ($cont === "") {
            return true;
        }
        
        $pre_quote = preg_quote(DataPhp::getPre());
        $hip_quote = preg_quote(DataPhp::getHip());

        $cont_esc = DataPhp::escapeDataPhp($cont);

        $old_cont = @file_get_contents($data_php);
        if ($old_cont) {
            // ファイルが、データphp形式以外の場合は、何もせずにfalseを返す
            if (!preg_match("/^\s*<\?php\s\/\*/", $old_cont)) {
                trigger_error('putDataPhp() file is broken.', E_USER_WARNING);
                return false;
            }
            
            $old_cut = preg_replace('{'.$hip_quote.'.*$}s', '', $old_cont);
            
            // 指定に応じて、古い内容の末尾が改行でなければ、改行を追加する
            if ($ncheck) {
                if (substr($old_cut, -1) != "\n") {
                    $old_cut .= "\n";
                }
            }
            
            $new_cont = $old_cut . $cont_esc . DataPhp::getHip();
            
        // データ内容がまだなければ、新規データphp
        } else {
            $new_cont = DataPhp::getPre() . $cont_esc . DataPhp::getHip();
        }
        
        FileCtl::make_datafile($data_php, $perm);
        
        if (!$fp = fopen($data_php, 'wb')) {
            die("Error: ファイルを更新できませんでした");
            return false;
        }
        @flock($fp, LOCK_EX);
        $last = ignore_user_abort(1);
        ftruncate($fp, 0);
        fwrite($fp, $new_cont);
        ignore_user_abort($last);
        @flock($fp, LOCK_UN);
        fclose($fp);
        
        return true;
    }
    
    /**
     * データphp形式のデータをエスケープする
     *
     * @static
     * @access  private
     * @return  string
     */
    function escapeDataPhp($str)
    {
        // &<>/ → &xxx; のエスケープをする
        $str = str_replace("&", "&amp;", $str);
        $str = str_replace("<", "&lt;", $str);
        $str = str_replace(">", "&gt;", $str);
        $str = str_replace("/", "&frasl;", $str);
        return $str;
    }

    /**
     * データphp形式のデータをアンエスケープする
     *
     * @static
     * @access  private
     * @return  string
     */
    function unescapeDataPhp($str)
    {
        // &<>/ → &xxx; のエスケープを元に戻す
        $str = str_replace('&lt;', '<', $str);
        $str = str_replace('&gt;', '>', $str);
        $str = str_replace('&frasl;', '/', $str);
        $str = str_replace('&amp;', '&', $str);
        return $str;
    }

}
