<?php
/**
 * rep2expack - 携帯から SPM 相当の機能を利用するための関数
 */

/**
 * レス番号を指定して 移動・コピー(+引用)・AAS するフォームを生成
 *
 * @return string
 */
function kspform(&$aThread, $default = '', $params = null)
{
    global $_conf;

    // 入力を4桁の数字に限定するための属性
    //$numonly_at = 'maxlength="4" istyle="4" format="*N" mode="numeric"';
    $numonly_at = 'maxlength="4" istyle="4" format="4N" mode="numeric"';

    // 選択可能なオプション
    $options = array();
    $options['goto'] = 'GO';
    $options['copy'] = 'ｺﾋﾟｰ';
    $options['copy_quote'] = '&gt;ｺﾋﾟｰ';
    $options['res_quote']  = '&gt;ﾚｽ';
    if ($_conf['expack.aas.enabled']) {
        $options['aas']        = 'AAS';
        $options['aas_rotate'] = 'AAS*';
    }
    $options['aborn_res']  = 'ｱﾎﾞﾝ:ﾚｽ';
    $options['aborn_name'] = 'ｱﾎﾞﾝ:名前';
    $options['aborn_mail'] = 'ｱﾎﾞﾝ:ﾒｰﾙ';
    $options['aborn_id']   = 'ｱﾎﾞﾝ:ID';
    $options['aborn_msg']  = 'ｱﾎﾞﾝ:ﾒｯｾｰｼﾞ';
    $options['ng_name'] = 'NG:名前';
    $options['ng_mail'] = 'NG:ﾒｰﾙ';
    $options['ng_id']   = 'NG:ID';
    $options['ng_msg']  = 'NG:ﾒｯｾｰｼﾞ';

    // フォーム生成
    $form = "<form method=\"get\" action=\"spm_k.php\">";
    $form .= $_conf['k_input_ht'];

    // 隠しパラメータ
    $hidden = '<input type="hidden" name="%s" value="%s">';
    $form .= sprintf($hidden, 'host', htmlspecialchars($aThread->host, ENT_QUOTES));
    $form .= sprintf($hidden, 'bbs', htmlspecialchars($aThread->bbs, ENT_QUOTES));
    $form .= sprintf($hidden, 'key', htmlspecialchars($aThread->key, ENT_QUOTES));
    $form .= sprintf($hidden, 'offline', '1');

    // 追加の隠しパラメータ
    if (is_array($params)) {
        foreach ($params as $param_name => $param_value) {
            $form .= sprintf($hidden, $param_name, htmlspecialchars($param_value, ENT_QUOTES));
        }
    }

    // オプションを選択するメニュー
    $form .= '<select name="ktool_name">';
    foreach ($options as $opt_name => $opt_title) {
        $form .= "<option value=\"{$opt_name}\">{$opt_title}</option>";
    }
    $form .= '</select>';

    // 数値入力フォームと実行ボタン
    $form .= "<input type=\"text\" size=\"3\" name=\"ktool_value\" value=\"{$default}\" {$numonly_at}>";
    $form .= '<input type="submit" value="OK" title="OK">';

    $form .= '</form>';

    return $form;
}

/**
 * スレッドを指定する
 */
function kspDetectThread()
{
    global $_conf, $host, $bbs, $key, $ls;

    // スレURLの直接指定
    if (($nama_url = $_GET['nama_url']) || ($nama_url = $_GET['url'])) {

            // 2ch or pink - http://choco.2ch.net/test/read.cgi/event/1027770702/
            if (preg_match("/http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))\/test\/read\.cgi\/([^\/]+)\/([0-9]+)(\/)?([^\/]+)?/", $nama_url, $matches)) {
                $host = $matches[1];
                $bbs = $matches[3];
                $key = $matches[4];
                $ls = $matches[6];

            // 2ch or pink 過去ログhtml - http://pc.2ch.net/mac/kako/1015/10153/1015358199.html
            } elseif ( preg_match("/(http:\/\/([^\/]+\.(2ch\.net|bbspink\.com))(\/[^\/]+)?\/([^\/]+)\/kako\/\d+(\/\d+)?\/(\d+)).html/", $nama_url, $matches) ){ //2ch pink 過去ログhtml
                $host = $matches[2];
                $bbs = $matches[5];
                $key = $matches[7];
                $kakolog_uri = $matches[1];
                $_GET['kakolog'] = urlencode($kakolog_uri);

            // まち＆したらばJBBS - http://kanto.machibbs.com/bbs/read.pl?BBS=kana&KEY=1034515019
            } elseif ( preg_match("/http:\/\/([^\/]+\.machibbs\.com|[^\/]+\.machi\.to)\/bbs\/read\.(pl|cgi)\?BBS=([^&]+)&KEY=([0-9]+)(&START=([0-9]+))?(&END=([0-9]+))?[^\"]*/", $nama_url, $matches) ){
                $host = $matches[1];
                $bbs = $matches[3];
                $key = $matches[4];
                $ls = $matches[6] ."-". $matches[8];
            } elseif (preg_match("{http://((jbbs\.livedoor\.jp|jbbs\.livedoor.com|jbbs\.shitaraba\.com)(/[^/]+)?)/bbs/read\.(pl|cgi)\?BBS=([^&]+)&KEY=([0-9]+)(&START=([0-9]+))?(&END=([0-9]+))?[^\"]*}", $nama_url, $matches)) {
                $host = $matches[1];
                $bbs = $matches[5];
                $key = $matches[6];
                $ls = $matches[8] ."-". $matches[10];

            // したらばJBBS http://jbbs.livedoor.com/bbs/read.cgi/computer/2999/1081177036/-100
            }elseif( preg_match("{http://(jbbs\.livedoor\.jp|jbbs\.livedoor.com|jbbs\.shitaraba\.com)/bbs/read\.cgi/(\w+)/(\d+)/(\d+)/((\d+)?-(\d+)?)?[^\"]*}", $nama_url, $matches) ){
                $host = $matches[1] ."/". $matches[2];
                $bbs = $matches[3];
                $key = $matches[4];
                $ls = $matches[5];
            }

    } else {
        if ($_GET['host']) { $host = $_GET['host']; } // "pc.2ch.net"
        if ($_POST['host']) { $host = $_POST['host']; }
        if ($_GET['bbs']) { $bbs = $_GET['bbs']; } // "php"
        if ($_POST['bbs']) { $bbs = $_POST['bbs']; }
        if ($_GET['key']) { $key = $_GET['key']; } // "1022999539"
        if ($_POST['key']) { $key = $_POST['key']; }
        if ($_GET['ls']) {$ls = $_GET['ls']; } // "all"
        if ($_POST['ls']) { $ls = $_POST['ls']; }
    }

    if (!($host && $bbs && $key)) {
        $htm['nama_url'] = htmlspecialchars($nama_url, ENT_QUOTES);
        $msg = "p2 - {$_conf['read_php']}: スレッドの指定が変です。<br>"
            . "<a href=\"{$htm['nama_url']}\">" . $htm['nama_url'] . "</a>";
        die($msg);
    }
}

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
