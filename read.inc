<?php
// p2 - read •â•

//=================================================
// ‚ ‚Ú[‚ñ&NGƒ[ƒhÝ’è‚ð“Ç‚Ýž‚ÞŠÖ”
//=================================================
function readNgAbornFile(){
	global $prefdir;
	global $aborn_words, $ng_words;

	$aborn_name_txt = $prefdir."/p2_aborn_name.txt";
	if(file_exists($aborn_name_txt)){
		$aborn_words['name'] = file($aborn_name_txt);
	}
	
	$aborn_mail_txt = $prefdir."/p2_aborn_mail.txt";
	if(file_exists($aborn_mail_txt)){
		$aborn_words['mail'] = file($aborn_mail_txt);
	}
	
	$aborn_msg_txt = $prefdir."/p2_aborn_msg.txt";
	if(file_exists($aborn_msg_txt)){
		$aborn_words['msg'] = file($aborn_msg_txt);
	}
	
	$aborn_id_txt = $prefdir."/p2_aborn_id.txt";
	if(file_exists($aborn_id_txt)){
		$aborn_words['id'] = file($aborn_id_txt);
	}
	
	$ng_name_txt = $prefdir."/p2_ng_name.txt";
	if(file_exists($ng_name_txt)){
		$ng_words['name'] = file($ng_name_txt);
	}
	
	$ng_mail_txt = $prefdir."/p2_ng_mail.txt";
	if(file_exists($ng_mail_txt)){
		$ng_words['mail'] = file($ng_mail_txt);
	}
	
	$ng_msg_txt = $prefdir."/p2_ng_msg.txt";
	if(file_exists($ng_msg_txt)){
		$ng_words['msg'] = file($ng_msg_txt);
	}
	
	$ng_id_txt = $prefdir."/p2_ng_id.txt";
	if(file_exists($ng_id_txt)){
		$ng_words['id'] = file($ng_id_txt);
	}
}

?>