<?php
/* vim: set fileencoding=cp932 ai et ts=4 sw=4 sts=0 fdm=marker: */
/* mi: charset=Shift_JIS */

require_once (P2EX_LIBRARY_DIR . '/ic2/findexec.inc.php');
require_once (P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php');
require_once (P2EX_LIBRARY_DIR . '/ic2/database.class.php');
require_once (P2EX_LIBRARY_DIR . '/ic2/db_images.class.php');

class ThumbNailer
{
    // {{{ properties

    var $db;         // @var object,  PEAR DB_{phptype}のインスタンス
    var $ini;        // @var array,   ImageCache2の設定
    var $mode;       // @var integer, サムネイルの種類
    var $cachedir;   // @var string,  ImageCache2のキャッシュ保存ディレクトリ
    var $sourcedir;  // @var string,  オリジナル保存ディレクトリ
    var $thumbdir;   // @var string,  サムネイル保存ディレクトリ
    var $magick;     // @var string,  ImageMagickのパス
    var $max_width;  // @var integer, サムネイルの最大幅
    var $max_height; // @var integer, サムネイルの最大高さ
    var $type;       // @var string,  サムネイルの画像形式（JPEGかPNG）
    var $quality;    // @var integer, サムネイルの品質
    var $bgcolor;    // @var mixed,   サムネイルの背景色
    var $resize;     // @var bolean,  画像をリサイズするか否か
    var $rotate;     // @var integer, 画像を回転する角度（回転しないとき0）
    var $trim;       // @var bolean , 画像をトリミングするか否か
    var $coord;      // @var array ,  画像をトリミングする範囲（トリミングしないときFALSE）
    var $found;      // @var array,   IC2DB_Imagesでクエリを送信した結果
    var $dynamic;    // @var boolean, 動的生成するか否か（TRUEのとき結果をファイルに保存しない）
    var $cushion;    // @var string , 動的生成に利用する中間イメージのパス（ソースから直接生成するときFALSE）
    var $buf;        // @var string,  動的生成した画像データ
    // @var array $default_options,    動的生成時のオプション
    var $default_options = array(
        'quality' => NULL,
        'rotate'  => 0,
        'trim'    => FALSE,
        'cushion' => FALSE,
    );
    // @var array $mimemap, MIMEタイプと拡張子の対応表
    var $mimemap = array('image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif');

    // }}}
    // {{{ constructor

    /**
     * コンストラクタ
     * 
     * @access public
     */
    function ThumbNailer($mode = 1, $options = NULL)
    {
        if (is_array($options) && count($options) > 0) {
            $options = array_merge($this->default_options, $options);
            $this->dynamic = TRUE;
            $this->cushion = $options['cushion'];
        } else {
            $options = $this->default_options;
            $this->dynamic = FALSE;
            $this->cushion = FALSE;
        }

        // 設定
        $this->ini = ic2_loadconfig();

        // データベースに接続
        $icdb = &new IC2DB_Images;
        $this->db = &$icdb->getDatabaseConnection();
        if (DB::isError($this->db)) {
            $this->error($this->db->getMessage());
        }

        // サムネイルモード判定
        switch ($mode) {
            case 1:  $this->mode = 1; $setting = $this->ini['Thumb1']; break;
            case 2:  $this->mode = 2; $setting = $this->ini['Thumb2']; break;
            case 3:  $this->mode = 3; $setting = $this->ini['Thumb3']; break;
            default: $this->mode = 1; $setting = $this->ini['Thumb1']; 
        }

        // イメージドライバ判定
        $this->driver = $this->dynamic ? 'gd' : strtolower($this->ini['General']['driver']);
        switch ($this->driver) {
            case 'imagemagick': // システムのImageMagick
            case 'imagemagick6': // システムのImageMagick6
                $searchpath = $this->ini['General']['magick'];
                if (!findexec('convert', $searchpath)) {
                    $this->error('ImageMagickが使えません。');
                }
                if ($searchpath) {
                    $this->magick = $searchpath . DIRECTORY_SEPARATOR . 'convert';
                } else {
                    $this->magick = 'convert';
                }
                break;
            case 'gd': // PHPのGD拡張機能
                if (!extension_loaded('gd')) { $this->error('GDが使えません。'); }
                if (!function_exists('imagerotate') && $options['rotate'] != 0) { $this->error('imagerotate関数が使えません。'); };
                break;
            /*case 'imagick': // PHPのImageMagick拡張機能 (PECL)
                // 安定版（ImageMagick6に完全対応？）がリリースされたら試してみよう。
                if (!extension_loaded('imagick')) { $this->error('imagickが使えません。'); }
                break;*/
            default:
                $this->error('無効なイメージドライバです。');
        }

        // ディレクトリ設定
        $this->cachedir   = $this->ini['General']['cachedir'];
        $this->sourcedir  = $this->cachedir . '/' . $this->ini['Source']['name'];
        $this->thumbdir   = $this->cachedir . '/' . $setting['name'];

        // サムネイルの画像形式・幅・高さ・回転角度・品質設定
        $rotate = (int) $options['rotate'];
        if (abs($rotate) < 4) {
            $rotate = $rotate * 90;
        }
        $rotate = ($rotate < 0) ? ($rotate % 360) + 360 : $rotate % 360;
        $this->rotate = ($rotate % 90 == 0) ? $rotate : 0;
        if ($this->rotate % 180 == 90) {
            $this->max_width  = (int) $setting['height'];
            $this->max_height = (int) $setting['width'];
        } else {
            $this->max_width  = (int) $setting['width'];
            $this->max_height = (int) $setting['height'];
        }
        if (is_null($options['quality'])) {
            $this->quality = (int) $setting['quality'];
        } else {
            $this->quality = (int) $options['quality'];
        }
        if (0 < $this->quality && $this->quality <= 100) {
            $this->type = '.jpg';
        } else {
            $this->type = '.png';
            $this->quality = 0;
        }
        $this->trim = (bool) $options['trim'];

        // サムネイルの背景色設定
        if (preg_match('/^#?([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})$/i', // RGB各色2桁の16進数
                       $this->ini['General']['bgcolor'], $c)) {
            $r = hexdec($c[1]);
            $g = hexdec($c[2]);
            $b = hexdec($c[3]);
        } elseif (preg_match('/^#?([0-9A-F])([0-9A-F])([0-9A-F])$/i', // RGB各色1桁の16進数
                  $this->ini['General']['bgcolor'], $c)) {
            $r = hexdec($c[1] . $c[1]);
            $g = hexdec($c[2] . $c[2]);
            $b = hexdec($c[3] . $c[3]);
        } elseif (preg_match('/^(\d{1,3}),(\d{1,3}),(\d{1,3})$/', // RGB各色1～3桁の10進数
                  $this->ini['General']['bgcolor'], $c)) {
            $r = min(intval($c[1]), 255);
            $g = min(intval($c[2]), 255);
            $b = min(intval($c[3]), 255);
        } else {
            $r = NULL;
            $g = NULL;
            $b = NULL;
        }
        $this->_bgcolor($r, $g, $b);
    }

    // }}}
    // {{{ convert()

    /**
     * サムネイルを作成
     * 
     * @access public
     */
    function &convert($size, $md5, $mime, $width, $height, $force = FALSE)
    {
        // 画像
        if (!empty($this->cushion) && file_exists($this->cushion)) {
            $src    = realpath($this->cushion);
            $csize  = getimagesize($this->cushion);
            $width  = $csize[0];
            $height = $csize[1];
        } else {
            $src = $this->srcPath($size, $md5, $mime, TRUE);
        }
        $thumbURL = $this->thumbPath($size, $md5, $mime);
        $thumb = $this->thumbPath($size, $md5, $mime, TRUE);
        if ($src == FALSE) {
            return PEAR::raiseError("無効なMIMEタイプ。({$mime})");
        } elseif (!file_exists($src)) {
            return PEAR::raiseError("オリジナル画像がキャッシュされていません。({$src})");
        }
        if (!$force && !$this->dynamic && file_exists($thumb)) {
            return $thumbURL;
        }
        $thumbdir = dirname($thumb);
        if (!is_dir($thumbdir) && !@mkdir($thumbdir)) {
            return PEAR::raiseError("ディレクトリを作成できませんでした。({$thumbdir})");
        }
        
        // サイズが既定値以下で回転なし、画像形式が同じならばそのままコピー
        $_size = $this->calc($width, $height);
        if ($this->resize == FALSE && $this->rotate == 0 && $this->type == $this->mimemap[$mime]) {
            if (@copy($src, $thumb)) {
                return $thumbURL;
            } else {
                return PEAR::raiseError("画像をコピーできませんでした。({$src} -&gt; {$thumb})");
            }
        }
        
        // イメージドライバにサムネイル作成処理をさせる
        switch ($this->driver) {
            case 'imagemagick':
                $result = &$this->_magick($src, $thumb, $_size);
                break;
            case 'imagemagick6':
                $result = &$this->_magick6($src, $thumb, $_size);
                break;
            case 'gd':
                $size = array();
                list($size['tw'], $size['th']) = explode('x', $_size);
                if (is_array($this->coord)) {
                    $size['sx'] = $this->coord['x'][0];
                    $size['sy'] = $this->coord['y'][0];
                    $size['sw'] = $this->coord['x'][1];
                    $size['sh'] = $this->coord['y'][1];
                } else {
                    $size['sx'] = 0;
                    $size['sy'] = 0;
                    $size['sw'] = $width;
                    $size['sh'] = $height;
                }
                if ($this->dynamic) {
                    $result = &$this->_gdBuffer($src, $thumb, $size);
                    //$result = &$this->_gdDirect($src, $thumb, $size);
                } else {
                    $result = &$this->_gd($src, $thumb, $size);
                }
                break;
            default:
                $this->error('無効なイメージドライバです。');
        }
        
        if (PEAR::isError($result)) {
            return $result;
        }
        return $thumbURL;
    }

    // }}}
    // {{{ gdConvert()

    /**
     * GDで変換、イメージリソースを返す
     * 
     * @access private
     */
    function &gdConvert($original, $size)
    {
        extract($size);
        // オリジナルのイメージストリームを取得
        $ext = strrchr($original, '.');
        switch ($ext) {
            case '.jpg': $src = @imagecreatefromjpeg($original); break;
            case '.png': $src = @imagecreatefrompng($original); break;
            case '.gif': $src = @imagecreatefromgif($original); break;
        }
        if (!is_resource($src)) {
            return PEAR::raiseError("画像の読み込みに失敗しました。({$original})");
        }
        // サムネイルのイメージストリームを作成
        $dst = @imagecreatetruecolor($tw, $th);
        if (!is_null($this->bgcolor)) {
            $bg = imagecolorallocate($dst, $this->bgcolor[0], $this->bgcolor[1], $this->bgcolor[2]);
            imagefill($dst, 0, 0, $bg);
        }
        // オリジナルをサムネイルにコピー
        if ($this->resize) {
            imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $tw, $th, $sw, $sh);
        } else {
            imagecopy($dst, $src, 0, 0, $sx, $sy, $sw, $sh);
        }
        if ($this->rotate > 0) {
            $rotate = abs($this->rotate - 360);
            $dst = imagerotate($dst, $rotate, 0);
        }
        imagedestroy($src);
        return $dst;
    }

    // }}}
    // {{{ &_gd()

    /**
     * GDで変換、ファイルに出力
     * 
     * @access private
     */
    function &_gd($original, $thumbnail, $size)
    {
        $dst = &$this->gdConvert($original, $size);
        // サムネイルを保存
        if ($this->type == '.png') {
            $result = @imagepng($dst, $thumbnail);
        } else {
            $result = @imagejpeg($dst, $thumbnail, $this->quality);
        }
        imagedestroy($dst);
        if (!$result) {
            return PEAR::raiseError("サムネイルの作成に失敗しました。({$thumbnail})");
        }
        return TRUE;
    }

    // }}}
    // {{{ _gdBuffer()

    /**
     * GDで変換、バッファに保存
     * 
     * @access private
     */
    function &_gdBuffer($original, $thumbnail, $size)
    {
        $dst = &$this->gdConvert($original, $size);
        // サムネイルを保存
        ob_start();
        if ($this->type == '.png') {
            $result = @imagepng($dst);
        } else {
            $result = @imagejpeg($dst, '', $this->quality);
        }
        $this->buf = ob_get_clean();
        imagedestroy($dst);
        if (!$result) {
            return PEAR::raiseError("サムネイルの作成に失敗しました。({$thumbnail})");
        }
        return TRUE;
    }

    // }}}
    // {{{ _gdDirect()

    /**
     * GDで変換、直接表示
     * 
     * @access private
     */
    function &_gdDirect($original, $thumbnail, $size)
    {
        $dst = &$this->gdConvert($original, $size);
        // サムネイルを保存
        $name = 'filename="' . basename($result) . '"';
        if ($this->type == '.png') {
            header('Content-Type: image/png; ' . $name);
            header('Content-Disposition: inline; ' . $name);
            $result = @imagepng($dst);
        } else {
            header('Content-Type: image/jpeg; ' . $name);
            header('Content-Disposition: inline; ' . $name);
            $result = @imagejpeg($dst, '', $this->quality);
        }
        imagedestroy($dst);
        if (!$result) {
            return PEAR::raiseError("サムネイルの作成に失敗しました。({$thumbnail})");
        }
        return TRUE;
    }

    // }}}
    // {{{ _magick()

    /**
     * ImageMagickで変換、ファイルに出力
     * 
     * @access private
     */
    function &_magick($original, $thumbnail, $size)
    {
        $command = $this->magick;
        if ($this->resize)      { $command .= ' -resize ' . $size; }
        if ($this->rotate > 0)  { $command .= ' -rotate ' . $this->rotate; }
        if ($this->quality > 0) { $command .= ' -quality ' . $this->quality; }
        if (!is_null($this->bgcolor)) {
            /* ImageMagickで透過部分の背景色を任意の色にするのはめんどくさそうなので保留 */
        }
        if (preg_match('/\.gif$/', $original)) {
            $command .= ' +adjoin';
            $original .= '[0]';
        }
        $original  = escapeshellarg($original);
        $thumbnail = escapeshellarg($thumbnail);
        $command .= " +profile '*' $original $thumbnail";
        @exec($command, $results, $status);
        if ($status != 0) {
            $errmsg = "convert failed. ( $command . )\n";
            while (!is_null($errstr = array_shift($results))) {
                if ($errstr === '') { break; }
                $errmsg .= $errstr . "\n";
            }
            return PEAR::raiseError($errmsg);
        }
        return TRUE;
    }

    // }}}
    // {{{ _magick6()

    /**
     * ImageMagick6で変換、ファイルに出力
     * 
     * @access private
     */
    function &_magick6($original, $thumbnail, $size)
    {
        $command = $this->magick;
        $command .=  ($this->resize == TRUE) ? ' -thumbnail ' . $size : ' -strip';
        if ($this->rotate > 0)  { $command .= ' -rotate ' . $this->rotate; }
        if ($this->quality > 0) { $command .= ' -quality ' . $this->quality; }
        if (!is_null($this->bgcolor)) {
            /* ImageMagickで透過部分の背景色を任意の色にするのはめんどくさそうなので保留 */
        }
        if (preg_match('/\.gif$/', $original)) {
            $command .= ' +adjoin';
            $original .= '[0]';
        }
        $original  = escapeshellarg($original);
        $thumbnail = escapeshellarg($thumbnail);
        $command .= " $original $thumbnail";
        @exec($command, $results, $status);
        if ($status != 0) {
            $errmsg = "convert failed. ( $command . )\n";
            while (!is_null($errstr = array_shift($results))) {
                if ($errstr === '') { break; }
                $errmsg .= $errstr . "\n";
            }
            return PEAR::raiseError($errmsg);
        }
        return TRUE;
    }

    // }}}
    // {{{ _bgcolor()

    /**
     * 背景色を設定
     * 
     * @access private
     */
    function _bgcolor($r, $g, $b)
    {
        if (is_null($r) || is_null($g) || is_null($b)) {
            $this->bgcolor = NULL;
            return;
        }
        switch ($this->driver) {
            case 'gd':
                $this->bgcolor = array($r, $g, $b);
                break;
            case 'imagemagick':
            case 'imagemagick6':
                $this->bgcolor = '"#' . dechex($r) . dechex($g) . dechex($b) . '"';
                break;
            default:
                $this->bgcolor = "$r,$g,$b";
        }
    }

    // }}}
    // {{{ calc()

    /**
     * サムネイルサイズ計算
     * 
     * @access public
     */
    function calc($width, $height)
    {
        $debug = FALSE;
        // デフォルト値・フラグを設定
        $t_width  = $width;
        $t_height = $height;
        $this->resize = FALSE;
        $this->coord   = FALSE;
        // オリジナルがサムネイルの最大サイズより小さいとき、オリジナルの大きさをそのまま返す
        if ($width <= $this->max_width && $height <= $this->max_height) {
            // リサイズ・トリミングともに無効
            return ($width . 'x' . $height);
        }
        // 縦横どちらに合わせるかを判定（最大サイズより横長 = 横幅に合わせる）
        if (($width / $height) >= ($this->max_width / $this->max_height)) {
            // 横に合わせる
            $main = $width;
            $sub  = $height;
            $max_main = $this->max_width;
            $max_sub  = $this->max_height;
            $t_main = &$t_width;  // $t_mainと$t_subをサムネイルサイズの
            $t_sub  = &$t_height; // リファレンスにしているのが肝
            $c_main = 'x';
            $c_sub  = 'y';
        } else {
            // 縦に合わせる
            $main = $height;
            $sub  = $width;
            $max_main = $this->max_height;
            $max_sub  = $this->max_width;
            $t_main = &$t_height;
            $t_sub  = &$t_width;
            $c_main = 'y';
            $c_sub  = 'x';
        }
        // サムネイルサイズと変換フラグを決定
        $t_main = $max_main;
        if ($this->trim) {
            // トリミングする
            $this->coord = array($c_main => array(0, $main), $c_sub => array(0, $sub));
            $ratio = $t_sub / $max_sub;
            if ($ratio <= 1) {
                // オリジナルがサムネイルの最大サイズより小さいとき、縮小せずにトリミング
                // $t_main == $max_main, $t_sub == $sub
                // ceil($sub * ($t_main / $t_sub)) = ceil($sub * $t_main / $sub) = $t_main = $max_main
                $c_length = $max_main;
            } elseif ($ratio < 1.05) {
                // 縮小率が極めて小さいとき、画質劣化を避けるために縮小せずにトリミング
                $this->coord[$c_sub][0] = floor(($t_sub - $max_sub) / 2);
                $t_sub = $max_sub;
                $c_length = $max_main;
            } else {
                // サムネイルサイズいっぱいに収まるように縮小＆トリミング
                $this->resize = TRUE;
                $t_sub = $max_sub;
                $c_length = ceil($sub * ($t_main / $t_sub));
            }
            $this->coord[$c_main] = array(floor(($main - $c_length) / 2), $c_length);
        } else {
            // アスペクト比を維持したまま縮小し、トリミングはしない
            $this->resize = TRUE;
            $t_sub = round($max_main * ($sub / $main));
        }
        // チェック
        if ($debug) {
            require_once 'Var_Dump.php';
            $flags = array(
                'width' => $width,
                'height' => $height,
                'max_width' => $this->max_width,
                'max_height' => $this->max_height,
                't_width' => $t_width,
                't_height' => $t_height,
                'resize' => $this->resize,
                'coord' => $this->coord,
            );
            Var_Dump::display($flags);
            if ($this->dynamic) {
                exit;
            }
        }
        // サムネイルサイズを返す
        return ($t_width . 'x' . $t_height);
    }

    // }}}
    // {{{ srcPath()

    /**
     * ソースファイルのパス
     * 
     * @access public
     */
    function srcPath($size, $md5, $mime, $FSFullPath = FALSE)
    {
        $directory = $this->getSubDir($this->sourcedir, $size, $md5, $mime, $FSFullPath);
        if (!$directory) {
            return FALSE;
        }
        
        $basename = $size . '_' . $md5 . $this->mimemap[$mime];
        
        return $directory . ($FSFullPath ? DIRECTORY_SEPARATOR : '/') . $basename;
    }

    // }}}
    // {{{ thumbPath()

    /**
     * サムネイルのパス
     *
     * @access public
     */
    function thumbPath($size, $md5, $mime, $FSFullPath = FALSE)
    {
        $directory = $this->getSubDir($this->thumbdir, $size, $md5, $mime, $FSFullPath);
        if (!$directory) {
            return FALSE;
        }
        
        $basename = $size . '_' . $md5;
        if ($this->rotate > 0) {
            $basename .= '_' . str_pad($this->rotate, 3, 0, STR_PAD_LEFT);
        }
        if ($this->trim) {
            $basename .= '_tr';
        }
        $basename .= $this->type;
        
        return $directory . ($FSFullPath ? DIRECTORY_SEPARATOR : '/') . $basename;
    }

    // }}}
    // {{{ getSubDir()

    /**
     * 実際に画像が保存されるサブディレクトリのパス
     * 
     * @access public
     */
    function getSubDir($basedir, $size, $md5, $mime, $FSFullPath = FALSE)
    {
        if (!is_dir($basedir)) {
            return FALSE;
        }
        
        $dirID = $this->dirID($size, $md5, $mime);
        
        if ($FSFullPath) {
            $directory = realpath($basedir) . DIRECTORY_SEPARATOR . $dirID;
        } else {
            $directory = $basedir . '/' . $dirID;
        }
        
        return $directory;
    }

    // }}
    // {{{ dirID()

    /**
     * ディレクトリID
     * 
     * @access public
     */
    function dirID($size = NULL, $md5 = NULL, $mime = NULL)
    {
        if ($size && $md5 && $mime) {
            $icdb = &new IC2DB_Images;
            $icdb->whereAddQUoted('size', '=', $size);
            $icdb->whereAddQuoted('md5',  '=', $md5);
            $icdb->whereAddQUoted('mime', '=', $mime);
            $icdb->orderByArray(array('id' => 'ASC'));
            if ($icdb->find(TRUE)) {
                $this->found = $icdb->toArray();
                return str_pad(ceil($icdb->id / 1000), 5, 0, STR_PAD_LEFT);
            }
        }
        $sql = 'SELECT MAX(' . $this->db->quoteIdentifier('id') . ') + 1 FROM '
             . $this->db->quoteIdentifier($this->ini['General']['table']) . ';';
        $nextid = &$this->db->getOne($sql);
        if (DB::isError($nextid) || !$nextid) {
            $nextid = 1;
        }
        return str_pad(ceil($nextid / 1000), 5, 0, STR_PAD_LEFT);
    }

    // }}
    // {{{ error()

    /**
     * エラーメッセージを表示して終了
     * 
     * @access public
     */
    function error($message = '')
    {
        echo <<<EOF
<html>
<head><title>ImageCache::Error</title></head>
<body>
<p>{$message}</p>
</body>
</html>
EOF;
        exit;
    }

    // }}
}

?>
