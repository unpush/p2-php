////
// 削除関数
// deleLog('host={$aThread->host}&bbs={$aThread->bbs}&key={$aThread->key}{$ttitle_en_q}{$sid_q}', {$STYLE['info_pop_size']}, 'read', this);
//
function deleLog(tquery, info_pop_width, info_pop_height, page, obj)
{
	// read.phpでは、ページの読み込みが完了していなければ、なにもしない
	// （read.php は読み込み完了時にidx記録が生成されるため）
	if ((page == 'read') && !gIsPageLoaded) {
		return true;
	}

	var xmlHttpObj = getXmlHttp();

	if (!xmlHttpObj) {
		// alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
		// XMLHTTP（と obj.parentNode.innerHTML） に未対応なら小窓で
		infourl = 'info.php?' + tquery + '&popup=2&dele=true';
		return openSubWin(infourl,info_pop_width,info_pop_height,0,0);
	}

	var url = 'httpcmd.php?' + tquery + '&cmd=delelog'; // スクリプトと、コマンド指定
	
	var func = function(xobj){
		var rmsg = '';
		var res = xmlHttpObj.responseText.replace(/^<\?xml .+?\?>\n?/, '');;
		if (res == '1') {
			rmsg = (page == 'subject') ? '削' : '完了';
		} else if (res == '2') {
			rmsg = (page == 'subject') ? '無' : '完了';
		}
		if (rmsg) {
			// Gray() は IE ActiveX用
			if (page == 'read_new') {
				obj.parentNode.parentNode.parentNode.parentNode.parentNode.parentNode.style.filter = 'Gray()';
			} else if (page == 'read') {
				document.body.style.filter = 'Gray()';
			}
			obj.parentNode.innerHTML = rmsg;
		}
	};

	obj.style.color = 'gray';
	getResponseTextHttp(xmlHttpObj, url, 'nc', true, func);

	return true;
}
