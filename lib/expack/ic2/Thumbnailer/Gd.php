<?php
/**
 * Thumbnailer_Gd
 * PHP Versions 4 and 5
 */

require_once dirname(__FILE__) . '/Common.php';

// {{{ Thumbnailer_Gd

/**
 * Image manipulation class which uses gd php extension.
 */
class Thumbnailer_Gd extends Thumbnailer_Common
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
        if ($this->_png) {
            $result = imagepng($dst, $thumbnail);
        } else {
            $result = imagejpeg($dst, $thumbnail, $this->_quality);
        }
        imagedestroy($dst);
        if (!$result) {
            $retval = &PEAR::raiseError("Failed to create a thumbnail. ({$thumbnail})");
        } else {
            $retval = true;
        }
        return $retval;
    }

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
        $dst = $this->_convert($source, $size);
        // サムネイルを作成
        ob_start();
        if ($this->_png) {
            $result = imagepng($dst);
        } else {
            $result = imagejpeg($dst, '', $this->_quality);
        }
        $retval = ob_get_clean();
        imagedestroy($dst);
        if (!$result) {
            unset($retval);
            $retval = &PEAR::raiseError("Failed to create a thumbnail. ({$thumbnail})");
        }
        return $retval;
    }

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
        $dst = $this->_convert($source, $size);
        // サムネイルを出力
        $this->_httpHeader($name);
        if ($this->_png) {
            $result = imagepng($dst);
        } else {
            $result = imagejpeg($dst, '', $this->_quality);
        }
        imagedestroy($dst);
        if (!$result) {
            $retval = &PEAR::raiseError("Failed to create a thumbnail. ({$name})");
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
     * @return resource gd
     */
    function _convert($source, $size)
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
            $error = &PEAR::raiseError("Failed to load the image. ({$source})");
            return $error;
        }
        // サムネイルのイメージストリームを作成
        $dst = imagecreatetruecolor($tw, $th);
        if (!is_null($this->_bgcolor)) {
            $bg = imagecolorallocate($dst, $this->_bgcolor[0], $this->_bgcolor[1], $this->_bgcolor[2]);
            imagefill($dst, 0, 0, $bg);
        }
        // ソースをサムネイルにコピー
        if ($this->_resampling) {
            imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $tw, $th, $sw, $sh);
        } else {
            imagecopy($dst, $src, 0, 0, $sx, $sy, $sw, $sh);
        }
        imagedestroy($src);
        // 回転
        if ($this->_rotation) {
            $degrees = ($this->_rotation == 90) ? -90 : (($this->_rotation == 270) ? 90: $this->_rotation);
            $tmp = imagerotate($dst, $degrees, $bg);
            imagedestroy($dst);
            return $tmp;
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
