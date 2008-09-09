/* p2 - 並べ替えJavaScriptファイル */

function makeOptionList(form_idx)
{
	if (form_idx == null) { fidx = 0; }
	val = "";
	for (j=0; j<document.form[fidx].length; j++) {
		if (val > "") { val += ","; }
		if (document.form[fidx].options[j].value > "") {
			val += document.form[fidx].options[j].value;
		}
	}
	return val;
}

function submitApply(form_idx)
{
	if (form_idx == null) { fidx = 0; }
	document.form['list'].value = makeOptionList(fidx);
	document.form.submit();
}

function order(action, form_idx)
{
	if (form_idx == null) { fidx = 0; }
	sel = document.form[fidx].selectedIndex;
	if (sel != -1 && document.form[fidx].options[sel].value > "") {
		selText = document.form[fidx].options[sel].text;
		selValue = document.form[fidx].options[sel].value;
		if (action == "delete") {
			if (confirm("選択されているアイテムが削除されます。")) {
				document.form[fidx].options[sel]=null;
			}
		} else if (sel != document.form[fidx].length-1 && action == "bottom") {
			for(i=sel; i<document.form[fidx].length-1; i++){
				document.form[fidx].options[i].text = document.form[fidx].options[i+1].text;
				document.form[fidx].options[i].value = document.form[fidx].options[i+1].value;
			}
			document.form[fidx].options[document.form[fidx].length-1].text = selText;
			document.form[fidx].options[document.form[fidx].length-1].value = selValue;
			document.form[fidx].selectedIndex = document.form[fidx].length-1;
		} else if (sel != 0 && action == "top") {
			for(i=sel; 0<i; i--){
				document.form[fidx].options[i].text = document.form[fidx].options[i-1].text;
				document.form[fidx].options[i].value = document.form[fidx].options[i-1].value;
			}
			document.form[fidx].options[0].text = selText;
			document.form[fidx].options[0].value = selValue;
			document.form[fidx].selectedIndex = 0;
		} else if (sel > 0 && action == "up") {
			document.form[fidx].options[sel].text = document.form[fidx].options[sel-1].text;
			document.form[fidx].options[sel].value = document.form[fidx].options[sel-1].value;
			document.form[fidx].options[sel-1].text = selText;
			document.form[fidx].options[sel-1].value = selValue;
			document.form[fidx].selectedIndex--;
		} else if (sel < document.form[fidx].length-1 && action == "down") {
			document.form[fidx].options[sel].text = document.form[fidx].options[sel+1].text;
			document.form[fidx].options[sel].value = document.form[fidx].options[sel+1].value;
			document.form[fidx].options[sel+1].text = selText;
			document.form[fidx].options[sel+1].value = selValue;
			document.form[fidx].selectedIndex++;
		}
	} else {
		alert("アイテムを選択してください。");
	}
}
