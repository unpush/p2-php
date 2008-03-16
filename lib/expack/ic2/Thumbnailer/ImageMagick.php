<?php
/**
 * Thumbnailer_ImageMagick
 * PHP Versions 4 and 5
 */

require_once dirname(__FILE__) . '/Common.php';

// {{{ Thumbnailer_ImageMagick

/**
 * Image manipulation class which uses ImageMagick.
 */
class Thumbnailer_ImageMagick extends Thumbnailer_Common
{
    // {{{ protected properties

    var $_imagemagick_convert = 'covnert';
    var $_imagemagick_version6 = true;

    // }}}
    // {{{ setImageMagickConvertPath()

    /**
     * Sets the path of convert(1);
     *
     * @access public
     * @param string $path
     * @return void
     */
    function setImageMagickConvertPath($path)
    {
        if (is_file($path) && is_executable($path)) {
            $this->_imagemagick_convert = escapeshellarg($path);
        }
    }

    // }}}
    // {{{ setImageMagick6()

    /**
     * Sets wheter the avaliable ImageMagick's version is greater than 6.0 or not.
     *
     * @access public
     * @param bool $version6
     * @return void
     */
    function setImageMagick6($version6)
    {
        $this->_imagemagick_version6 = $version6;
    }

    // }}}
    // {{{ save()

    /**
     * Convert and save.
     *
     * @access public
     * @param string $source
     * @param string $thumbnail
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    function save($source, $thumbnail, $size)
    {
        $command = $this->_convert($source, $size) . ' ' . escapeshellarg($thumbnail);
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

    // }}}
    // {{{ capture()

    /**
     * Convert and capture.
     *
     * @access public
     * @param string $source
     * @param array $size
     * @return string
     * @throws PEAR_Error
     */
    function capture($source, $size)
    {
        $command = $this->_convert($source, $size) . ' -';
        ob_start();
        @passthru($command, $status);
        $retval = ob_get_clean();
        if ($status != 0) {
            unset($retval);
            $retval = &PEAR::raiseError("convert failed. (`{$command}`)");
        }
        return $retval;
    }

    // }}}
    // {{{ output()

    /**
     * Convert and output.
     *
     * @access public
     * @param string $source
     * @param string $name
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    function output($source, $name, $size)
    {
        $command = $this->_convert($source, $size) . ' -';
        $this->_httpHeader($name);
        @passthru($command, $status);
        if ($status != 0) {
            $retval = &PEAR::raiseError("convert failed. (`{$command}`)");
        } else {
            $retval = true;
        }
        return $retval;
    }

    // }}}
    // {{{ _convert()

    /**
     * Image conversion abstraction.
     *
     * @access protected
     * @param string $source
     * @param array $size
     * @return string
     */
    function _convert($source, $size)
    {
        $source = (!$source || $source == '-') ? '-' : escapeshellarg($source);
        extract($size);

        $command = $this->_imagemagick_convert;

        // 元のサイズを指定
        $command .= sprintf(' -size %dx%d', $w, $h);

        // 複数フレームからなる画像かもしれないとき
        if (preg_match('/\.gif$/', $source)) {
            $command .= ' +adjoin';
            $source .= '[0]';
        }

        // クロップしてパイプ
        if ($this->_trimming) {
            $command .= sprintf(' -format PNG -crop %dx%d+%d+%d %s - | %s -size %dx%d',
                                $sw, $sh, $sx, $sy,
                                $source,
                                $this->_imagemagick_convert,
                                $sw, $sh);
            $source = '-';
        }

        // 透過部分の背景色を任意の色にするのはめんどくさそうなので保留
        //$command .= sprintf(' -background rgb(%d,%d,%d)', $this->_bgcolor[0], $this->_bgcolor[1], $this->_bgcolor[2]);

        // 回転
        if ($this->_rotation) {
            $command .= sprintf(' -rotate %d', $this->_rotation);
            if ($this->_rotation % 180 == 90) {
                $_t = $tw;
                $tw = $th;
                $th = $_t;
            }
        }

        // サムネイルのサイズを指定・メタデータは除去
        if ($this->_imagemagick_version6) {
            if ($this->_resampling) {
                $command .= sprintf(' -thumbnail %dx%d!', $tw, $th);
            } else {
                $command .= ' -strip';
            }
        } else {
            if ($this->_resampling) {
                $command .= sprintf(' -scale %dx%d!', $tw, $th);
            }
            $command .= " +profile '*'";
        }

        // サムネイルの画像形式と品質
        if ($this->_png) {
            $command .= ' -format PNG';
        } else {
            $command .= ' -format JPEG';
            if ($this->_quality) {
                $command .= sprintf(' -quality %d', $this->_quality);
            }
        }

        // 元の画像のパスを指定
        $command .= ' ' . $source;

        return $command;
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
