function setFocus(ID){
	document.getElementById(ID).focus();
}

function mailSage(obj){
	if (mailran = document.getElementById('mail')) {
		if (obj.checked==true) {
			mailran.value="sage";
		} else {
			if(mailran.value=="sage"){
				mailran.value="";
			}
		}
	}
}

function checkSage(obj){
	if (cbsage = document.getElementById('sage')) {
		if (obj.value == "sage") {
			cbsage.checked=true;
		} else {
			cbsage.checked=false;
		}
	}
}
