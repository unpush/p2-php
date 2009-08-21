<?php

class GifAnimationDetector {
    public static function isAnimated($filename) {
        if (!$fp = @fopen($filename, 'rb')) return false;
        $ret = self::_isAnimGifInternal($fp);
        fclose($fp);
        return $ret;
    }
    protected static function _isAnimGifInternal($fp) {
        // parse header
        if (false === ($header = @fread($fp, 13))) return false;
        $ver = substr($header, 3, 3);
//      87a‚Å‚àM—p‚Å‚«‚È‚¢‚±‚Æ‚ª‚ ‚Á‚½‚Ì‚Å
//        if ($ver != '89a') return false;

        $global_color_table_size = 1 << ((hexdec(bin2hex(substr($header, 10, 1))) & 0x07) + 1);
        $global_color_table_flag = (hexdec(bin2hex(substr($header, 10, 1))) >> 7) & 0x01;
        if ($global_color_table_flag && $global_color_table_size > 0) {
            // skip global color table
            if (@fseek($fp, $global_color_table_size * 3, SEEK_CUR) != 0) return false;
        }

        // parse blocks
        $cnt = 0;
        $before = '';
        while (!feof($fp)) {
            if (false === ($block_head = @fread($fp, 1))) return false;
            if (feof($fp)) return false;
            switch (bin2hex($block_head)) {
            case '2c':  // image block
                if ($before == 'f9' || $before == 'ff') {
                    $cnt++; // found graphic control block(f9) or application block(ff) + image block(2c)
                }
                if ($cnt > 1) return true;
                $term = self::_seekImageBlock($fp);
                $before = '2c';
                break;
            case '21':  // extension block
                if (false === ($label = @fread($fp, 1))) return false;
                if (feof($fp)) return false;
                $before = bin2hex($label);
                $term = self::_seekSubBlocks($fp);
                break;
            case '3b':  // trailer
                return false;
                break;
            default:    // unknown
                return false;
            }
            if ($term === false) return false;
            if (bin2hex($term) !== '00') return false;
        }
        return false;
    }
    protected static function _seekImageBlock($fp) {
        if (false === ($header = @fread($fp, 9))) return false;
        if (feof($fp)) return false;
        $local_color_table_size = 1 << ((hexdec(bin2hex(substr($header, 8, 1))) & 0x07) + 1);
        $local_color_table_flag = (hexdec(bin2hex(substr($header, 8, 1))) >> 7) & 0x01;
        if ($local_color_table_flag && $local_color_table_size > 0) {
            // skip local color table
            if (@fseek($fp, $local_color_table_size * 3, SEEK_CUR) != 0) return false;
        }
        if (false === (@fread($fp, 1))) return false; // LZW Minimum Code Size
        if (feof($fp)) return false;
        return self::_seekSubBlocks($fp);
    }
    protected static function _seekSubBlocks($fp) {
        while (false !== ($block_size = @fread($fp, 1))) {
            if (feof($fp)) return false;
            if (bin2hex($block_size) === '00') {
                return $block_size;
            }
            // skip sub block
            if (@fseek($fp, hexdec(bin2hex($block_size)), SEEK_CUR) != 0) return false;
        }
        return false;
    }
}
