/**
 * ImagCache2::ON/OFF
 */

// {{{ ic2_switch()

/**
 * ImageCache2の有効/無効を切り替える
 *
 * @param Boolean onoff
 * @param Boolean mobile
 * @return Number
 */
function ic2_switch(onoff, mobile)
{
	var objHTTP = getXmlHttp();
	if (!objHTTP) {
		alert('Error: XMLHTTP 通信オブジェクトの作成に失敗しました。');
	}

	var url = 'httpcmd.php?cmd=ic2&switch=' + (onoff ? '1' : '0') + '&mobile=' + (mobile ? '1' : '0');
	var res = getResponseTextHttp(objHTTP, url, 'nc');

	if (res) {
		return parseInt(res);
	} else {
		return 0;
	}
}

// }}}
// {{{ ic2_menu_switch()

/**
 * ImageCache2の有効/無効を切り替える
 * PC用サイドメニューから呼び出される
 *
 * @param Boolean onoff
 * @return Boolean(false)
 */
function ic2_menu_switch(onoff)
{
	var btn_on = document.getElementById('ic2_switch_on');
	var btn_off = document.getElementById('ic2_switch_off');

	switch (ic2_switch(onoff, 0)) {
	  case 1:
		btn_on.style.display = 'inline';
		btn_off.style.display = 'none';
		break;
	  case 2:
		btn_on.style.display = 'none';
		btn_off.style.display = 'inline';
		break;
	}

	return false;
}

// }}}
// {{{ ic2_iphone_switch()

/**
 * ImageCache2の有効/無効を切り替える (iPhone用)
 *
 * @param Boolean onoff
 * @return void
 */
function ic2_mobile_switch(onoff)
{
	ic2_switch(onoff, 1);
}

// }}}

/*
 * Local Variables:
 * mode: javascript
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: t
 * End:
 */
/* vim: set syn=javascript fenc=cp932 ai noet ts=4 sw=4 sts=4 fdm=marker: */
