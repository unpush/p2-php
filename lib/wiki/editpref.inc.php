<?php

// +Wiki
// {{{ PC - +Wiki
echo "<td>\n\n";
echo <<<EOP
<fieldset>
<legend>+Wiki</legend>
    <a href="edit_link_plugin.php{$_conf['k_at_q']}">リンクプラグイン編集</a> ｜ 
    <a href="edit_dat_plugin.php{$_conf['k_at_q']}">DAT取得プラグイン編集</a> ｜ 
    <a href="edit_replace_imageurl.php{$_conf['k_at_q']}">置換画像URLプラグイン編集</a>
</fieldset>\n
EOP;
echo "</td></tr>\n\n";

// }}}