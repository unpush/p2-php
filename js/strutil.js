/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

var StrUtil = new function()
{
	var self = this;

	this.xhtml = false;

	/* PHP の同名関数を模したメソッド */

	this.trim = function(str)
	{
		return str.replace(/^\s+|\s+$/g, '');
	}

	this.ltrim = function(str)
	{
		return str.replace(/^\s+/g, '');
	}

	this.rtrim = function(str)
	{
		return str.replace(/^\s+$/g, '');
	}

	this.nl2br = function(str)
	{
		if (self.xhtml) {
			return str.replace(/(\r\n|\r|\n)/g, '<br/>$1');
		}
		return str.replace(/(\r\n|\r|\n)/g, '<br>$1');
	}

	this.strip_tags = function(str)
	{
		return str.replace(/<.*?>/g, '');
	}

	this.htmlspecialchars = function(str)
	{
		return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); //"
	}

	/* 2ch 互換方式の nl2br/htmlspecialchars */

	this.nl2br2 = function(str)
	{
		return str.replace(/\r\n|\r|\n/g, ' <br> ');
	}

	this.htmlspecialchars2 = function(str)
	{
		return str.replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); //"
	}

	/*
		This function is in the public domain.
		Feel free to link back to http://jan.moesen.nu/
	*/
	this.sprintf = function()
	{
		if (!arguments || arguments.length < 1 || !RegExp)
		{
			return;
		}
		var str = arguments[0];
		var out = '';
		var pos = 0;
		var re = /([^%])?%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)/g; //'
		var a = b = [], numSubstitutions = 0, numMatches = 0;
		while (a = re.exec(str))
		{
			var leftpart = a[1], pPad = a[2], pJustify = a[3], pMinLength = a[4];
			var pPrecision = a[5], pType = a[6];

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

			if (re.lastIndex - a[0].length > pos) {
				out += str.substring(pos, re.lastIndex - a[0].length);
			}
			out += leftpart + subst;
			pos = re.lastIndex;
		}
		if (pos < str.length) {
			out += str.substring(pos, str.length);
		}
		return out;
	}

	/* sprintf() をベースに vsprintf() を実装 */

	this.vsprintf = function(format, params)
	{
		var args = [format];
		for (var i = 0; i < params.length; i++) {
			args.push(params[i]);
		}
		return self.sprintf.apply(this, args);
	}

	/* 実体参照 - 実際の文字 の対応表 */

	var _entity_map = {
		   _quot:'\x22',   _amp:'\x26',   _apos:'\x27',   _lt:'\x3C',   _gt:'\x3E',
		   _nbsp:'\u00A0',   _iexcl:'\u00A1',    _cent:'\u00A2',   _pound:'\u00A3',
		 _curren:'\u00A4',     _yen:'\u00A5',  _brvbar:'\u00A6',    _sect:'\u00A7',
		    _uml:'\u00A8',    _copy:'\u00A9',    _ordf:'\u00AA',   _laquo:'\u00AB',
		    _not:'\u00AC',     _shy:'\u00AD',     _reg:'\u00AE',    _macr:'\u00AF',
		    _deg:'\u00B0',  _plusmn:'\u00B1',    _sup2:'\u00B2',    _sup3:'\u00B3',
		  _acute:'\u00B4',   _micro:'\u00B5',    _para:'\u00B6',  _middot:'\u00B7',
		  _cedil:'\u00B8',    _sup1:'\u00B9',    _ordm:'\u00BA',   _raquo:'\u00BB',
		 _frac14:'\u00BC',  _frac12:'\u00BD',  _frac34:'\u00BE',  _iquest:'\u00BF',
		 _Agrave:'\u00C0',  _Aacute:'\u00C1',   _Acirc:'\u00C2',  _Atilde:'\u00C3',
		   _Auml:'\u00C4',   _Aring:'\u00C5',   _AElig:'\u00C6',  _Ccedil:'\u00C7',
		 _Egrave:'\u00C8',  _Eacute:'\u00C9',   _Ecirc:'\u00CA',    _Euml:'\u00CB',
		 _Igrave:'\u00CC',  _Iacute:'\u00CD',   _Icirc:'\u00CE',    _Iuml:'\u00CF',
		    _ETH:'\u00D0',  _Ntilde:'\u00D1',  _Ograve:'\u00D2',  _Oacute:'\u00D3',
		  _Ocirc:'\u00D4',  _Otilde:'\u00D5',    _Ouml:'\u00D6',   _times:'\u00D7',
		 _Oslash:'\u00D8',  _Ugrave:'\u00D9',  _Uacute:'\u00DA',   _Ucirc:'\u00DB',
		   _Uuml:'\u00DC',  _Yacute:'\u00DD',   _THORN:'\u00DE',   _szlig:'\u00DF',
		 _agrave:'\u00E0',  _aacute:'\u00E1',   _acirc:'\u00E2',  _atilde:'\u00E3',
		   _auml:'\u00E4',   _aring:'\u00E5',   _aelig:'\u00E6',  _ccedil:'\u00E7',
		 _egrave:'\u00E8',  _eacute:'\u00E9',   _ecirc:'\u00EA',    _euml:'\u00EB',
		 _igrave:'\u00EC',  _iacute:'\u00ED',   _icirc:'\u00EE',    _iuml:'\u00EF',
		    _eth:'\u00F0',  _ntilde:'\u00F1',  _ograve:'\u00F2',  _oacute:'\u00F3',
		  _ocirc:'\u00F4',  _otilde:'\u00F5',    _ouml:'\u00F6',  _divide:'\u00F7',
		 _oslash:'\u00F8',  _ugrave:'\u00F9',  _uacute:'\u00FA',   _ucirc:'\u00FB',
		   _uuml:'\u00FC',  _yacute:'\u00FD',   _thorn:'\u00FE',    _yuml:'\u00FF',
		  _OElig:'\u0152',   _oelig:'\u0153',  _Scaron:'\u0160',  _scaron:'\u0161',
		   _Yuml:'\u0178',    _circ:'\u02C6',   _tilde:'\u02DC',    _fnof:'\u0192',
		  _Alpha:'\u0391',    _Beta:'\u0392',   _Gamma:'\u0393',   _Delta:'\u0394',
		_Epsilon:'\u0395',    _Zeta:'\u0396',     _Eta:'\u0397',   _Theta:'\u0398',
		   _Iota:'\u0399',   _Kappa:'\u039A',  _Lambda:'\u039B',      _Mu:'\u039C',
		     _Nu:'\u039D',      _Xi:'\u039E', _Omicron:'\u039F',      _Pi:'\u03A0',
		    _Rho:'\u03A1',   _Sigma:'\u03A3',     _Tau:'\u03A4', _Upsilon:'\u03A5',
		    _Phi:'\u03A6',     _Chi:'\u03A7',     _Psi:'\u03A8',   _Omega:'\u03A9',
		  _alpha:'\u03B1',    _beta:'\u03B2',   _gamma:'\u03B3',   _delta:'\u03B4',
		_epsilon:'\u03B5',    _zeta:'\u03B6',     _eta:'\u03B7',   _theta:'\u03B8',
		   _iota:'\u03B9',   _kappa:'\u03BA',  _lambda:'\u03BB',      _mu:'\u03BC',
		     _nu:'\u03BD',      _xi:'\u03BE', _omicron:'\u03BF',      _pi:'\u03C0',
		    _rho:'\u03C1',  _sigmaf:'\u03C2',   _sigma:'\u03C3',     _tau:'\u03C4',
		_upsilon:'\u03C5',     _phi:'\u03C6',     _chi:'\u03C7',     _psi:'\u03C8',
		  _omega:'\u03C9',_thetasym:'\u03D1',   _upsih:'\u03D2',     _piv:'\u03D6',
		   _ensp:'\u2002',    _emsp:'\u2003',  _thinsp:'\u2009',    _zwnj:'\u200C',
		    _zwj:'\u200D',     _lrm:'\u200E',     _rlm:'\u200F',   _ndash:'\u2013',
		  _mdash:'\u2014',   _lsquo:'\u2018',   _rsquo:'\u2019',   _sbquo:'\u201A',
		  _ldquo:'\u201C',   _rdquo:'\u201D',   _bdquo:'\u201E',  _dagger:'\u2020',
		 _Dagger:'\u2021',    _bull:'\u2022',  _hellip:'\u2026',  _permil:'\u2030',
		  _prime:'\u2032',   _Prime:'\u2033',  _lsaquo:'\u2039',  _rsaquo:'\u203A',
		  _oline:'\u203E',   _frasl:'\u2044',    _euro:'\u20AC',   _image:'\u2111',
		 _weierp:'\u2118',    _real:'\u211C',   _trade:'\u2122', _alefsym:'\u2135',
		   _larr:'\u2190',    _uarr:'\u2191',    _rarr:'\u2192',    _darr:'\u2193',
		   _harr:'\u2194',   _crarr:'\u21B5',    _lArr:'\u21D0',    _uArr:'\u21D1',
		   _rArr:'\u21D2',    _dArr:'\u21D3',    _hArr:'\u21D4',  _forall:'\u2200',
		   _part:'\u2202',   _exist:'\u2203',   _empty:'\u2205',   _nabla:'\u2207',
		   _isin:'\u2208',   _notin:'\u2209',      _ni:'\u220B',    _prod:'\u220F',
		    _sum:'\u2211',   _minus:'\u2212',  _lowast:'\u2217',   _radic:'\u221A',
		   _prop:'\u221D',   _infin:'\u221E',     _ang:'\u2220',     _and:'\u2227',
		     _or:'\u2228',     _cap:'\u2229',     _cup:'\u222A',     _int:'\u222B',
		 _there4:'\u2234',     _sim:'\u223C',    _cong:'\u2245',   _asymp:'\u2248',
		     _ne:'\u2260',   _equiv:'\u2261',      _le:'\u2264',      _ge:'\u2265',
		    _sub:'\u2282',     _sup:'\u2283',    _nsub:'\u2284',    _sube:'\u2286',
		   _supe:'\u2287',   _oplus:'\u2295',  _otimes:'\u2297',    _perp:'\u22A5',
		   _sdot:'\u22C5',   _lceil:'\u2308',   _rceil:'\u2309',  _lfloor:'\u230A',
		 _rfloor:'\u230B',    _lang:'\u2329',    _rang:'\u232A',     _loz:'\u25CA',
		 _spades:'\u2660',   _clubs:'\u2663',  _hearts:'\u2665',   _diams:'\u2666'
	}

	/* 実体参照・数値参照をデコードする */

	this.decodeEntity = function(str)
	{
		var re = /&([A-Za-z]+[0-9]?|#([0-9]+|x([0-9A-Fa-f]+)))(?:;|\b)/g;
		var out = '', pos = 0;
		var chr = '', ucd = 0;
		while (a = re.exec(str)) {
			if (pos < re.lastIndex - a[0].length) {
				out += str.substring(pos, re.lastIndex - a[0].length);
			}
			if (a[1].charAt(0) != '#') {
				chr = '_' + a[1];
				out += (_entity_map[chr]) ? _entity_map[chr] : a[0];
			} else {
				ucd = (a[2].charAt(0) != 'x') ? parseInt(a[2]) : parseInt(a[3], 16);
				out += (ucd != NaN && ucd < 65536) ? String.fromCharCode(ucd) : a[0];
			}
			pos = re.lastIndex;
		}
		if (pos < str.length) {
			out += str.substring(pos, str.length);
		}
		return out;
	}
}

