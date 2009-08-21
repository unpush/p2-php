<?php
/**
 * Thumbnailer_Magickwand
 * PHP Version 5
 */

require_once dirname(__FILE__) . '/Common.php';

// {{{ Thumbnailer_Magickwand

/**
 * Image manipulation class which uses magickwand php extension.
 *
 * @deprecated
 */
class Thumbnailer_Magickwand extends Thumbnailer_Common
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
        $im = $this->_convert($source, $size);
        if (PEAR::isError($im)) {
            return $im;
        }

        if (MagickWriteImage($im, $thumbnail)) {
            return true;
        } else {
            return $this->_raiseError($im, "Failed to write thumbnail({$source}:{$thumbnail}).");
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
        $im = $this->_convert($source, $size);
        if (PEAR::isError($im)) {
            return $im;
        }

        $blob = MagickGetImageBlob($im);
        if ($blob) {
            return $blob;
        } else {
            return $this->_raiseError($im, "Failed to get thumbnail({$source}).");
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
        $im = $this->_convert($source, $size);
        if (PEAR::isError($im)) {
            return $im;
        }

        $this->_httpHeader($name);
        if (MagickEchoImageBlob($im)) {
            return true;
        } else {
            return $this->_raiseError($im, "Failed to output thumbnail({$source}).");
        }
    }

    // }}}
    // {{{ _convert()

    /**
     * Image conversion abstraction.
     *
     * @param string $source
     * @param array $size
     * @return resource MagickWand
     * @throws PEAR_Error
     */
    protected function _convert($source, $size)
    {
        extract($size);

        $im = NewMagickWand();
        if (!MagickReadImage($im, $source)) {
            return $this->_raiseError($im, "Failed to read image ({$source}).");
        }

        if ($this->doesTrimming()) {
            if (!MagickCropImage($im, $sw, $sh, $sx, $sy)) {
                return $this->_raiseError($in, "Failed to crop image ({$source}).");
            }
        }

        if ($this->doesResampling()) {
            if (!MagickThumbnailImage($im, $tw, $th)) {
                return $this->_raiseError($im, "Failed to thumbnail image ({$source}).");
            }
        } else {
            if (!MagickStripImage($im)) {
                return $this->_raiseError($im, "Failed to strip image ({$source}).");
            }
        }

        if ($degrees = $this->getRotation()) {
            $bgcolor = $this->getBgColor();
            $bg = sprintf('rgb(%d,%d,%d)', $bgcolor[0], $bgcolor[1], $bgcolor[2]);
            if (!MagickRotateImage($im, NewPixelWand($bg), $degrees)) {
                return $this->_raiseError($im, "Failed to rotate image ({$source}).");
            }
        }

        if ($this->isPng()) {
            MagickSetFormat($im, 'PNG');
        } else {
            MagickSetFormat($im, 'JPEG');
            if ($this->getQuality()) {
                MagickSetCompressionQuality($im, $this->getQuality());
            }
        }

        MagickSetImageColorspace($im, MW_RGBColorspace);

        return $im;
    }

    // }}}
    // {{{ _raiseError()

    /**
     * Raises PEAR_Error.
     *
     * @param resource MagickWand $im
     * @param string $errmsg
     * @return PEAR_Error
     */
    protected function _raiseError($im, $errmsg)
    {
        if (WandHasException($im)) {
            $errmsg .= "\n" . WandGetExceptionString($im);
        }
        $err = PEAR::raiseError($errmsg);
        return $err;
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
        // TODO (not implemented)
        return $thumb;
    }

    // }}}
    // {{{ _decorateGifCaution()

    /**
     * stamp gif caution mark.
     *
     * @param mixed $thumb
     * @return mixed
     */
    protected function _decorateGifCaution($thumb)
    {
        // TODO (not implemented)
        return $thumb;
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
