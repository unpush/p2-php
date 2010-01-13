<?php
/**
 * Thumbnailer_Imagick
 * PHP Version 5
 */

// {{{ Thumbnailer_Imagick

/**
 * Image manipulation class which uses imagick php extension version 2.0 or later.
 */
class Thumbnailer_Imagick extends Thumbnailer_Common
{
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
        try {
            return $this->_convert($source, $size)->writeImage($thumbnail);
        } catch (Exception $e) {
            return PEAR::raiseError(get_class($e) . '::' . $e->getMessage());
        }
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
        try {
            return $this->_convert($source, $size)->getImageBlob();
        } catch (Exception $e) {
            return PEAR::raiseError(get_class($e) . '::' . $e->getMessage());
        }
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
        try {
            $blob = $this->_convert($source, $size)->getImageBlob();
            if ($blob) {
                $this->_httpHeader($name, strlen($blob));
                echo $blob;
                return true;
            } else {
                return PEAR::raiseError("Failed to create a thumbnail.");
            }
        } catch (Exception $e) {
            return PEAR::raiseError(get_class($e) . '::' . $e->getMessage());
        }
    }

    // }}}
    // {{{ _convert()

    /**
     * Image conversion abstraction.
     *
     * @param string $source
     * @param array $size
     * @return Imagick
     */
    protected function _convert($source, $size)
    {
        extract($size);

        $im = new Imagick();
        $im->readImage($source);

        if (method_exists($im, 'rewind')) {
            $im->rewind();
        }
        if (method_exists($im, 'flattenImages')) {
            $im->flattenImages();
        }
        if (method_exists($im, 'getImageMatte') && method_exists($im, 'setImageMatte')) {
            if ($im->getImageMatte()) {
                $im->setImageMatte(false);
            }
        }

        if ($this->doesTrimming()) {
            $im->cropImage($sw, $sh, $sx, $sy);
        }

        if ($this->doesResampling()) {
            $im->thumbnailImage($tw, $th);
        } else {
            $im->stripImage();
        }

        if ($degrees = $this->getRotation()) {
            $bgcolor = $this->getBgColor();
            $bg = sprintf('rgb(%d,%d,%d)', $bgcolor[0], $bgcolor[1], $bgcolor[2]);
            $im->rotateImage(new ImagickPixel($bg), $degrees);
        }

        if ($this->isPng()) {
            $im->setFormat('PNG');
        } else {
            $im->setFormat('JPEG');
            if ($this->getQuality()) {
                $im->setCompressionQuality($this->getQuality());
            }
        }

        $im->setImageColorSpace(Imagick::COLORSPACE_RGB);

        return $im;
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
