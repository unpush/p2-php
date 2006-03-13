/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

// PHP の同名関数を模した JavaScript 関数

function nl2br(str)
{
	return str.replace(/\r\n|\r|\n/g, '<br />');
}

function htmlspecialchars(str)
{
	return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); //"
}

function strip_tags(str)
{
	return str.replace(/<\/?[^>]+>/gi, '');
}

/*
	This code is in the public domain. Feel free to link back to http://jan.moesen.nu/
*/
function sprintf()
{
	if (!arguments || arguments.length < 1 || !RegExp)
	{
		return;
	}
	var str = arguments[0];
	var re = /([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)(.*)/; //'
	var a = b = [], numSubstitutions = 0, numMatches = 0;
	while (a = re.exec(str))
	{
		var leftpart = a[1], pPad = a[2], pJustify = a[3], pMinLength = a[4];
		var pPrecision = a[5], pType = a[6], rightPart = a[7];

		//alert(a + '\n' + [a[0], leftpart, pPad, pJustify, pMinLength, pPrecision);

		numMatches++;
		if (pType == '%')
		{
			subst = '%';
		}
		else
		{
			numSubstitutions++;
			if (numSubstitutions >= arguments.length)
			{
				alert('Error! Not enough function arguments (' + (arguments.length - 1) + ', excluding the string)\nfor the number of substitution parameters in string (' + numSubstitutions + ' so far).');
			}
			var param = arguments[numSubstitutions];
			var pad = '';
			       if (pPad && pPad.substr(0,1) == "'") pad = leftpart.substr(1,1);
			  else if (pPad) pad = pPad;
			var justifyRight = true;
			       if (pJustify && pJustify === "-") justifyRight = false;
			var minLength = -1;
			       if (pMinLength) minLength = parseInt(pMinLength);
			var precision = -1;
			       if (pPrecision && pType == 'f') precision = parseInt(pPrecision.substring(1));
			var subst = param;
			       if (pType == 'b') subst = parseInt(param).toString(2);
			  else if (pType == 'c') subst = String.fromCharCode(parseInt(param));
			  else if (pType == 'd') subst = parseInt(param) ? parseInt(param) : 0;
			  else if (pType == 'u') subst = Math.abs(param);
			  else if (pType == 'f') subst = (precision > -1) ? Math.round(parseFloat(param) * Math.pow(10, precision)) / Math.pow(10, precision): parseFloat(param);
			  else if (pType == 'o') subst = parseInt(param).toString(8);
			  else if (pType == 's') subst = param;
			  else if (pType == 'x') subst = ('' + parseInt(param).toString(16)).toLowerCase();
			  else if (pType == 'X') subst = ('' + parseInt(param).toString(16)).toUpperCase();
		}
		str = leftpart + subst + rightPart;
	}
	return str;
}

function vsprintf(format, params)
{
	var args = [format];
	for (var i = 0; i < params.length; i++) {
		args.push(params[i]);
	}
	return sprintf.apply(this, args);
}

// String オブジェクトのプロトタイプを拡張

String.prototype.nl2br = function()
{
	return nl2br(this);
}

String.prototype.htmlspecialchars = function()
{
	return htmlspecialchars(this);
}

String.prototype.strip_tags = function()
{
	return strip_tags(this);
}

String.prototype.sprintf = function()
{
	var args = [this];
	for (var i = 0; i < arguments.length; i++) {
		args.push(arguments[i]);
	}
	return sprintf.apply(this, args);
}

String.prototype.vsprintf = function()
{
	return vsprintf.apply(this, [this, arguments[0]]);
}
