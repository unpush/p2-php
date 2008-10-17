
// 対象idのHTML要素を表示or非表示する
// @param  string  hiddenClassName  元々隠されている場合の対象クラス名
function showHide(id, hiddenClassName)
{
	var obj = document.getElementById(id);
	if (!obj) {
		return false;
	}

	if (obj.style.display == 'block') {
		obj.style.display = "none";
	} else if(obj.style.display == 'none') {
		obj.style.display = "block";
	} else {
		if (hiddenClassName && obj.className != hiddenClassName) {
			obj.style.display = "none";
		} else {
			obj.style.display = "block";
		}
	}
	return false;
}
