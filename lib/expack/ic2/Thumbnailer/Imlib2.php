<?php
/**
 * Thumbnailer_Imlib2
 * PHP Version 5
 */

require_once dirname(__FILE__) . '/Common.php';

// {{{ Thumbnailer_Imlib2

/**
 * Image manipulation class which uses imlib2 php extension.
 *
 * @deprecated
 */
class Thumbnailer_Imlib2 extends Thumbnailer_Common
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
        $err = 0;
        // サムネイルを保存
        if ($this->isPng()) {
            imlib2_image_set_format($dst, 'png');
            $result = imlib2_save_image($dst, $thumbnail, $err);
        } else {
            imlib2_image_set_format($dst, 'jpeg');
            $result = imlib2_save_image($dst, $thumbnail, $err, $this->getQuality());
        }
        imlib2_free_image($dst);
        if (!$result) {
            $retval = PEAR::raiseError("Failed to create a thumbnail. ({$thumbnail}:{$err})");
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
     * imlib2_dump_image() の出力をキャプチャしようとするとうまくいかないので
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
        $err = 0;
        // サムネイルを作成
        $tempfile = $this->_tempnam();
        if ($this->isPng()) {
            imlib2_image_set_format($dst, 'png');
            $result = imlib2_save_image($dst, $tempfile, $err);
        } else {
            imlib2_image_set_format($dst, 'jpeg');
            $result = imlib2_save_image($dst, $tempfile, $err, $this->getQuality());
        }
        imlib2_free_image($dst);
        if (!$result) {
            $retval = PEAR::raiseError("Failed to create a thumbnail. ({$thumbnail}:{$err})");
        } else {
            $retval = file_get_contents($tempfile);
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
        $dst = $this->_convert($source, $size);
        $err = 0;
        // サムネイルを出力
        $this->_httpHeader($name);
        if ($this->isPng()) {
            imlib2_image_set_format($dst, 'png');
            $result = imlib2_dump_image($dst, $err);
        } else {
            imlib2_image_set_format($dst, 'jpeg');
            $result = imlib2_dump_image($dst, $err, $this->getQuality());
        }
        imlib2_free_image($dst);
        if (!$result) {
            $retval = PEAR::raiseError("Failed to create a thumbnail. ({$name}:{$err})");
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
     * @return resource Unknown (imlib2?)
     */
    protected function _convert($source, $size)
    {
        extract($size);
        $err = 0;
        // ソースのイメージストリームを取得
        $src = imlib2_load_image($source, $err);
        if ($err) {
            $error = PEAR::raiseError("Failed to load the image. ({$source}:{$err})");
            return $error;
        }
        // サムネイルのイメージストリームを作成
        $dst = imlib2_create_image($tw, $th);
        $bgcolor = $this->getBgColor();
        imlib2_image_fill_rectangle($dst, 0, 0, $tw, $th, $bgcolor[0], $bgcolor[1], $bgcolor[2], $bgcolor[3]);
        // ソースをサムネイルにコピー
        /* imlib_blend_image_onto_image(int dstimg, int srcimg, int malpha, int srcx, int srcy, int srcw, int srch,
            int dstx, int dsty, int dstw, int dsth, char dither, char blend, char alias) */
        imlib2_blend_image_onto_image($dst, $src, 255, $sx, $sy, $sw, $sh, 0, 0, $tw, $th, false, true, $this->doesResampling());
        imlib2_free_image($src);
        // 回転
        if ($degrees = $this->getRotation()) {
            imlib2_image_orientate($dst, $degrees / 90);
        }
        return $dst;
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
