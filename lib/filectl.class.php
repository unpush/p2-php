<?php

if (!defined('FILE_USE_INCLUDE_PATH')) {
    define('FILE_USE_INCLUDE_PATH', 1);
}

if (!defined('FILE_APPEND')) {
    define('FILE_APPEND', 8);
}

/**
 * ファイルを操作するクラス
 * インスタンスを作らずにクラスメソッドで利用する
 */
class FileCtl{

    /**
     * 書き込み用のファイルがなければ生成してパーミッションを調整する
     */
    function make_datafile($file, $perm = 0606)
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

    /**
     * 親ディレクトリがなければ生成してパーミッションを調整する
     */
    function mkdir_for($apath)
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

    /**
     * gzファイルの中身を取得する
     */
    function get_gzfile_contents($filepath)
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

    /**
     * 文字列をファイルに書き込む
     * （PHP5のfile_put_contentsの代替的役割）
     *
     * このfunctionは、PHP License に基づく、Aidan Lister氏 <aidan@php.net> による、
     * PHP_Compat の file_put_contents.php のコードを元に、独自の変更（flock() など）を加えたものです。
     * This product includes PHP, freely available from <http://www.php.net/>
     */
    function file_write_contents($filename, $content, $flags = null, $resource_context = null)
    {
        // If $cont is an array, convert it to a string
        if (is_array($content)) {
            $content = implode('', $content);
        }

        /*
        shift_jisの文字が途中から入ったりすると、stringではないと判断されることがある？
        // If we don't have a string, throw an error
        if (!is_string($content)) {
            trigger_error('file_write_contents() '.$filename.', The 2nd parameter should be either a string or an array', E_USER_WARNING);
            return false;
        }
        */

        // Get the length of date to write
        $length = strlen($content);

        // Check what mode we are using
        $file_append = ($flags & FILE_APPEND) ? true : false;
        $mode = $file_append ? 'ab' : 'ab';

        // Check if we're using the include path
        $use_inc_path = ($flags & FILE_USE_INCLUDE_PATH) ?
                    true :
                    false;

        // Open the file for writing
        if (($fh = @fopen($filename, $mode, $use_inc_path)) === false) {
            trigger_error('file_write_contents() '.$filename.', failed to open stream: Permission denied', E_USER_WARNING);
            return false;
        }

        @flock($fh, LOCK_EX);
        $last = ignore_user_abort(1);

        // Write to the file
        $bytes = 0;

        if (!$file_append) {
            ftruncate($fh, 0);
        }

        if (($bytes = @fwrite($fh, $content)) === false) {
            $errormsg = sprintf('file_write_contents() Failed to write %d bytes to %s',
                            $length,
                            $filename);
            trigger_error($errormsg, E_USER_WARNING);
            ignore_user_abort($last);
            return false;
        }

        ignore_user_abort($last);
        @flock($fh, LOCK_UN);
        fclose($fh);

        if ($bytes != $length) {
            $errormsg = sprintf('file_put_contents() Only %d of %d bytes written, possibly out of free disk space.',
                            $bytes,
                            $length);
            trigger_error($errormsg, E_USER_WARNING);
            return false;
        }

        return $bytes;
    }
}
?>
