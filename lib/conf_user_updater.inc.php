<?php
/**
 * rep2expack - ユーザ設定移行支援
 */

// {{{ conf_user_update_080908()

/**
 * 080908で携帯用の設定キーを変更したので、旧設定から移行する
 *
 * @param array $old 旧設定
 * @return array 新しいキーに書き換えられた設定
 */
function conf_user_update_080908(array $old)
{
    $map = array(
        'k_sb_show_first'       => 'mobile.sb_show_first',
        'k_sb_disp_range'       => 'mobile.sb_disp_range',
        'sb_ttitle_max_len_k'   => 'mobile.sb_ttitle_max_len',
        'sb_ttitle_trim_len_k'  => 'mobile.sb_ttitle_trim_len',
        'sb_ttitle_trim_pos_k'  => 'mobile.sb_ttitle_trim_pos',
        'k_rnum_range'          => 'mobile.rnum_range',
        'ktai_res_size'         => 'mobile.res_size',
        'ktai_ryaku_size'       => 'mobile.ryaku_size',
        'k_aa_ryaku_size'       => 'mobile.aa_ryaku_size',
        'before_respointer_k'   => 'mobile.before_respointer',
        'k_use_tsukin'          => 'mobile.use_tsukin',
        'k_use_picto'           => 'mobile.use_picto',
        'k_bbs_noname_name'     => 'mobile.bbs_noname_name',
        'k_clip_unique_id'      => 'mobile.clip_unique_id',
        'k_date_zerosuppress'   => 'mobile.date_zerosuppress',
        'k_clip_time_sec'       => 'mobile.clip_time_sec',
        'k_copy_divide_len'     => 'mobile.copy_divide_len',
        'k_save_packet'         => 'mobile.save_packet',
        'mobile.id_underline'   => 'mobile.underline_id',
    );

    $new = array();

    foreach ($old as $key => $value) {
        if (array_key_exists($key, $map)) {
            $new[$map[$key]] = $value;
        } else {
            $new[$key] = $value;
        }
    }

    $new['mobile.sb_show_first'] = 2;

    return $new;
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
