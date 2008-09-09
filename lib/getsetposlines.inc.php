<?php
/**
 * rep2 - ポジションを考慮しながら、ラインデータを追加して、結果を取得する関数
 */

// {{{ getSetPosLines()

/**
 * ポジションを考慮しながら、ラインデータを追加して、結果を取得する
 *
 * @param array     $lines            あらかめじめ重複要素を削除したライン配列
 * @param string    $data             新規ラインデータ
 * @param integer   $before_line_num  移動前の行番号（先頭は0）
 * @param mixed     $set              0(解除), 1(追加), top, up, down, bottom
 * @return array
 */
function getSetPosLines($lines, $data, $before_line_num, $set)
{
    if ($set == 1 or $set == 'top') {
        $after_line_num = 0; // 移動後の行番号

    } elseif ($set == 'up') {
        $after_line_num = $before_line_num - 1;
        if ($after_line_num < 0) {
            $after_line_num = 0;
        }

    } elseif ($set == 'down') {
        $after_line_num = $before_line_num + 1;
        if ($after_line_num >= sizeof($lines)) {
            $after_line_num = 'bottom';
        }

    } elseif ($set == 'bottom') {
        $after_line_num = 'bottom';

    } else {
        return $lines;
    }

    //================================================
    // セットする
    //================================================
    $reclines = array();
    if (!empty($lines)) {
        $i = 0;
        foreach ($lines as $l) {
            if ($i === $after_line_num) {
                $reclines[] = $data;
            }
            $reclines[] = $l;
            $i++;
        }
        if ($after_line_num === 'bottom') {
            $reclines[] = $data;
        }
        //「$after_line_num == "bottom"」だと誤動作する。
    } else {
        $reclines[] = $data;
    }

    return $reclines;
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
