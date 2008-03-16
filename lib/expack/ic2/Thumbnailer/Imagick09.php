<?php
/**
 * Thumbnailer_Imagick09
 * PHP Versions 4 and 5
 */

require_once dirname(__FILE__) . '/Common.php';

// {{{ Thumbnailer_Imagick09

/**
 * Image manipulation class which uses imagick php extension version 0.9.13 or earlier.
 */
class Thumbnailer_Imagick09 extends Thumbnailer_Common
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
    function save($source, $thumbnail, $size)
    {
        $dst = $this->_convert($source, $size);
        // サムネイルを保存
        if ($this->_quality > 0) {
            imagick_setcompressionquality($dst, $this->_quality);
        }
        $prefix = (($this->_png) ? 'png' : 'jpeg') . ':';
        $result = imagick_writeimage($dst, $prefix.$thumbnail);
        if (!$result) {
            $reason = imagick_failedreason($dst);
            $detail = imagick_faileddescription($dst);
            $retval = &PEAR::raiseError("Failed to create a thumbnail. ({$thumbnail}:{$reason}:{$detail})");
        } else {
            $retval = true;
        }
        imagick_destroyhandle($dst);
        return $retval;
    }

    // }}}
    // {{{ capture()

    /**
     * Convert and capture.
     *
     * imagick_image2blob() ではうまくいかないので
     * いったん一時ファイルに書き出したデータを読み込む
     *
     * @access public
     * @param string $source
     * @param array $size
     * @return string
     * @throws PEAR_Error
     */
    function capture($source, $size)
    {
        $dst = $this->_convert($source, $size);
        // サムネイルを作成
        if ($this->_quality > 0) {
            imagick_setcompressionquality($dst, $this->_quality);
        }
        $prefix = (($this->_png) ? 'png' : 'jpeg') . ':';
        $tempfile = $this->_tempnam();
        $result = imagick_writeimage($dst, $prefix.$tempfile);
        if (!$result) {
            $reason = imagick_failedreason($dst);
            $detail = imagick_faileddescription($dst);
            $retval = &PEAR::raiseError("Failed to create a thumbnail. ({$thumbnail}:{$reason}:{$detail})");
        } else {
            $retval = file_get_contents($tempfile);
        }
        imagick_destroyhandle($dst);
        return $retval;
    }

    // }}}
    // {{{ output()

    /**
     * Convert and output.
     *
     * imagick_image2blob() ではうまくいかないので
     * いったん一時ファイルに書き出し、readfile() する
     *
     * @access protected
     * @param string $source
     * @param string $name
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    function output($source, $name, $size)
    {
        $dst = $this->_convert($source, $size);
        // サムネイルを出力
        if ($this->_quality) {
            imagick_setcompressionquality($dst, $this->_quality);
        }
        $prefix = (($this->_png) ? 'png' : 'jpeg') . ':';
        $tempfile = $this->_tempnam();
        $result = imagick_writeimage($dst, $prefix.$tempfile);
        if (!$result) {
            $reason = imagick_failedreason($dst);
            $detail = imagick_faileddescription($dst);
            $retval = &PEAR::raiseError("Failed to create a thumbnail. ({$name}:{$reason}:{$detail})");
        } else {
            $this->_httpHeader($name, filesize($tempfile));
            readfile($tempfile);
            $retval = true;
        }
        imagick_destroyhandle($dst);
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
     * @return resource imagick handle
     */
    function _convert($source, $size)
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
            $error = &PEAR::raiseError("Failed to load the image. ({$source}:{$reason}:{$detail})");
            return $error;
        }
        // サムネイルのイメージストリームを作成
        $bg = sprintf('rgb(%d,%d,%d)', $this->_bgcolor[0], $this->_bgcolor[1], $this->_bgcolor[2]);
        $dst = imagick_getcanvas($bg, $tw, $th);
        // ソースをリサイズし、サムネイルにコピー
        if ($sx != 0 || $sy != 0) {
            imagick_crop($src, $sx, $sy, $sw, $sh);
        }
        if ($this->_resampling) {
            imagick_scale($src, $tw, $th, '!');
        }
        imagick_composite($dst, IMAGICK_COMPOSITE_OP_ATOP, $src, 0, 0);
        imagick_destroyhandle($src);
        // 回転
        if ($this->_rotation) {
            imagick_rotate($dst, $this->_rotation);
        }
        return $dst;
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
