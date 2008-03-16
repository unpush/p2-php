<?php
/**
 * Thumbnailer_Common
 * PHP Versions 4 and 5
 */

require_once 'PEAR.php';

// {{{ Thumbnailer_Common

/**
 * Image manipulation abstraction.
 *
 * @abstract
 */
class Thumbnailer_Common
{
    // {{{ protected properties

    var $_bgcolor = array(0, 0, 0, 255);
    var $_http;
    var $_png;
    var $_quality = 70;
    var $_resampling = true;
    var $_rotation = 0;
    var $_tempDir = '/tmp';
    var $_trimming = false;

    // }}}
    // {{{ setBgColor()

    /**
     * Sets background color.
     *
     * @access public
     * @param int $r
     * @param int $g
     * @param int $b
     * @param int $a (optional)
     * @return void
     */
    function setBgColor($r, $g, $b, $a = 255)
    {
        $this->_bgcolor = array($r, $g, $b, $a);
    }

    // }}}
    // {{{ setHttp()

    /**
     * Sets whether to output HTTP headers.
     *
     * @access public
     * @param bool $http
     * @return void
     */
    function setHttp($http)
    {
        $this->_http = $http;
    }

    // }}}
    // {{{ setPng()

    /**
     * Sets whether to save as png or jpeg.
     *
     * @access public
     * @param bool $png
     * @return void
     */
    function setPng($png)
    {
        $this->_png = $png;
    }

    // }}}
    // {{{ setQuality()

    /**
     * Sets quality.
     *
     * @access public
     * @param int $quality
     * @return void
     */
    function setQuality($quality)
    {
        $this->_quality = $quality;
    }

    // }}}
    // {{{ setResampling()

    /**
     * Sets whether to reample or not.
     *
     * @access public
     * @param bool $resample
     * @return void
     */
    function setResampling($resample)
    {
        $this->_resampling = $resample;
    }

    // }}}
    // {{{ setRotation()

    /**
     * Sets rotation.
     *
     * @access public
     * @param int $angle
     * @return void
     */
    function setRotation($angle)
    {
        $this->_rotation = $angle;
    }

    // }}}
    // {{{ setTempDir()

    /**
     * Sets temporaty directory.
     *
     * @access public
     * @param string $dir
     * @return void
     */
    function setTempDir($dir)
    {
        if (is_dir($dir)) {
            $this->_tempDir = realpath($dir);
        }
    }

    // }}}
    // {{{ setTrimming()

    /**
     * Sets whether to trim or not.
     *
     * @access public
     * @param bool $trim
     * @return void
     */
    function setTrimming($trim)
    {
        $this->_trimming = $trim;
    }

    // }}}
    // {{{ save()

    /**
     * Convert and save.
     *
     * @abstract
     * @access public
     * @param string $source
     * @param string $thumbnail
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    function save($source, $thumbnail, $size)
    {
        return PEAR::raiseError(__CLASS__ . '::' . __METHOD__ . ' must be inherited.');
    }

    /**
     * Convert and capture.
     *
     * @abstract
     * @access public
     * @param string $source
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    function capture($source, $size)
    {
        return PEAR::raiseError(__CLASS__ . '::' . __METHOD__ . ' must be inherited.');
    }

    /**
     * Convert and output.
     *
     * @abstract
     * @access public
     * @param string $source
     * @param string $name
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    function output($source, $name, $size)
    {
        return PEAR::raiseError(__CLASS__ . '::' . __METHOD__ . ' must be inherited.');
    }

    // }}}
    // {{{ _convert()

    /**
     * Image conversion abstraction.
     *
     * @abstract
     * @access protected
     * @param string $source
     * @param array $size
     * @return mixed
     */
    function _convert($source, $size)
    {
        return PEAR::raiseError(__CLASS__ . '::' . __METHOD__ . ' must be inherited.');
    }

    // }}}
    // {{{ _tempnam()

    /**
     * Creates temporary file name which will be removed on shutdown.
     *
     * @access protected
     * @return string
     */
    function _tempnam()
    {
        $tmp = tempnam($this->_tempDir, 'thumb_temp_');
        $esc = addslashes($tmp);
        register_shutdown_function(create_function('',
            'if (file_exists("' . $esc . '")) { @unlink("' . $esc . '"); }'));
        return $tmp;
    }

    // }}}
    // {{{ _httpHeader()

    /**
     * Outputs HTTP header.
     *
     * @access protected
     * @param string $name
     * @param int $length
     * @return void
     */
    function _httpHeader($name = null, $length = null)
    {
        if ($this->_http) {
            $mimetype = 'image/' . (($this->_png) ? 'png' : 'jpeg');
            if ($name) {
                $name = 'filename="' . basename($name) . '"';
                header('Content-Type: ' . $mimetype . '; ' . $name);
                header('Content-Disposition: inline; ' . $name);
            } else {
                header('Content-Type: ' . $mimetype);
                header('Content-Disposition: inline');
            }
            if ($length) {
                header(sprintf('Content-Length: %d', $length));
            }
        }
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
