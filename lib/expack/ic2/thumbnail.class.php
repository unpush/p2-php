<?php
/**
 * rep2expack - ImageCache2
 */

require_once P2EX_LIBRARY_DIR . '/ic2/findexec.inc.php';
require_once P2EX_LIBRARY_DIR . '/ic2/loadconfig.inc.php';
require_once P2EX_LIBRARY_DIR . '/ic2/database.class.php';
require_once P2EX_LIBRARY_DIR . '/ic2/db_images.class.php';

define('IC2_THUMB_SIZE_DEFAULT', 1);
define('IC2_THUMB_SIZE_PC',      1);
define('IC2_THUMB_SIZE_MOBILE',  2);
define('IC2_THUMB_SIZE_INTERMD', 3);

class ThumbNailer
{
    // {{{ properties

    var $db;            // @var object  PEAR DB_{phptype}のインスタンス
    var $ini;           // @var array   ImageCache2の設定
    var $mode;          // @var int     サムネイルの種類
    var $cachedir;      // @var string  ImageCache2のキャッシュ保存ディレクトリ
    var $sourcedir;     // @var string  ソース保存ディレクトリ
    var $thumbdir;      // @var string  サムネイル保存ディレクトリ
    var $driver;        // @var string  イメージドライバの種類
    var $epeg;          // @var bool    Epegが利用可能か否か
    var $magick;        // @var string  ImageMagickのパス
    var $magick6;       // @var bool    ImageMagick6以上か否か
    var $max_width;     // @var int     サムネイルの最大幅
    var $max_height;    // @var int     サムネイルの最大高さ
    var $type;          // @var string  サムネイルの画像形式（JPEGかPNG）
    var $quality;       // @var int     サムネイルの品質
    var $bgcolor;       // @var mixed   サムネイルの背景色
    var $resize;        // @var bolean  画像をリサイズするか否か
    var $rotate;        // @var int     画像を回転する角度（回転しないとき0）
    var $trim;          // @var bolean  画像をトリミングするか否か
    var $coord;         // @var array   画像をトリミングする範囲（トリミングしないときfalse）
    var $found;         // @var array   IC2DB_Imagesでクエリを送信した結果
    var $dynamic;       // @var bool    動的生成するか否か（trueのとき結果をファイルに保存しない）
    var $intermd;       // @var string  動的生成に利用する中間イメージのパス（ソースから直接生成するときfalse）
    var $buf;           // @var string  動的生成した画像データ
    // @var array $default_options,    動的生成時のオプション
    var $default_options = array(
        'quality' => null,
        'rotate'  => 0,
        'trim'    => false,
        'intermd' => false,
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
    function ThumbNailer($mode = IC2_THUMB_SIZE_DEFAULT, $dynamic_options = null)
    {
        if (is_array($dynamic_options) && count($dynamic_options) > 0) {
            $options = array_merge($this->default_options, $dynamic_options);
            $this->dynamic = true;
            $this->intermd = $options['intermd'];
        } else {
            $options = $this->default_options;
            $this->dynamic = false;
            $this->intermd = false;
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
            case IC2_THUMB_SIZE_INTERMD:
                $this->mode = IC2_THUMB_SIZE_INTERMD;
                $setting = $this->ini['Thumb3'];
                break;
            case IC2_THUMB_SIZE_MOBILE:
                $this->mode = IC2_THUMB_SIZE_MOBILE;
                $setting = $this->ini['Thumb2'];
                break;
            case IC2_THUMB_SIZE_PC:
            default:
                $this->mode = IC2_THUMB_SIZE_PC;
                $setting = $this->ini['Thumb1'];
        }

        // イメージドライバ判定
        $driver = strtolower($this->ini['General']['driver']);
        $this->driver = $driver;
        $this->magick6 = false;
        switch ($driver) {
            case 'imagemagick6': // ImageMagick6 の convert コマンド
                $this->driver = 'imagemagick';
                $this->magick6 = true;
            case 'imagemagick': // ImageMagick の convert コマンド
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
            case 'gd': // PHP の GD 拡張機能
                if (!function_exists('imagerotate') && $options['rotate'] != 0) {
                    $this->error('imagerotate関数が使えません。');
                }
                break;
            case 'imagick': // PHP の ImageMagick 拡張機能
                if (!extension_loaded('imagick')) {
                    $this->error('imagickエクステンションが使えません。');
                }
                break;
            case 'imlib2': // PHP の Imlib2 拡張機能
                if (!extension_loaded('imlib2')) {
                    $this->error('imlib2エクステンションが使えません。');
                }
                break;
            default:
                $this->error('無効なイメージドライバです。');
        }

        $this->epeg = ($this->ini['General']['epeg'] && extension_loaded('epeg')) ? true : false;

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
            $r = max(0, min(intval($c[1]), 255));
            $g = max(0, min(intval($c[2]), 255));
            $b = max(0, min(intval($c[3]), 255));
        } else {
            $r = null;
            $g = null;
            $b = null;
        }
        $this->_bgcolor($r, $g, $b);
    }

