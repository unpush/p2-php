/*
 *ImageCache2::Manager
 */

// {{{ dropZeroOptions()

function dropZeroOptions(onoff)
{
	var limitDate = document.getElementById("dropZeroLimit");
	var selectTime = document.getElementById("dropZeroSelectTime");
	var selectType = document.getElementById("dropZeroSelectType");
	var toBlackList = document.getElementById("dropZeroToBlackList");
	if (onoff == true) {
		limitDate.disabled = false;
		selectTime.disabled = false;
		selectType.disabled = false;
		toBlackList.disabled = false;
	} else {
		limitDate.disabled = true;
		selectTime.disabled = true;
		selectType.disabled = true;
		toBlackList.disabled = true;
	}
}

// }}}
// {{{ isRadioSelected()

function isRadioSelected()
{
	var radios = document.getElementsByName("action");
	for (var i = 0; i < radios.length; i++) {
		if (radios[i].checked) {
			return confirm("本当によろしいですか？");
		}
	}
	alert("動作が選ばれていません。");
	return false;
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
