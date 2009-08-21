<?php
/**
 * Thumbnailer_Imagick09
 * PHP Version 5
 */

require_once dirname(__FILE__) . '/Common.php';

// {{{ Thumbnailer_Imagick09

/**
 * Image manipulation class which uses imagick php extension version 0.9.13 or earlier.
 *
 * @deprecated
 */
class Thumbnailer_Imagick09 extends Thumbnailer_Common
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
        $dst = $this->_convert($source, $size);
        $dst = $this->decorate($source, $dst);  // デコレーション
        // サムネイルを保存
        if ($this->getQuality() > 0) {
            imagick_setcompressionquality($dst, $this->getQuality());
        }
        $prefix = (($this->isPng()) ? 'png' : 'jpeg') . ':';
        $result = imagick_writeimage($dst, $prefix.$thumbnail);
        if (!$result) {
            $reason = imagick_failedreason($dst);
            $detail = imagick_faileddescription($dst);
            $retval = PEAR::raiseError("Failed to create a thumbnail. ({$thumbnail}:{$reason}:{$detail})");
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
     * @param string $source
     * @param array $size
     * @return string
     * @throws PEAR_Error
     */
    public function capture($source, $size)
    {
        $dst = $this->_convert($source, $size);
        // サムネイルを作成
        if ($this->getQuality() > 0) {
            imagick_setcompressionquality($dst, $this->getQuality());
        }
        $prefix = (($this->isPng()) ? 'png' : 'jpeg') . ':';
        $tempfile = $this->_tempnam();
        $result = imagick_writeimage($dst, $prefix.$tempfile);
        if (!$result) {
            $reason = imagick_failedreason($dst);
            $detail = imagick_faileddescription($dst);
            $retval = PEAR::raiseError("Failed to create a thumbnail. ({$thumbnail}:{$reason}:{$detail})");
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
     * @param string $source
     * @param string $name
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    public function output($source, $name, $size)
    {
        $dst = $this->_convert($source, $size);
        // サムネイルを出力
        if ($this->getQuality()) {
            imagick_setcompressionquality($dst, $this->getQuality());
        }
        $prefix = (($this->isPng()) ? 'png' : 'jpeg') . ':';
        $tempfile = $this->_tempnam();
        $result = imagick_writeimage($dst, $prefix.$tempfile);
        if (!$result) {
            $reason = imagick_failedreason($dst);
            $detail = imagick_faileddescription($dst);
            $retval = PEAR::raiseError("Failed to create a thumbnail. ({$name}:{$reason}:{$detail})");
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
     * @param string $source
     * @param array $size
     * @return resource imagick handle
     */
    protected function _convert($source, $size)
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
            $error = PEAR::raiseError("Failed to load the image. ({$source}:{$reason}:{$detail})");
            return $error;
        }
        // サムネイルのイメージストリームを作成
        $bgcolor = $this->getBgColor();
        $bg = sprintf('rgb(%d,%d,%d)', $bgcolor[0], $bgcolor[1], $bgcolor[2]);
        $dst = imagick_getcanvas($bg, $tw, $th);
        // ソースをリサイズし、サムネイルにコピー
        if ($this->doesTrimming()) {
            imagick_crop($src, $sx, $sy, $sw, $sh);
        }
        if ($this->doesResampling()) {
            imagick_scale($src, $tw, $th, '!');
        }
        imagick_composite($dst, IMAGICK_COMPOSITE_OP_ATOP, $src, 0, 0);
        imagick_destroyhandle($src);
        // 回転
        if ($degrees = $this->getRotation()) {
            imagick_rotate($dst, $degrees);
        }
        return $dst;
    }

    // }}}
    // {{{ _decorateAnimationGif()

    /**
     * stamp animation gif mark.
     *
     * @param resource $thumb
     * @return resource
     */
    protected function _decorateAnimationGif($thumb)
    {
        $deco = imagick_readimage($this->getDecorateAnigifFilePath());
        if (!is_resource($deco) || imagick_iserror($deco)) {
            if (is_resource($deco)) {
                $reason = imagick_failedreason($deco);
                $detail = imagick_faileddescription($deco);
                imagick_destroyhandle($deco);
            }
            $error = PEAR::raiseError("Failed to load the image. (" . $this->getDecorateAnigifFilePath() . ":{$reason}:{$detail})");
            return $error;
        }
        imagick_scale($deco, imagick_getwidth($thumb), imagick_getheight($thumb), '!');
        imagick_composite($thumb, IMAGICK_COMPOSITE_OP_ATOP, $deco, 0, 0);
        imagick_destroyhandle($deco);
        return $thumb;
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
        $deco = imagick_readimage($this->getDecorateGifCautionFilePath());
        if (!is_resource($deco) || imagick_iserror($deco)) {
            if (is_resource($deco)) {
                $reason = imagick_failedreason($deco);
                $detail = imagick_faileddescription($deco);
                imagick_destroyhandle($deco);
            }
            $error = PEAR::raiseError("Failed to load the image. (" . $this->getDecorateGifCautionFilePath() . ":{$reason}:{$detail})");
            return $error;
        }
        imagick_composite($thumb, IMAGICK_COMPOSITE_OP_ATOP, $deco,
            (imagick_getwidth($thumb) - imagick_getwidth($deco))/2,
            (imagick_getheight($thumb) - imagick_getheight($deco))/2);
        imagick_destroyhandle($deco);
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