    // }}}
    // {{{ convert method

    /**
     * サムネイルを作成
     *
     * @access  public
     * @return  string|bool|PEAR_Error
     *          サムネイルを生成・保存に成功したとき、サムネイルのパス
     *          テンポラリ・サムネイルの生成に成功したとき、true
     *          失敗したとき PEAR_Error
     */
    function &convert($size, $md5, $mime, $width, $height, $force = false)
    {
        // 画像
        if (!empty($this->intermd) && file_exists($this->intermd)) {
            $src    = realpath($this->intermd);
            $csize  = getimagesize($this->intermd);
            $width  = $csize[0];
            $height = $csize[1];
        } else {
            $src = $this->srcPath($size, $md5, $mime, true);
        }
        $thumbURL = $this->thumbPath($size, $md5, $mime);
        $thumb = $this->thumbPath($size, $md5, $mime, true);
        if ($src == false) {
            $error = &PEAR::raiseError("無効なMIMEタイプ。({$mime})");
            return $error;
        } elseif (!file_exists($src)) {
            $error = &PEAR::raiseError("ソース画像がキャッシュされていません。({$src})");
            return $error;
        }
        if (!$force && !$this->dynamic && file_exists($thumb)) {
            return $thumbURL;
        }
        $thumbdir = dirname($thumb);
        if (!is_dir($thumbdir) && !@mkdir($thumbdir)) {
            $error = &PEAR::raiseError("ディレクトリを作成できませんでした。({$thumbdir})");
            return $error;
        }

        // サイズが既定値以下で回転なし、画像形式が同じならばそのままコピー
        // --- 携帯で表示できないことがあるので封印、ちゃんとサムネイルをつくる
        $_size = $this->calc($width, $height);
        /*if ($this->resize == false && $this->rotate == 0 && $this->type == $this->mimemap[$mime]) {
            if (@copy($src, $thumb)) {
                return $thumbURL;
            } else {
                $error = &PEAR::raiseError("画像をコピーできませんでした。({$src} -&gt; {$thumb})");
                return $error;
            }
        }*/

        // Epegでサムネイルを作成
        if ($mime == 'image/jpeg' && $this->type == '.jpg' && $this->epeg && !$this->rotate && !$this->trim) {
            $dst = ($this->dynamic) ? '' : $thumb;
            $result = epeg_thumbnail_create($src, $dst, $this->max_width, $this->max_height, $this->quality);
            if ($result == false) {
                $error = &PEAR::raiseError("サムネイルを作成できませんでした。({$src} -&gt; {$dst})");
                return $error;
            }
            if ($this->dynamic) {
                $this->buf = $result;
            }
            return $thumbURL;
        }

        // イメージドライバにサムネイル作成処理をさせる
        switch ($this->driver) {
            case 'imagemagick':
                $_srcsize = sprintf('%dx%d', $width, $height);
                if ($this->rotate % 180 == 90) {
                    $_thumbsize = vsprintf('%2$dx%1$d!', explode('x', $_size));
                } else {
                    $_thumbsize = $_size . '!';
                }
                if ($this->dynamic) {
                    $result = &$this->_magickCapture($src, $_srcsize, $_thumbsize);
                } else {
                    $result = &$this->_magickSave($src, $thumb, $_srcsize, $_thumbsize);
                }
                break;
            case 'gd':
            case 'imagick':
            case 'imlib2':
            //case 'magickwand':
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
                    $result = &$this->{'_'.$this->driver.'Capture'}($src, $size);
                } else {
                    $result = &$this->{'_'.$this->driver.'Save'}($src, $thumb, $size);
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
    // {{{ image manipulation methods using gd php extension

    /**
     * gd エクステンションで変換、イメージリソースを返す
     *
     * @access private
     * @return resource gd
     */
    function &_gdConvert($source, $size)
    {
        extract($size);
        // ソースのイメージストリームを取得
        $ext = strrchr($source, '.');
        switch ($ext) {
            case '.jpg': $src = imagecreatefromjpeg($source); break;
            case '.png': $src = imagecreatefrompng($source); break;
            case '.gif': $src = imagecreatefromgif($source); break;
        }
        if (!is_resource($src)) {
            $error = &PEAR::raiseError("画像の読み込みに失敗しました。({$source})");
            return $error;
        }
        // サムネイルのイメージストリームを作成
        $dst = imagecreatetruecolor($tw, $th);
        if (!is_null($this->bgcolor)) {
            $bg = imagecolorallocate($dst, $this->bgcolor[0], $this->bgcolor[1], $this->bgcolor[2]);
            imagefill($dst, 0, 0, $bg);
        }
        // ソースをサムネイルにコピー
        if ($this->resize) {
            imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $tw, $th, $sw, $sh);
        } else {
            imagecopy($dst, $src, 0, 0, $sx, $sy, $sw, $sh);
        }
        imagedestroy($src);
        // 回転
        if ($this->rotate) {
            $degrees = ($this->rotate == 90) ? -90 : (($this->rotate == 270) ? 90: $this->rotate);
            $tmp = imagerotate($dst, $degrees, $bg);
            imagedestroy($dst);
            return $tmp;
        }
        return $dst;
    }

    /**
     * gd エクステンションで変換、ファイルに出力
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_gdSave($source, $thumbnail, $size)
    {
        $dst = &$this->_gdConvert($source, $size);
        // サムネイルを保存
        if ($this->type == '.png') {
            $result = imagepng($dst, $thumbnail);
        } else {
            $result = imagejpeg($dst, $thumbnail, $this->quality);
        }
        imagedestroy($dst);
        if (!$result) {
            $errmsg = "サムネイルの作成に失敗しました。({$thumbnail})";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $retval = true;
        }
        return $retval;
    }

    /**
     * gd エクステンションで変換、バッファに保存
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_gdCapture($source, $size)
    {
        $dst = &$this->_gdConvert($source, $size);
        // サムネイルを作成
        ob_start();
        if ($this->type == '.png') {
            $result = imagepng($dst);
        } else {
            $result = imagejpeg($dst, '', $this->quality);
        }
        $this->buf = ob_get_clean();
        imagedestroy($dst);
        if (!$result) {
            $errmsg = "サムネイルの作成に失敗しました。({$thumbnail})";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $retval = true;
        }
        return $retval;
    }

    /**
     * gd エクステンションで変換、直接出力
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_gdOutput($source, $thumbnail, $size)
    {
        $dst = &$this->_gdConvert($source, $size);
        // サムネイルを出力
        $name = 'filename="' . basename($thumbnail) . '"';
        if ($this->type == '.png') {
            header('Content-Type: image/png; ' . $name);
            header('Content-Disposition: inline; ' . $name);
            $result = imagepng($dst);
        } else {
            header('Content-Type: image/jpeg; ' . $name);
            header('Content-Disposition: inline; ' . $name);
            $result = imagejpeg($dst, '', $this->quality);
        }
        imagedestroy($dst);
        if (!$result) {
            $errmsg = "サムネイルの作成に失敗しました。({$thumbnail})";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $retval = true;
        }
        return $retval;
    }

    // }}}
    // {{{ image manipulation methods using imlib2 php extension

    /**
     * imlib2 エクステンションで変換、イメージリソースを返す
     *
     * @access private
     * @return resource Unknown (imlib2?)
     */
    function &_imlib2Convert($source, $size)
    {
        extract($size);
        $err = 0;
        // ソースのイメージストリームを取得
        $src = imlib2_load_image($source, $err);
        if ($err) {
            $error = &PEAR::raiseError("画像の読み込みに失敗しました。({$source}:{$err})");
            return $error;
        }
        // サムネイルのイメージストリームを作成
        $dst = imlib2_create_image($tw, $th);
        if (!is_null($this->bgcolor)) {
            list($r, $g, $b) = $this->bgcolor;
            imlib2_image_fill_rectangle($dst, 0, 0, $tw, $th, $r, $g, $b, 255);
        }
        // ソースをサムネイルにコピー
        /* imlib_blend_image_onto_image(int dstimg, int srcimg, int malpha, int srcx, int srcy, int srcw, int srch,
            int dstx, int dsty, int dstw, int dsth, char dither, char blend, char alias) */
        imlib2_blend_image_onto_image($dst, $src, 255, $sx, $sy, $sw, $sh, 0, 0, $tw, $th, false, true, $this->resize);
        imlib2_free_image($src);
        // 回転
        if ($this->rotate) {
            imlib2_image_orientate($dst, $this->rotate / 90);
        }
        return $dst;
    }

    /**
     * imlib2 エクステンションで変換、ファイルに出力
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_imlib2Save($source, $thumbnail, $size)
    {
        $dst = &$this->_imlib2Convert($source, $size);
        $err = 0;
        // サムネイルを保存
        if ($this->type == '.png') {
            imlib2_image_set_format($dst, 'png');
            $result = imlib2_save_image($dst, $thumbnail, $err);
        } else {
            imlib2_image_set_format($dst, 'jpeg');
            $result = imlib2_save_image($dst, $thumbnail, $err, $this->quality);
        }
        imlib2_free_image($dst);
        if (!$result) {
            $errmsg = "サムネイルの作成に失敗しました。({$thumbnail}:{$err})";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $retval = true;
        }
        return $retval;
    }

    /**
     * imlib2 エクステンションで変換、バッファに保存
     *
     * imlib2_dump_image() の出力をキャプチャしようとするとうまくいかないので
     * いったん一時ファイルに書き出したデータを読み込む
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_imlib2Capture($source, $size)
    {
        $dst = &$this->_imlib2Convert($source, $size);
        $err = 0;
        // サムネイルを作成
        $tempfile = $this->_tempnam();
        if ($this->type == '.png') {
            imlib2_image_set_format($dst, 'png');
            $result = imlib2_save_image($dst, $tempfile, $err);
        } else {
            imlib2_image_set_format($dst, 'jpeg');
            $result = imlib2_save_image($dst, $tempfile, $err, $this->quality);
        }
        imlib2_free_image($dst);
        if (!$result) {
            $errmsg = "サムネイルの作成に失敗しました。({$thumbnail}:{$err})";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $this->buf = file_get_contents($tempfile);
            $retval = true;
        }
        return $retval;
    }

    /**
     * imlib2 エクステンションで変換、直接出力
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_imlib2Output($source, $thumbnail, $size)
    {
        $dst = &$this->_imlib2Convert($source, $size);
        $err = 0;
        // サムネイルを出力
        $name = 'filename="' . basename($thumbnail) . '"';
        if ($this->type == '.png') {
            header('Content-Type: image/png; ' . $name);
            header('Content-Disposition: inline; ' . $name);
            imlib2_image_set_format($dst, 'png');
            $result = imlib2_dump_image($dst, $err);
        } else {
            header('Content-Type: image/jpeg; ' . $name);
            header('Content-Disposition: inline; ' . $name);
            imlib2_image_set_format($dst, 'jpeg');
            $result = imlib2_dump_image($dst, $err, $this->quality);
        }
        imlib2_free_image($dst);
        if (!$result) {
            $errmsg = "サムネイルの作成に失敗しました。({$thumbnail}:{$err})";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $retval = true;
        }
        return $retval;
    }

    // }}}
    // {{{ image manipulation methods using imagick extension

    /**
     * imagick エクステンションで変換、イメージリソースを返す
     *
     * @access private
     * @return resource imagick handle
     */
    function &_imagickConvert($source, $size)
    {
        extract($size);
        // ソースのイメージストリームを取得
        $src = imagick_readimage($source);
        if (!is_resource($src) || imagick_iserror($src)) {
            if (is_resource($src)) {
                $reason = imagick_failedreason($src);
                $detail = imagick_faileddescription($src);
                imagick_destroyhandle($src);
            }
            $error = &PEAR::raiseError("画像の読み込みに失敗しました。({$source}:{$reason}:{$detail})");
            return $error;
        }
        // サムネイルのイメージストリームを作成
        $bg = (!is_null($this->bgcolor)) ? $this->bgcolor : 'rgb(0,0,0)';
        $dst = imagick_getcanvas($bg, $tw, $th);
        // ソースをリサイズし、サムネイルにコピー
        if ($sx != 0 || $sy != 0) {
            imagick_crop($src, $sx, $sy, $sw, $sh);
        }
        if ($this->resize) {
            imagick_scale($src, $tw, $th, '!');
        }
        imagick_composite($dst, IMAGICK_COMPOSITE_OP_ATOP, $src, 0, 0);
        imagick_destroyhandle($src);
        // 回転
        if ($this->rotate) {
            imagick_rotate($dst, $this->rotate);
        }
        return $dst;
    }

    /**
     * imagick エクステンションで変換、ファイルに出力
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_imagickSave($source, $thumbnail, $size)
    {
        $dst = &$this->_imagickConvert($source, $size);
        // サムネイルを保存
        if ($this->quality > 0) {
            imagick_setcompressionquality($dst, $this->quality);
        }
        $prefix = (($this->type == '.png') ? 'png' : 'jpeg') . ':';
        $result = imagick_writeimage($dst, $prefix.$thumbnail);
        if (!$result) {
            $reason = imagick_failedreason($dst);
            $detail = imagick_faileddescription($dst);
            $errmsg = "サムネイルの作成に失敗しました。({$thumbnail}:{$reason}:{$detail})";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $retval = true;
        }
        imagick_destroyhandle($dst);
        return $retval;
    }

    /**
     * imagick エクステンションで変換、バッファに保存
     *
     * imagick_image2blob() ではうまくいかないので
     * いったん一時ファイルに書き出したデータを読み込む
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_imagickCapture($source, $size)
    {
        $dst = &$this->_imagickConvert($source, $size);
        // サムネイルを作成
        if ($this->quality > 0) {
            imagick_setcompressionquality($dst, $this->quality);
        }
        $prefix = (($this->type == '.png') ? 'png' : 'jpeg') . ':';
        $tempfile = $this->_tempnam();
        $result = imagick_writeimage($dst, $prefix.$tempfile);
        if (!$result) {
            $reason = imagick_failedreason($dst);
            $detail = imagick_faileddescription($dst);
            $errmsg = "サムネイルの作成に失敗しました。({$thumbnail}:{$reason}:{$detail})";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $this->buf = file_get_contents($tempfile);
            $retval = true;
        }
        imagick_destroyhandle($dst);
        return $retval;
    }

    /**
     * imagick エクステンションで変換、直接出力
     *
     * imagick_image2blob() ではうまくいかないので
     * いったん一時ファイルに書き出し、readfile() する
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_imagickOutput($source, $thumbnail, $size)
    {
        $dst = &$this->_imagickConvert($source, $size);
        // サムネイルを出力
        if ($this->quality) {
            imagick_setcompressionquality($dst, $this->quality);
        }
        $prefix = (($this->type == '.png') ? 'png' : 'jpeg') . ':';
        $tempfile = $this->_tempnam();
        $result = imagick_writeimage($dst, $prefix.$tempfile);
        if (!$result) {
            $reason = imagick_failedreason($dst);
            $detail = imagick_faileddescription($dst);
            $errmsg = "サムネイルの作成に失敗しました。({$thumbnail}:{$reason}:{$detail})";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $name = 'filename="' . basename($thumbnail) . '"';
            if ($this->type == '.png') {
                header('Content-Type: image/png; ' . $name);
                header('Content-Disposition: inline; ' . $name);
            } else {
                header('Content-Type: image/jpeg; ' . $name);
                header('Content-Disposition: inline; ' . $name);
            }
            readfile($tempfile);
            $retval = true;
        }
        imagick_destroyhandle($dst);
        return $retval;
    }

    // }}}
    // {{{ image manipulation methods using ImageMagick's convert command

    /**
     * ImageMagickのコマンド生成
     *
     * @access private
     * @return string
     */
    function _magickCommand($source, $thumbnail, $srcsize, $thumbsize)
    {
        $command = $this->magick;

        // 元のサイズを指定
        $command .= sprintf(' -size %s', escapeshellarg($srcsize));

        // 複数フレームからなる画像かもしれないとき
        if (preg_match('/\.gif$/', $source)) {
            $command .= ' +adjoin';
            $source .= '[0]';
        }

        // クロップしてパイプ
        if (is_array($this->coord)) {
            $x = $this->coord['x'];
            $y = $this->coord['y'];
            $command .= sprintf(" -crop '%dx%d+%d+%d'", $x[1], $y[1], $x[0], $y[0]);
            $command .= sprintf(' %s', escapeshellarg($source));
            $command .= ' - | ' . $this->magick;
            $command .= sprintf(" -size '%dx%d'", $x[1], $y[1]);
            $source = '-';
        }

        // 透過部分の背景色を任意の色にするのはめんどくさそうなので保留
        /*if (!is_null($this->bgcolor)) {
            $command .= sprintf(' -background %s', escapeshellarg($this->bgcolor));
        }*/
        // 回転
        if ($this->rotate) {
            $command .= sprintf(' -rotate %d', $this->rotate);
        }

        // サムネイルのサイズを指定・メタデータは除去
        if ($this->magick6) {
            if ($this->resize) {
                $command .= sprintf(' -thumbnail %s', escapeshellarg($thumbsize));
            } else {
                $command .= ' -strip';
            }
        } else {
            if ($this->resize) {
                $command .= sprintf(' -scale %s', escapeshellarg($thumbsize));
            }
            $command .= " +profile '*'";
        }
        // サムネイルの画像形式
        $command .= sprintf(' -format %s', (($this->type == '.png') ? 'PNG' : 'JPEG'));
        // サムネイルの品質
        if ($this->quality) {
            $command .= sprintf(' -quality %d', $this->quality);
        }

        // 元の画像のパスを指定
        $command .= sprintf(' %s', ((!$source || $source == '-') ? '-' : escapeshellarg($source)));
        // サムネイルの出力先を指定
        $command .= sprintf(' %s', ((!$thumbnail || $thumbnail == '-') ? '-' : escapeshellarg($thumbnail)));

        return $command;
    }

    /**
     * ImageMagickで変換、ファイルに出力
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_magickSave($source, $thumbnail, $srcsize, $thumbsize)
    {
        $command = $this->_magickCommand($source, $thumbnail, $srcsize, $thumbsize);
        @exec($command, $results, $status);
        if ($status != 0) {
            $errmsg = "convert failed. ( $command . )\n";
            while (!is_null($errstr = array_shift($results))) {
                if ($errstr === '') { break; }
                $errmsg .= $errstr . "\n";
            }
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $retval = true;
        }
        return $retval;
    }

    /**
     * ImageMagickで変換、バッファに保存
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_magickCapture($source, $srcsize, $thumbsize)
    {
        $command = $this->_magickCommand($source, '-', $srcsize, $thumbsize);
        ob_start();
        @passthru($command, $status);
        $this->buf = ob_get_clean();
        if ($status != 0) {
            $errmsg = "convert failed. ( $command . )\n";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $retval = true;
        }
        return $retval;
    }

    /**
     * ImageMagickで変換、直接出力
     *
     * @access private
     * @return boolean | object PEAR_Error
     */
    function &_magickOutput($source, $thumbnail, $srcsize, $thumbsize)
    {
        $command = $this->_magickCommand($source, '-', $srcsize, $thumbsize);
        $name = 'filename="' . basename($thumbnail) . '"';
        if ($this->type == '.png') {
            header('Content-Type: image/png; ' . $name);
            header('Content-Disposition: inline; ' . $name);
        } else {
            header('Content-Type: image/jpeg; ' . $name);
            header('Content-Disposition: inline; ' . $name);
        }
        @passthru($command, $status);
        if ($status != 0) {
            $errmsg = "convert failed. ( $command . )\n";
            $retval = &PEAR::raiseError($errmsg);
        } else {
            $retval = true;
        }
        return $retval;
    }

    // }}}
    // {{{ public utility methods

    /**
     * サムネイルサイズ計算
     *
     * @access public
     */
    function calc($width, $height)
    {
        // デフォルト値・フラグを設定
        $t_width  = $width;
        $t_height = $height;
        $this->resize = false;
        $this->coord   = false;
        // ソースがサムネイルの最大サイズより小さいとき、ソースの大きさをそのまま返す
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
                // ソースがサムネイルの最大サイズより小さいとき、縮小せずにトリミング
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
                $this->resize = true;
                $t_sub = $max_sub;
                $c_length = ceil($sub * ($t_main / $t_sub));
            }
            $this->coord[$c_main] = array(floor(($main - $c_length) / 2), $c_length);
        } else {
            // アスペクト比を維持したまま縮小し、トリミングはしない
            $this->resize = true;
            $t_sub = round($max_main * ($sub / $main));
        }
        // サムネイルサイズを返す
        return ($t_width . 'x' . $t_height);
    }

    /**
     * ソース画像のパスを取得
     *
     * @access public
     */
    function srcPath($size, $md5, $mime, $FSFullPath = false)
    {
        $directory = $this->getSubDir($this->sourcedir, $size, $md5, $mime, $FSFullPath);
        if (!$directory) {
            return false;
        }

        $basename = $size . '_' . $md5 . $this->mimemap[$mime];

        return $directory . ($FSFullPath ? DIRECTORY_SEPARATOR : '/') . $basename;
    }

    /**
     * サムネイルのパスを取得
     *
     * @access public
     */
    function thumbPath($size, $md5, $mime, $FSFullPath = false)
    {
        $directory = $this->getSubDir($this->thumbdir, $size, $md5, $mime, $FSFullPath);
        if (!$directory) {
            return false;
        }

        $basename = $size . '_' . $md5;
        if ($this->rotate) {
            $basename .= '_' . str_pad($this->rotate, 3, 0, STR_PAD_LEFT);
        }
        if ($this->trim) {
            $basename .= '_tr';
        }
        $basename .= $this->type;

        return $directory . ($FSFullPath ? DIRECTORY_SEPARATOR : '/') . $basename;
    }

    /**
     * 画像が保存されるサブディレクトリのパスを取得
     *
     * @access public
     */
    function getSubDir($basedir, $size, $md5, $mime, $FSFullPath = false)
    {
        if (!is_dir($basedir)) {
            return false;
        }

        $dirID = $this->dirID($size, $md5, $mime);

        if ($FSFullPath) {
            $directory = realpath($basedir) . DIRECTORY_SEPARATOR . $dirID;
        } else {
            $directory = $basedir . '/' . $dirID;
        }

        return $directory;
    }

    /**
     * 画像1000枚ごとにインクリメントするディレクトリIDを取得
     *
     * @access public
     */
    function dirID($size = null, $md5 = null, $mime = null)
    {
        if ($size && $md5 && $mime) {
            $icdb = &new IC2DB_Images;
            $icdb->whereAddQUoted('size', '=', $size);
            $icdb->whereAddQuoted('md5',  '=', $md5);
            $icdb->whereAddQUoted('mime', '=', $mime);
            $icdb->orderByArray(array('id' => 'ASC'));
            if ($icdb->find(true)) {
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
    // {{{ private utility methods

    /**
     * 背景色を設定
     *
     * @access private
     */
    function _bgcolor($r, $g, $b)
    {
        if (is_null($r) || is_null($g) || is_null($b)) {
            $this->bgcolor = null;
            return;
        }
        switch ($this->driver) {
            case 'gd':
            case 'imlib2':
                $this->bgcolor = array($r, $g, $b);
                break;
            case 'imagick':
            case 'imagemagick':
                $this->bgcolor = sprintf('rgb(%d,%d,%d)', $r, $g, $b);
                break;
            default:
                $this->bgcolor = sprintf('%d,%d,%d', $r, $g, $b);
        }
    }

    /**
     * 一時ファイルのパスを返す
     * 作成した一時ファイルは終了時に自動で削除される
     *
     * @access private
     */
    function _tempnam()
    {
        $tmp = tempnam(realpath($this->cachedir), sprintf('dump_%s_', date('ymdhis')));
        register_shutdown_function(create_function('', '@unlink("'.addslashes($tmp).'");'));
        return $tmp;
    }

    // }}}
    // {{{ error method

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

/*
 * Local variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
