// ■削除関数
// deleLog('host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$sid_q}', {$STYLE['info_pop_size']}, 'read', this);
//
function deleLog(tquery, info_pop_width, info_pop_height, page, obj)
{
	// read.phpでは、ページの読み込みが完了していなければ、なにもしない
	// （read.php は読み込み完了時にidx記録が生成されるため）
	if ((page == 'read') && !gIsPageLoaded) {
		return false;
	}
	
	var objHTTP = getXmlHttp();
	
	if (!objHTTP) {
		// alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
		// XMLHTTP（と obj.parentNode.innerHTML） に未対応なら小窓で
		infourl = 'info.php?' + tquery + '&popup=2&dele=true';
		return OpenSubWin(infourl,info_pop_width,info_pop_height,0,0);
	}

	url = 'httpcmd.php?' + tquery + '&cmd=delelog'; // スクリプトと、コマンド指定
	
	var res = getResponseTextHttp(objHTTP, url, 'nc');
	var rmsg = "";
	if (res) {
		if (res == '1') {
			if (page == 'subject') {
				rmsg = '削';
			} else {
				rmsg = '完了';
			}
		} else if (res == '2') {
			if (page == 'subject') {
				rmsg = '無';
			} else {
				rmsg = '完了';
			}
		}
		if (rmsg) {
			if (page == 'read_new') {
				obj.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.style.filter = 'Gray()'; // IE ActiveX用
			} else if (page == 'read') {
				document.body.style.filter = 'Gray()'; // IE ActiveX用
			}
			obj.parentNode.innerHTML = rmsg;
		}
	}
	return false;
}
