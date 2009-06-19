<?php
/**
 * ファイルを操作するクラス
 * staticメソッドで利用する
 */
class FileCtl
{
    /**
     * ファイルがなければ生成し、書き込み権限がなければパーミッションを調整する
     * （既にファイルがあり、書き込み権限もある場合は、何もしない。※modifiedの更新もしない）
     *
     * @static
     * @access  public
     *
     * @param   boolean  $die  true なら、エラーでただちに終了する
     * @return  boolean  問題がなければtrue
     */
    function make_datafile($file, $perm = 0606, $die = true)
    {
        $me = __CLASS__ . "::" . __FUNCTION__ . "()";
        
        // 引数チェック
        if (strlen($file) == 0) {
            trigger_error("$me, file is null", E_USER_WARNING);
            return false;
        }
        if (empty($perm)) {
            trigger_error("$me, empty perm. ( $file )", E_USER_WARNING);
            $die and die("Error: $me, empty perm");
            return false;
        }
        
        // ファイルがなければ
        if (!file_exists($file)) {
            if (!FileCtl::mkdirFor($file)) {
                $die and die("Error: $me -> FileCtl::mkdirFor() failed.");
                return false;
            }
            if (!touch($file)) {
                $die and die("Error: $me -> touch() failed.");
                return false;
            }
            chmod($file, $perm);
        
        // ファイルがあれば
        } else {
            if (!is_writable($file)) {
                if (false === $cont = file_get_contents($file)) {
                    $die and die("Error: $me -> file_get_contents() failed.");
                    return false;
                }
                unlink($file);
                if (false === file_put_contents($file, $cont, LOCK_EX)) {
                    // 備忘メモ: $file が nullの時、file_put_contents() はfalseを返すがwaringは出さないので注意
                    // ここでは $file は約束されているが…
                    $die and die("Error: $me -> file_put_contents() failed.");
                    return false;
                }
                chmod($file, $perm);
            }
        }
        return true;
    }
    
    /**
     * 指定ディレクトリがなければ（再帰的に）生成して、パーミッションの調整も行う
     * PHP 5.0.0 以上であれば、mkdir() で recursive パラメータが追加されている。  
     *
     * @access  public
     * @param   integer  $perm  パーミッション ex) 0707
     * @param   boolean  $die   true なら、エラーが生じた時点で、ただちにdieする
     * @return  boolean  実行成否。※既にディレクトリが存在している時もtrueを返す。
     */
    function mkdirR($dir, $perm = null, $die = true)
    {
        return FileCtl::_mkdirR($dir, $perm, $die, 0);
    }
    
    /**
     * mkdirR() の実処理を行う
     *
     * @access  private
     * @param   integer  $rtimes  再帰呼び出しされている現在回数
     * @return  boolean
     */
    function _mkdirR($dir, $perm = null, $die = true, $rtimes = 0)
    {
        global $_conf;
        
        $me = __CLASS__ . "::" . __FUNCTION__ . "()";
        
        // 引数エラー
        if (strlen($dir) == 0) {
            trigger_error("$me cannot mkdir. no dirname", E_USER_WARNING);
            $die and die('Error');
        }
        
        // 既にディレクトリが存在している時は、そのままでOK
        if (is_dir($dir)) {
            return true;
        }
        
        if (empty($perm)) {
            $perm = empty($_conf['data_dir_perm']) ? 0707 : $_conf['data_dir_perm'];
        }
        
        $dir_limit = 50; // 親階層を上る制限回数
        
        // 再帰超過エラー
        if ($rtimes > $dir_limit) {
            trigger_error("$me cannot mkdir. ($dir) too match up dir! I'm very tired.", E_USER_WARNING);
            $die and die('Error');
            return false;
        }
        
        // 親から先に再帰実行
        if (!FileCtl::_mkdirR(dirname($dir), $perm, $die, ++$rtimes)) {
            $die and die('Error: FileCtl::_mkdirR()');
            return false;
        }
        
        if (!mkdir($dir, $perm)) {
            trigger_error("$me -> mkdir failed, $dir", E_USER_WARNING);
            $die and die('Error: mkdir()');
            return false;
        }
        chmod($dir, $perm);

        return true;
    }
    
