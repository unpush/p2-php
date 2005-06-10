function setSerialAvailable(onoff)
{
	var from = document.getElementById("s_from");
	var to   = document.getElementById("s_to");
	var pad  = document.getElementById("s_pad");
	if (onoff == true) {
		from.disabled = false;
		to.disabled   = false;
		pad.disabled  = false;
		if (from.value == 'from') {
			from.value = '';
		}
		if (to.value == 'to') {
			to.value = '';
		}
		from.focus();
	} else {
		from.disabled = true;
		to.disabled   = true;
		pad.disabled  = true;
	}
}
