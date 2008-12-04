<?php
// p2 ースタイル設定
// for edit_conf_user.php

?>
body,td{
	font-size: <?php echo $STYLE['fontsize'] ?>;
}

p#pan_menu{
	border-bottom: solid 1px #ccc;
}

table#edit_conf_user{
	border: solid 1px #ccc;
}

table#edit_conf_user td{
	padding: 4px;
	margin: 4px;
	border: solid 0px;
	border-right: solid 1px #efefef;
	border-bottom: solid 1px #efefef;
}

tr.group{
	background-color: #efefef;
}
<?php

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
