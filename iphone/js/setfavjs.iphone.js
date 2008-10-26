// --------------------------------------------------------------
// "お気に" の文字列無しの ＋か★のみ版
//-------------------------------------------------------------------
function setFavJsNoStr(tquery, favdo, info_pop_width, info_pop_height, page, obj,numb)
{
	// read.phpでは、ページの読み込みが完了していなければ、なにもしない
	// （read.php は読み込み完了時にidx記録が生成されるため）
	if ((page == 'subject') && !gIsPageLoaded) {
		return false;
	}
	
	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		// alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
		// XMLHTTP（とinnerHTML） に未対応なら小窓で
		infourl = 'info_i.php?' + tquery + '&setfav=' + favdo + '&popup=2';
		return !openSubWin(infourl,info_pop_width,info_pop_height,0,0);
	}

	url = 'httpcmd.php?' + tquery + '&setfav=' + favdo + '&cmd=setfav'; // スクリプトと、コマンド指定

	var res = getResponseTextHttp(objHTTP, url, 'nc');
	var rmsg = "";
	if (res) {
		if (res == '1') {
			rmsg = '完了';
		}
		if (rmsg) {
			if (favdo == '1') {
				nextset = '0';
				favmark = '<img src="iui/icon_del.png">';
				favtitle = 'お気にスレから外す';
			} else {
				nextset = '1';
				favmark = '<img src="iui/icon_add.png">';
				favtitle = 'お気にスレに追加';
			}
			if (obj.className) {
				objClass = ' class="' + obj.className + '"';
			} else {
				objClass = '';
			}
			if (page != 'subject') {
				favstr = '';
			} else {
				favstr = '';
			}
			var favhtm = '<a id="'+numb+'"' + objClass + ' href="info_i.php?' + tquery + '&amp;setfav=' + nextset + '" target="info" onClick="return setFavJsNoStr(\'' + tquery + '\', \''+nextset+'\', '+info_pop_width+', '+info_pop_height+', \'' + page + '\', this, \''+numb+ '\');" title="' + favtitle + '">' + favstr + favmark + '</a>';
			if (page != 'read') {
				obj.parentNode.innerHTML = favhtm;
			} else {
				var span = document.getElementsByTagName('span');
				for (var i = 0; i < span.length; i++) {
					if (span[i].className == 'plus' && span[i].id == numb) {
						span[i].innerHTML = favhtm;
					}
				}
			}
		}
	}
	return false;
}
