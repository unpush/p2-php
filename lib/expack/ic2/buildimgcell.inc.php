<?php
/**
 * ImageCache2 - 一覧表示用ユーティリティ関数
 */

// {{{ ic2_image_extra_info()

function ic2_image_extra_info($img)
{
    global $_conf, $ini, $icdb, $thumb;

    // 長すぎるURIは折り返す
    if (strlen($img['uri']) > 45) {
        $w = explode("\n", wordwrap($img['uri'], 45, "\n", 1));
        $w = array_map('htmlspecialchars', $w);
        $add['uri_w'] = implode('<br />', $w);
    } else {
        $add['uri_w'] = $img['uri'];
    }

    if ($img['mime'] == 'clamscan/infected') {

        // ウィルスに感染していたファイルのとき
        $add['src'] = './img/x04.png';
        $add['thumb'] = './img/x04.png';
        $add['t_width'] = 32;
        $add['t_height'] = 32;

    } else {

        // ソースとサムネイルのパスを取得
        $add['src'] = $thumb->srcPath($icdb->size, $icdb->md5, $icdb->mime);
        $add['thumb'] = $thumb->thumbPath($icdb->size, $icdb->md5, $icdb->mime);

        // サムネイルの縦横の大きさを計算
        $m = explode('x', $thumb->calc($icdb->width, $icdb->height));
        $add['t_width'] = (int)$m[0];
        $add['t_height'] = (int)$m[1];

    }

    // ソースのファイルサイズの書式を整える
    if ($img['size'] > 1024 * 1024) {
        $add['size_f'] = number_format($img['size'] / (1024 * 1024), 1) . 'MB';
    } elseif ($img['size'] > 1024) {
        $add['size_f'] = number_format($img['size'] / 1024, 1) . 'KB';
    } else {
        $add['size_f'] = $img['size'] . 'B';
    }

    // 日付の書式を整える
    $add['date'] = date('Y-m-d (D) H:i:s', $img['time']);

    return $add;
}

// }}}
// {{{ ic2_read_exif()

function ic2_read_exif($path)
{
    $exif = @exif_read_data($path, '', true, false);
    if ($exif) {
        // バイナリで、しかもデータサイズが大きい要素を削除
        if (isset($exif['MakerNote'])) {
            unset($exif['MakerNote']);
        }
        if (isset($exif['EXIF']) && isset($exif['EXIF']['MakerNote'])) {
            unset($exif['EXIF']['MakerNote']);
        }
        return $exif;
    } else {
        return null;
    }
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