    /**
     * 指定したパスの親ディレクトリがなければ（再帰的に）生成して、パーミッションの調整も行う
     * mkdirR()にdirname()して送っているだけなので、このメソッドはなくてもいいかもしれない。 
     *
     * @static
     * @access  public
     * @return  boolean
     */
    function mkdirFor($apath)
    {
        return FileCtl::mkdirR(dirname($apath));
    }
    
    // {{{ file_read_lines()

    /**
     * ファイル全体を読み込んで配列に格納する
     * エラー抑制付きの @file() の代用
     *
     * @param string $filename
     * @param int $flags
     * @param resource $context
     */
    static public function file_read_lines($filename, $flags = 0, $context = null)
    {
        if (!is_readable($filename)) {
            return false;
        }
        $lines = file($filename, $flags, $context);
        if (($flags & FILE_IGNORE_NEW_LINES) && $lines &&
            strlen($lines[0]) && substr($lines[0], -1) == "\r")
        {
            $lines = array_map(create_function('$l', 'return rtrim($l, "\\r");'), $lines);
            if ($flags & FILE_SKIP_EMPTY_LINES) {
                $lines = array_filter($lines, 'strlen');
            }
        }
        return $lines;
    }

    // }}}
    
    /**
     * gzファイルの中身を取得する
     *
     * @static
     * @access  public
     * @return  string|false
     */
    function getGzFileContents($filepath)
    {
        if (is_readable($filepath)) {
            ob_start();
            readgzfile($filepath);
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }
        
        return false;
    }

    /**
     * Windowsでは上書きの rename() でエラーが出るようなので、そのエラーを回避したrename()
     * ※ただし、unlink() と rename() の間で一瞬の間が空くので完全ではない。
     * 参考 http://ns1.php.gr.jp/pipermail/php-users/2005-October/027827.html
     *
     * @return  boolean
     */
    function rename($src_file, $dest_file)
    {
        $win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? true : false;
        
        if ($win) {
            if (file_exists($dest_file) and is_writable($dest_file) and unlink($dest_file)) {
                return rename($src_file, $dest_file);
            } else {
                return false;
            }
        }
        return rename($src_file, $dest_file);
    }

    /**
     * 書き込み中の不完全なファイル内容が読み取られることのないように、一時ファイルに書き込んでからリネームする
     * ※ただし、Windowsの場合は、上書きrenameが不完全となるので直接書き込むこととする
     *
     * @static
     * @access  public
     * @param   string   $tmp_dir  一時保存ディレクトリ
     * @return  boolean  実行成否 （成功時に書き込みバイト数を返す意味ってほとんどない気がする）
     */
    function filePutRename($file, $cont, $tmp_dir = null)
    {
        if (strlen($file) == 0) {
            trigger_error(__CLASS__ . '::' . __FUNCTION__ . '(), file is null', E_USER_WARNING);
            return false;
        }
        
        $win = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? true : false;
        
        // 一時ファイルパスを決める
        $prefix = 'rename_';
        
        // 一時ディレクトリの明示指定がある場合
        if ($tmp_dir) { // strlen($tmp_dir) > 0 とすべきところだが、むしろ"0"もなしということにしてみる
            if (!is_dir($tmp_dir)) {
                trigger_error(__FUNCTION__ . "() -> is_dir($tmp_dir) failed.", E_USER_WARNING);
                return false;
            }
        
        } else {
            if (isset($GLOBALS['_conf']['tmp_dir'])) {
                $tmp_dir = $GLOBALS['_conf']['tmp_dir'];
                if (!is_dir($tmp_dir)) {
                    FileCtl::mkdirR($tmp_dir);
                }
            } else {
                // 2006/10/05 php_get_tmpdir() は might be only in CVS
                if (function_exists('php_get_tmpdir')) {
                    $tmp_dir = php_get_tmpdir();
                } else {
                    // これで動作はするが、null指定でも大丈夫かな。2007/01/22 WinではNG?未確認
                    $tmp_dir = null;
                }
            }
        }

        $write_file = $win ? $file : tempnam($tmp_dir, $prefix);
        
        if (false === $r = file_put_contents($write_file, $cont, LOCK_EX)) {
            return false;
        }
        if (!$win) {
            if (!rename($write_file, $file)) {
                return false;
            }
        }
        return true;
    }
    
