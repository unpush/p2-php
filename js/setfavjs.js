// ■お気にセット関数
// setFavJs('host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$sid_q}', '{$favdo}',{$STYLE['info_pop_size']}, this);
//
function setFavJs(tquery, favdo, info_pop_width, info_pop_height, page, obj)
{
	// read.phpでは、ページの読み込みが完了していなければ、なにもしない
	// （read.php は読み込み完了時にidx記録が生成されるため）
	if ((page == 'read') && !gIsPageLoaded) {
		return false;
	}

	if (arguments.length > 6) {
		setnum = parseInt(arguments[6]).toString();
	} else {
		setnum = '-1';
	}

	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		// alert("Error: XMLHTTP 通信オブジェクトの作成に失敗しました。") ;
		// XMLHTTP（とinnerHTML） に未対応なら小窓で
		infourl = 'info.php?' + tquery + '&setfav=' + favdo + '&popup=2';
		if (setnum != '-1') {
			infourl += '&setnum=' + setnum;
		}
		return OpenSubWin(infourl,info_pop_width,info_pop_height,0,0);
	}

	url = 'httpcmd.php?' + tquery + '&setfav=' + favdo + '&cmd=setfav'; // スクリプトと、コマンド指定
	if (setnum != '-1') {
		url += '&setnum=' + setnum;
	}

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
				if (setnum == '-1') {
					favmark = '+';
				} else {
					favmark = setnum;
				}
				favtitle = 'お気にスレに追加';
			}
			if (obj.className) {
				objClass = ' class="' + obj.className + '"';
			} else {
				objClass = '';
			}
			if (page != 'subject' && setnum == '-1') {
				favstr = 'お気に';
			} else {
				favstr = '';
			}
			if (setnum != '-1') {
				var favhtm = '<a' + objClass + ' href="info.php?' + tquery + '&amp;setfav=' + nextset + '&amp;setnum=' + setnum + '" target="info" onClick="return setFavJs(\'' + tquery + '\', \''+nextset+'\', '+info_pop_width+', '+info_pop_height+', \'' + page + '\', this, \'' + setnum + '\');" title="' + favtitle + '">' + favstr + favmark + '</a>';
			} else {
				var favhtm = '<a' + objClass + ' href="info.php?' + tquery + '&amp;setfav=' + nextset + '" target="info" onClick="return setFavJs(\'' + tquery + '\', \''+nextset+'\', '+info_pop_width+', '+info_pop_height+', \'' + page + '\', this);" title="' + favtitle + '">' + favstr + favmark + '</a>';
			}
			if (page != 'read') {
				obj.parentNode.innerHTML = favhtm;
			} else {
				var span = document.getElementsByTagName('span');
				tgtcls = 'favdo'
				if (setnum != '-1') {
					tgtcls += ' set' + setnum;
				}
				for (var i = 0; i < span.length; i++) {
					if (span[i].className == tgtcls) {
						span[i].innerHTML = favhtm;
					}
				}
			}
		}
	}
	return false;
}
