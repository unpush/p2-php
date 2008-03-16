<?php
/**
 * Thumbnailer_Imagick
 * PHP Version 5
 */

require_once dirname(__FILE__) . '/Common.php';

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
     * @access public
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
     * @access public
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
     * @access public
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
     * @access protected (Currently set to be public because of PHP4 compatibility of the parent class.)
     * @param string $source
     * @param array $size
     * @return object Imagick
     */
    /*protected*/ public function _convert($source, $size)
    {
        extract($size);

        $im = new Imagick();

        if ($this->_trimming) {
            $in = new Imagick();
            $in->readImage($source);
            $in->setFormat('PNG');
            $in->cropImage($sw, $sh, $sx, $sy);
            $im->readImageBlob($in->getImageBlob());
            unset($in);
        } else {
            $im->readImage($source);
        }

        if ($this->_rotation) {
            $bg = sprintf('rgb(%d,%d,%d)', $this->_bgcolor[0], $this->_bgcolor[1], $this->_bgcolor[2]);
            $im->rotateImage(new ImagickPixel($bg), $this->_rotation);
            if ($this->_rotation % 180 == 90) {
                $_t = $tw;
                $tw = $th;
                $th = $_t;
            }
        }

        if ($this->_resampling) {
            $im->thumbnailImage($tw, $th);
        } else {
            $im->stripImage();
        }

        if ($this->_png) {
            $im->setFormat('PNG');
        } else {
            $im->setFormat('JPEG');
            if ($this->_quality) {
                $im->setCompressionQuality($this->_quality);
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
