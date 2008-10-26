////
// お気にセット関数
// setFavJs('host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$sid_q}', '{$favdo}',{$STYLE['info_pop_size']}, this);
//
function setFavJs(tquery, favdo, info_pop_width, info_pop_height, page, obj)
{
	// read.phpでは、ページの読み込みが完了していなければ、なにもしない
	// （read.php は読み込み完了時にidx記録が生成されるため）
	if ((page == 'read') && !gIsPageLoaded) {
		return false;
	}
	
	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		// alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
		// XMLHTTP（とinnerHTML） に未対応なら小窓で
		infourl = 'info.php?' + tquery + '&setfav=' + favdo + '&popup=2';
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
				favmark = '★';
				favtitle = 'お気にスレから外す';
			} else {
				nextset = '1';
				favmark = '+';
				favtitle = 'お気にスレに追加';
			}
			if (obj.className) {
				objClass = ' class="' + obj.className + '"';
			} else {
				objClass = '';
			}
			if (page != 'subject') {
				favstr = 'お気に';
			} else {
				favstr = '';
			}
			var favhtm = '<a' + objClass + ' href="info_i.php?' + tquery + '&amp;setfav=' + nextset + '" target="info" onClick="return setFavJs(\'' + tquery + '\', \''+nextset+'\', '+info_pop_width+', '+info_pop_height+', \'' + page + '\', this);" title="' + favtitle + '">' + favmark + '</a>';
			if (page != 'read') {
				obj.parentNode.innerHTML = favhtm;
			} else {
				var span = document.getElementsByTagName('span');
				for (var i = 0; i < span.length; i++) {
					if (span[i].className == 'favdo') {
						span[i].innerHTML = favhtm;
					}
				}
			}
		}
	}
	return false;
}