    // {{{ scandirR()

    /**
     * 再帰的にディレクトリを走査する
     *
     * リストをファイルとディレクトリに分けて返す。それそれのリストは単純な配列
     *
     * @static
     * @access  public
     * @return  array|false
     */
    function scandirR($dir)
    {
        $dir = realpath($dir);
        $list = array('files' => array(), 'dirs' => array());
        $files = scandir($dir);
        if ($files === false) {
            return false;
        }
        foreach ($files as $filename) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            $filename = $dir . DIRECTORY_SEPARATOR . $filename;
            if (is_dir($filename)) {
                $child = FileCtl::scandirR($filename);
                if ($child) {
                    $list['dirs'] = array_merge($list['dirs'], $child['dirs']);
                    $list['files'] = array_merge($list['files'], $child['files']);
                }
                $list['dirs'][] = $filename;
            } else {
                $list['files'][] = $filename;
            }
        }
        return $list;
    }

    // }}}
    // {{{ garbageCollection()

    /**
     * いわゆるひとつのガベコレ
     *
     * $targetDirから最終更新より$lifeTime秒以上たったファイルを削除
     *
     * @access  public
     * @param   string   $targetDir  ガーベッジコレクション対象ディレクトリ
     * @param   integer  $lifeTime   ファイルの有効期限（秒）
     * @param   string   $prefix     対象ファイル名の接頭辞（オプション）
     * @param   string   $suffix     対象ファイル名の接尾辞（オプション）
     * @param   boolean  $recurive   再帰的にガーベッジコレクションするか否か（デフォルトではFALSE）
     * @return  array|false    削除に成功したファイルと失敗したファイルを別々に記録した二次元の配列
     */
    function garbageCollection($targetDir, $lifeTime, $prefix = '', $suffix = '', $recursive = FALSE)
    {
        $result = array('successed' => array(), 'failed' => array(), 'skipped' => array());
        $expire = time() - $lifeTime;
        //ファイルリスト取得
        if ($recursive) {
            $list = FileCtl::scandirR($targetDir);
            if ($list === false) {
                return false;
            }
            $files = &$list['files'];
        } else {
            $list = scandir($targetDir);
            $files = array();
            $targetDir = realpath($targetDir) . DIRECTORY_SEPARATOR;
            foreach ($list as $filename) {
                if ($filename == '.' || $filename == '..') { continue; }
                $files[] = $targetDir . $filename;
            }
        }
        //検索パターン設定（$prefixと$suffixにスラッシュを含まないように）
        if ($prefix || $suffix) {
            $prefix = (is_array($prefix)) ? implode('|', array_map('preg_quote', $prefix)) : preg_quote($prefix);
            $suffix = (is_array($suffix)) ? implode('|', array_map('preg_quote', $suffix)) : preg_quote($suffix);
            $pattern = '/^' . $prefix . '.+' . $suffix . '$/';
        } else {
            $pattern = '';
        }
        //ガベコレ開始
        foreach ($files as $filename) {
            if ($pattern && !preg_match($pattern, basename($filename))) {
                //$result['skipped'][] = $filename;
                continue;
            }
            if (filemtime($filename) < $expire) {
                if (@unlink($filename)) {
                    $result['successed'][] = $filename;
                } else {
                    $result['failed'][] = $filename;
                }
            }
        }
        return $result;
    }

    // }}}
}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
