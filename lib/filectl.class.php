<?php
// {{{ constants
/*
if (!defined('FILE_USE_INCLUDE_PATH')) {
    define('FILE_USE_INCLUDE_PATH', 1);
}

if (!defined('FILE_APPEND')) {
    define('FILE_APPEND', 8);
}
*/
// }}}
// {{{ FileCtl

/**
 * ファイルを操作するクラス
 * インスタンスを作らずにクラスメソッドで利用する
 *
 * @static
 */
class FileCtl
{
    // {{{ make_datafile()

    /**
     * 書き込み用のファイルがなければ生成してパーミッションを調整する
     */
    static public function make_datafile($file, $perm = 0606)
    {
        // 念のためにデフォルト補正しておく
        if (empty($perm)) {
            $perm = 0606;
        }

        if (!file_exists($file)) {
            // 親ディレクトリが無ければ作る
            FileCtl::mkdir_for($file) or die("Error: cannot make parent dirs. ( $file )");
            touch($file) or die("Error: cannot touch. ( $file )");
            chmod($file, $perm);
        } else {
            if (!is_writable($file)) {
                $cont = @file_get_contents($file);
                unlink($file);
                if (FileCtl::file_write_contents($file, $cont) === false) {
                    die('Error: cannot write file.');
                }
                chmod($file, $perm);
            }
        }
        return true;
    }

    // }}}
    // {{{ mkdir_for()

    /**
     * 親ディレクトリがなければ生成してパーミッションを調整する
     */
    static public function mkdir_for($apath)
    {
        global $_conf;

        $dir_limit = 50; // 親階層を上る制限回数

        $perm = (!empty($_conf['data_dir_perm'])) ? $_conf['data_dir_perm'] : 0707;

        if (!$parentdir = dirname($apath)) {
            die("Error: cannot mkdir. ( {$parentdir} )<br>親ディレクトリが空白です。");
        }
        $i = 1;
        if (!is_dir($parentdir)) {
            if ($i > $dir_limit) {
                die("Error: cannot mkdir. ( {$parentdir} )<br>階層を上がり過ぎたので、ストップしました。");
            }
            FileCtl::mkdir_for($parentdir);
            mkdir($parentdir, $perm) or die("Error: cannot mkdir. ( {$parentdir} )");
            chmod($parentdir, $perm);
            $i++;
        }
        return true;
    }

    // }}}
    // {{{ get_gzfile_contents()

    /**
     * gzファイルの中身を取得する
     */
    static public function get_gzfile_contents($filepath)
    {
        if (is_readable($filepath)) {
            ob_start();
            readgzfile($filepath);
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ file_write_contents()

    /**
     * 文字列をファイルに書き込む
     * （file_put_contents()+強制LOCK_EX）
     *
     * @param string $filename
     * @param mixed $data
     * @param int $flags
     * @param resource $context
     */
    static public function file_write_contents($filename,
                                               $data,
                                               $flags = 0,
                                               $context = null
                                               )
    {
        return file_put_contents($filename, $data, $flags | LOCK_EX, $context);
    }

    // }}}
}

// }}}

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
