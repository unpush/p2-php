/*
 * rep2expack - ÉXÉåàÍóóópJavaScript
 */

// {{{ setWinTitle()

var setWinTitle = function () {
	if (sb_vars.shinchaku_ari) {
		window.top.document.title = 'Åö' + sb_vars.ptitle;
	} else {
		if (window.top != window.self) {
			window.top.document.title = window.self.document.title;
		}
	}
};

// }}}
// {{{ chNewAllColor()

var chNewAllColor = function () {
	$('#smynum1, #smynum2, a.un_a').css('color', sb_vars.ttcolor);
};

// }}}
// {{{ chUnColor()

var chUnColor = function (idnum) {
	$('#un' + idnum).css('color', sb_vars.ttcolor);
}

// }}}
// {{{ chTtColor()

var chTtColor = function (idnum) {
	$('#tt' + idnum + ', #to' + idnum).css('color', sb_vars.ttcolor_v);
};

// }}}
// {{{ wrapDeleLog()

var wrapDeleLog = function (qeury, from) {
	return deleLog(qeury, sb_vars.pop_size[0], sb_vars.pop_size[1], 'subject', from);
};

// }}}
// {{{ wrapSetFavJs()

var wrapSetFavJs = function (query, favdo, from) {
	return setFavJs(query, favdo, sb_vars.pop_size[0], sb_vars.pop_size[1], 'subject', from);
};

// }}}
// {{{ wrapOpenSubWin()

var wrapOpenSubWin = function (url) {
	return OpenSubWin(url + '&popup=1', sb_vars.pop_size[0], sb_vars.pop_size[1], 0, 0);
};

// }}}
// {{{ wrapShowMotoLsPopUp()

var wrapShowMotoLsPopUp = function (event, element) {
	showMotoLsPopUp(event, element, element.nextSibling.innerText)
};

// }}}
// {{{ resizeTitleCell()

var resizeTitleCell = function () {
	var w = $(window).width(), d = 0;
	$.each($('.threadlist tr').first().find('th'), function(){
		var self = $(this);
		w -= self.outerWidth();
		if (self.hasClass('tl')) {
			w += self.width();
			d++;
		}
	});
	$('.threadlist .tl').css('max-width', (w / d) + 'px');
};

// }}}
// {{{ checkAll()

var checkAll = function () {
	var checboxes = $('.threadlist input:checkbox[name!=allbox]');
	if ($('#allbox').attr('checked')) {
		checboxes.attr('checked', 'checked');
	} else {
		checboxes.removeAttr('checked');
	}
};

// }}}
// {{{ offrec_ajax()

var offrec_ajax = function (anchor) {
	var url = anchor.href.replace('info.php?', 'httpcmd.php?cmd=offrec&');
	$.get(url, null, function(text, status){
		if (status == 'error') {
			window.alert('Async error!');
		} else if (text === '0' || text === '') {
			window.alert('óöóâèúé∏îs!');
		} else {
			var row = anchor.parentNode.parentNode;
			row.parentNode.removeChild(row);
		}
	});
	return false;
};

// }}}
// {{{ $(document).ready()

$(document).ready(function(){
	setWinTitle();
	resizeTitleCell();
	$('.threadlist td.tl a').map(function(){
		var self = $(this);
		if (!self.hasClass('moto_thre')) {
			self.attr('title', self.text());
		}
	})
});

// }}}
// {{{ $(window).resize

$(window).resize(resizeTitleCell);

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
