// ■スレッドあぼーんセット関数
// setTAbornJs('host={$aThread->host}{$bbs_q}{$key_q}{$ttitle_en_q}{$sid_q}', '{$tabdo}',{$STYLE['info_pop_size']}, 'read|subject|info', this);
//
function setTAbornJs(tquery, tabdo, info_pop_width, info_pop_height, page, obj)
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
		infourl = 'info.php?' + tquery + '&taborn=' + tabdo + '&popup=2';
		return OpenSubWin(infourl,info_pop_width,info_pop_height,0,0);
	}

	url = 'httpcmd.php?' + tquery + '&taborn=' + tabdo + '&cmd=taborn'; // スクリプトと、コマンド指定

	var res = getResponseTextHttp(objHTTP, url, 'nc');
	var rmsg = "";
	if (res) {
		if (res == '1') {
			rmsg = '完了';
		}
		if (rmsg) {
			var ta_num = document.getElementById('ta_num');
			var ta_int = (ta_num) ? parseInt(ta_num.innerHTML, 10) : 0;
			if (tabdo == '1') {
				ta_int++;
				nextset = '0';
				tabmark = '×';
				tabtitle = 'あぼーん解除';
			} else {
				ta_int--;
				nextset = '1';
				tabmark = '－';
				tabtitle = 'あぼーんする';
			}
			if (ta_num) {
				ta_num.innerHTML = ta_int.toString();
			}
			if (obj.className) {
				objClass = ' class="' + obj.className + '"';
			} else {
				objClass = '';
			}
			if (page != 'subject') {
				tabstr = 'あぼーん';
			} else {
				tabstr = '';
			}
			var tabhtm = '<a' + objClass + ' href="info.php?' + tquery + '&amp;taborn=' + nextset + '" target="info" onClick="return setTAbornJs(\'' + tquery + '\', \''+nextset+'\', '+info_pop_width+', '+info_pop_height+', \'' + page + '\', this);" title="' + tabtitle + '">' + tabstr + tabmark + '</a>';
			if (page != 'read') {
				var cBox = document.getElementById('tabcb_removenow');
				if (nextset == '0' && cBox && cBox.checked) {
					var tr = obj.parentNode;
					while (tr.tagName.toUpperCase() != 'TR') {
						tr = tr.parentNode;
					}
					/*var suicide = function() {
						tr.parentNode.removeChild(tr);
					}
					setTimeout(suicide, 300);*/
					tr.parentNode.removeChild(tr);
				} else {
					obj.parentNode.innerHTML = tabhtm;
				}
			} else {
				var span = document.getElementsByTagName('span');
				for (var i = 0; i < span.length; i++) {
					if (span[i].className == 'tabdo') {
						span[i].innerHTML = tabhtm;
					}
				}
			}
		}
	}
	return false;
}

//スレッドあぼーんのスイッチを表示する
function showTAborn(i, tabdo, info_pop_width, info_pop_height, page, obj)
{
	var th, th2, to, tx, closure, cBox, nObj;
	
	tx = '1em';
	
	if (th = document.getElementById('sb_th_no')) {
		th2 = document.createElement(th.tagName);
		th2.className = th.className;
		th2.style.width = tx;
		th2.appendChild(document.createTextNode('Ｘ'));
		if (th.nextSibling) {
			th.parentNode.insertBefore(th2, th.nextSibling);
		} else {
			th.parentNode.appendChild(th2);
		}
	}
	
	while (to = document.getElementById('to' + i.toString())) {
		closure = function() {
			var td, td2, tparam, tquery, cObj, pObj;
			
			tparam = '&taborn=' + tabdo;
			tquery = to.href.substring(to.href.indexOf('?') + 1, to.href.length) + tparam;
			
			cObj = document.createElement('a');
			cObj.href = to.href + tparam;
			if (to.target) {
				cObj.target = to.target;
			}
			if (to.className) {
				cObj.className = to.className;
			}
			cObj.onclick = function() {
				return setTAbornJs(tquery, tabdo, info_pop_width, info_pop_height, page, cObj);
			}
			
			if (tabdo == '1') {
				cObj.appendChild(document.createTextNode('－'));
			} else {
				cObj.appendChild(document.createTextNode('×'));
			}
			
			pObj = document.createElement('span');
			pObj.appendChild(cObj);
			
			td = to.parentNode;
			while (td.tagName.toUpperCase() != 'TD') {
				td = td.parentNode;
			}
			td2 = document.createElement('td');
			td2.className = td.className;
			td2.style.width = tx;
			td2.appendChild(pObj);
			if (td.nextSibling) {
				td.parentNode.insertBefore(td2, td.nextSibling);
			} else {
				td.parentNode.appendChild(td2);
			}
		}
		closure();
		i++;
	}
	
	cBox = document.createElement('input');
	cBox.id = 'tabcb_removenow';
	cBox.type = 'checkbox';
	cBox.checked = true;
	cBox.defaultChecked = true;
	
	nObj = document.createElement('label');
	nObj.appendChild(cBox);
	nObj.appendChild(document.createTextNode('あぼーんしたスレッドを一覧から消去'));
	obj.parentNode.replaceChild(nObj, obj);
	
	return false;
}
