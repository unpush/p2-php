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
