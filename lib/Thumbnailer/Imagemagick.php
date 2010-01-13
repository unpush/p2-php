<?php
/**
 * Thumbnailer_Imagemagick
 * PHP Version 5
 */

// {{{ Thumbnailer_Imagemagick

/**
 * Image manipulation class which uses ImageMagick.
 */
class Thumbnailer_Imagemagick extends Thumbnailer_Common
{
    // {{{ properties

    protected $_imageMagickConvert = 'convert';
    protected $_imageMagickVersion6 = true;
    protected $_imageMagickSupportsFlatten = true;
    protected $_imageMagickSupportsAdjoin = true;

    // }}}
    // {{{ __construct

    /**
     * Constructor
     *
     * @param string $path
     */
    public function __construct($path = null)
    {
        if ($path !== null) {
            $this->setImageMagickConvertPath($path);
        }

        if (strncasecmp(PHP_OS, 'win', 3) == 0) {
            $this->_imageMagickSupportsAdjoin = false;
        }
    }

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
            $this->_imageMagickConvert = $convert;

            $output = null;
            @exec("$convert --version 2>&1", $output);
            if ($output && preg_match('/Version: ImageMagick (([0-9.]+)-[0-9]+)/', $output[0], $v)) {
                $this->_imageMagickVersion6 = version_compare($v[2], '6.0.0',   'ge');
                $this->_imageMagickSupportsFlatten = version_compare($v[1], '6.3.6-2', 'ge');
            } else {
                $this->_imageMagickVersion6 = false;
                $this->_imageMagickSupportsFlatten = false;
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
        $command = $this->_convert($source, $size) . ' ' . escapeshellarg($thumbnail);
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
        if ($source === '' || $source === null) {
            $source = '-';
        }
        extract($size);

        $command = $this->_imageMagickConvert;

        // 元のサイズを指定
        $command .= sprintf(' -size "%dx%d"', $w, $h);

        // 複数フレームからなる画像かもしれないとき
        if (preg_match('/\\.gif$/i', $source)) {
            if ($this->_imageMagickSupportsFlatten && !$this->doesTrimming()) {
                $command .= ' -flatten';
            }
            if ($this->_imageMagickSupportsAdjoin) {
                $source .= '[0]';
            }
        }

        // エスケープ
        if ($source != '-') {
            $source =  escapeshellarg($source);
        }

        // クロップしてパイプ
        if ($this->doesTrimming()) {
            $command .= sprintf(' -format PNG -crop "%dx%d+%d+%d" %s - | %s -size "%dx%d"',
                                $sw, $sh, $sx, $sy,
                                $source,
                                $this->_imageMagickConvert,
                                $sw, $sh);
            $source = '-';
        }


        // 回転
        if ($degrees = $this->getRotation()) {
            $bgcolor = $this->getBgColor();
            $command .= sprintf(' -rotate %d', $degrees);
            $command .= sprintf(' -background "rgb(%d,%d,%d)"',
                                $bgcolor[0], $bgcolor[1], $bgcolor[2]);
            if ($degrees % 180 == 90) {
                $_t = $tw;
                $tw = $th;
                $th = $_t;
            }
        }

        // サムネイルのサイズを指定・メタデータは除去
        if ($this->_imageMagickVersion6) {
            if ($this->doesResampling()) {
                $command .= sprintf(' -thumbnail "%dx%d!"', $tw, $th);
            } else {
                $command .= ' -strip';
            }
        } else {
            if ($this->doesResampling()) {
                $command .= sprintf(' -scale "%dx%d!"', $tw, $th);
            }
            $command .= ' +profile "*"';
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
