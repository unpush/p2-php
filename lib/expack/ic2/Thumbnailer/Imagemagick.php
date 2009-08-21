<?php
/**
 * Thumbnailer_Imagemagick
 * PHP Version 5
 */

require_once dirname(__FILE__) . '/Common.php';

// {{{ Thumbnailer_Imagemagick

/**
 * Image manipulation class which uses ImageMagick.
 */
class Thumbnailer_Imagemagick extends Thumbnailer_Common
{
    // {{{ properties

    protected $_imagemagick_convert = 'convert';
    protected $_imagemagick_version_gte6 = true;
    protected $_imagemagick_have_flatten = true;

    // }}}
    // {{{ setImageMagickConvertPath()

    /**
     * Sets the path of convert(1);
     *
     * @param string $path
     * @return void
     */
    public function setImageMagickConvertPath($path)
    {
        if (is_file($path) && is_executable($path)) {
            $convert = escapeshellarg($path);
            $this->_imagemagick_convert = $convert;

            $output = null;
            @exec("$convert --version 2>&1", $output);
            if ($output && preg_match('/Version: ImageMagick (([0-9.]+)-[0-9]+)/', $output[0], $v)) {
                $this->_imagemagick_version_gte6 = version_compare($v[2], '6.0.0',   'ge');
                $this->_imagemagick_have_flatten = version_compare($v[1], '6.3.6-2', 'ge');
            } else {
                $this->_imagemagick_version_gte6 = false;
                $this->_imagemagick_have_flatten = false;
            }
        }
    }

    // }}}
    // {{{ save()

    /**
     * Convert and save.
     *
     * @param string $source
     * @param string $thumbnail
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    public function save($source, $thumbnail, $size)
    {
        $command = $this->_convert($source, $size) . ' ' . $this->decorate($source, '') . ' ' . escapeshellarg($thumbnail);
        @exec($command, $results, $status);
        if ($status != 0) {
            $errmsg = "convert failed. ( $command . )\n";
            while (!is_null($errstr = array_shift($results))) {
                if ($errstr === '') { break; }
                $errmsg .= $errstr . "\n";
            }
            $retval = PEAR::raiseError($errmsg);
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
     * @param string $source
     * @param array $size
     * @return string
     * @throws PEAR_Error
     */
    public function capture($source, $size)
    {
        $command = $this->_convert($source, $size) . ' -';
        ob_start();
        @passthru($command, $status);
        $retval = ob_get_clean();
        if ($status != 0) {
            unset($retval);
            $retval = PEAR::raiseError("convert failed. (`{$command}`)");
        }
        return $retval;
    }

    // }}}
    // {{{ output()

    /**
     * Convert and output.
     *
     * @param string $source
     * @param string $name
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    public function output($source, $name, $size)
    {
        $command = $this->_convert($source, $size) . ' -';
        $this->_httpHeader($name);
        @passthru($command, $status);
        if ($status != 0) {
            $retval = PEAR::raiseError("convert failed. (`{$command}`)");
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
     * @param string $source
     * @param array $size
     * @return string
     */
    protected function _convert($source, $size)
    {
        $source = (!$source || $source == '-') ? '-' : escapeshellarg($source);
        extract($size);

        $command = $this->_imagemagick_convert;

        // 元のサイズを指定
        $command .= sprintf(' -size %dx%d', $w, $h);

        // 複数フレームからなる画像かもしれないとき
        if ($this->_imagemagick_have_flatten) {
            $command .= ' -flatten';
        }
        if (!$this->windows && preg_match('/\\.gif$/', $source)) {
            $command .= ' +adjoin';
            $source .= '[0]';
        }

        // クロップしてパイプ
        if ($this->doesTrimming()) {
            $command .= sprintf(' -format PNG -crop %dx%d+%d+%d %s - | %s -size %dx%d',
                                $sw, $sh, $sx, $sy,
                                $source,
                                $this->_imagemagick_convert,
                                $sw, $sh);
            $source = '-';
        }


        // 回転
        if ($degrees = $this->getRotation()) {
            $bgcolor = $this->getBgColor();
            $command .= sprintf(' -rotate %d', $degrees);
            $command .= sprintf(' -background rgb(%d,%d,%d)', $bgcolor[0], $bgcolor[1], $bgcolor[2]);
            if ($degrees % 180 == 90) {
                $_t = $tw;
                $tw = $th;
                $th = $_t;
            }
        }

        // サムネイルのサイズを指定・メタデータは除去
        if ($this->_imagemagick_version_gte6) {
            if ($this->doesResampling()) {
                $command .= sprintf(' -thumbnail %dx%d!', $tw, $th);
            } else {
                $command .= ' -strip';
            }
        } else {
            if ($this->doesResampling()) {
                $command .= sprintf(' -scale %dx%d!', $tw, $th);
            }
            $command .= " +profile '*'";
        }

        // サムネイルの画像形式と品質
        if ($this->isPng()) {
            $command .= ' -format PNG';
        } else {
            $command .= ' -format JPEG';
            if ($this->getQuality()) {
                $command .= sprintf(' -quality %d', $this->getQuality());
            }
        }

        // 元の画像のパスを指定
        $command .= ' ' . $source;

        return $command;
    }

    // }}}
    // {{{ _decorateAnimationGif()

    /**
     * stamp animation gif mark.
     *
     * @param mixed $thumb
     * @return mixed
     */
    protected function _decorateAnimationGif($thumb)
    {
        return (strlen($thumb) ? $thumb . ' ' : '') .
            escapeshellarg($this->getDecorateAnigifFilePath());
    }

    // }}}
    // {{{ _decorateGifCaution()

    /**
     * stamp gif caution mark.
     *
     * @param resource $thumb
     * @return resource
     */
    protected function _decorateGifCaution($thumb)
    {
        return (strlen($thumb) ? $thumb . ' ' : '') .
            escapeshellarg($this->getDecorateGifCautionFilePath());
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