// String オブジェクトのプロトタイプを拡張

String.prototype.trim = function()
{
	return StrUtil.trim(this);
}

String.prototype.ltrim = function()
{
	return StrUtil.ltrim(this);
}

String.prototype.rtrim = function()
{
	return StrUtil.rtrim(this);
}

String.prototype.nl2br = function()
{
	return StrUtil.nl2br(this);
}

String.prototype.strip_tags = function()
{
	return StrUtil.strip_tags(this);
}

String.prototype.htmlspecialchars = function()
{
	return StrUtil.htmlspecialchars(this);
}

String.prototype.nl2br2 = function()
{
	return StrUtil.nl2br2(this);
}

String.prototype.htmlspecialchars2 = function()
{
	return StrUtil.htmlspecialchars2(this);
}

String.prototype.sprintf = function()
{
	var args = [this];
	for (var i = 0; i < arguments.length; i++) {
		args.push(arguments[i]);
	}
	return StrUtil.sprintf.apply(this, args);
}

String.prototype.vsprintf = function()
{
	return StrUtil.vsprintf.apply(this, [this, arguments[0]]);
}

String.prototype.decodeEntity = function()
{
	return StrUtil.decodeEntity(this);
}

// Textarea オブジェクトのプロトタイプを拡張 - IE ではこの方法は使えない
/*
document.createElement('textarea').constructor.prototype.toHTML = function()
{
	return this.value.decodeEntity().htmlspecialchars().nl2br();
}

document.createElement('textarea').constructor.prototype.toHTML2 = function()
{
	return this.value.htmlspecialchars2().nl2br2();
}
*/
