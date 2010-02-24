/*
 * rep2expack - スレ一覧用JavaScript
 */

// {{{ GLOBALS

if (typeof rep2 === 'undefined') {
	rep2 = {};
}

rep2.subject = {};

// }}}
// {{{ rep2.subject.setWindowTitle()

rep2.subject.setWindowTitle = function () {
	if (sb_vars.shinchaku_ari) {
		window.top.document.title = '★' + sb_vars.ptitle;
	} else {
		if (window.top != window.self) {
			window.top.document.title = window.self.document.title;
		}
	}
};

// }}}
// {{{ rep2.subject.changeNewAllColor()

rep2.subject.changeNewAllColor = function () {
	$('#smynum1, #smynum2, a.un_a').css('color', sb_vars.ttcolor);
};

// }}}
// {{{ rep2.subject.changeUnReadColor()

rep2.subject.changeUnReadColor = function (idnum) {
	$('#un' + idnum).css('color', sb_vars.ttcolor);
}

// }}}
// {{{ rep2.subject.changeThreadTitleColor()

rep2.subject.changeThreadTitleColor = function (idnum) {
	$('#tt' + idnum + ', #to' + idnum).css('color', sb_vars.ttcolor_v);
};

// }}}
// {{{ rep2.subject.deleteLog()

rep2.subject.deleteLog = function (qeury, from) {
	return deleLog(qeury, sb_vars.pop_size[0], sb_vars.pop_size[1], 'subject', from);
};

// }}}
// {{{ rep2.subject.setFavorite()

rep2.subject.setFavorite = function (query, favdo, from) {
	return setFavJs(query, favdo, sb_vars.pop_size[0], sb_vars.pop_size[1], 'subject', from);
};

// }}}
// {{{ rep2.subject.openSubWindow()

rep2.subject.openSubWindow = function (url) {
	return OpenSubWin(url + '&popup=1', sb_vars.pop_size[0], sb_vars.pop_size[1], 0, 0);
};

// }}}
// {{{ rep2.subject.showMotoLsPopUp()

rep2.subject.showMotoLsPopUp = function (event, element) {
	showMotoLsPopUp(event, element, element.nextSibling.innerText)
};

// }}}
// {{{ rep2.subject.resizeTitleCell()

rep2.subject.resizeTitleCell = function () {
	var w = $(window).width(), d = 0;
	$.each($('table.threadlist tr').first().find('th'), function(){
		var self = $(this);
		w -= self.outerWidth();
		if (self.hasClass('tl')) {
			w += self.width();
			d++;
		}
	});
	$('table.threadlist td.tl div.el').width(w / d);
};

// }}}
// {{{ rep2.subject.checkAll()

rep2.subject.checkAll = function () {
	var checboxes = $('.threadlist input:checkbox[name!=allbox]');
	if ($('#allbox').attr('checked')) {
		checboxes.attr('checked', 'checked');
	} else {
		checboxes.removeAttr('checked');
	}
};

// }}}
// {{{ rep2.subject.offRecent()

rep2.subject.offRecent = function (anchor) {
	var url = anchor.href.replace('info.php?', 'httpcmd.php?cmd=offrec&');
	$.get(url, null, function(text, status){
		if (status == 'error') {
			window.alert('Async error!');
		} else if (text === '0' || text === '') {
			window.alert('履歴解除失敗!');
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
	rep2.subject.setWindowTitle();
	rep2.subject.resizeTitleCell();

	// 最近読んだスレ解除
	$('.threadlist td.tc a[href$="&offrec=true"]').click(function(){
		return rep2.subject.offRecent(this);
	});

	// 情報ウインドウ表示
	$('.threadlist td.to a.info').click(function(){
		return rep2.subject.openSubWindow(this.href.toString());
	});

	// URLからhost-bbs-keyを抽出する正規表現
	var re = /[\?&](host=.+?&bbs=.+?&key=.+?)(&|$)/;

	// ログを削除
	$('.threadlist td.tu').find('a.un, a.un_a, a.un_n').attr('title', 'クリックするとログ削除');
	$('.threadlist td.tu a.un_n').click(function(){
		if (!window.confirm('ログを削除しますか？')) {
			return false;
		}
		return rep2.subject.deleteLog(re.exec($(this).attr('href'))[1], this);
	});
	$('.threadlist td.tu').find('a.un, a.un_a').click(function(){
		return rep2.subject.deleteLog(re.exec($(this).attr('href'))[1], this);
	});

	// 範囲を指定して元スレを開く小ポップアップメニュー
	var hover = function (event) {
		rep2.subject.showMotoLsPopUp(event, this);
	};

	// スレッドタイトルの文字色を変更
	var generator = function (id, midoku_ari) {
		if (midoku_ari) {
			return (function(){
				rep2.subject.changeThreadTitleColor(id);
				rep2.subject.changeUnReadColor(id);
			});
		} else {
			return (function(){
				rep2.subject.changeThreadTitleColor(id);
			});
		}
	};

	// 元スレとスレッドタイトル
	$('.threadlist td.tl a').map(function(){
		var self = $(this);
		if (self.hasClass('moto_thre')) {
			self.hover(hover, hideMotoLsPopUp);
		} else {
			self.attr('title', self.text());
			self.click(generator(self.attr('id').substring(2), self.hasClass('midoku_ari')));
		}
	})

	// IE対策
	if ($.browser.msie) {
		$('.threadlist td').contents('[nodeType=3]').wrap('<span class="nowrap"></span>');
	}
});

// }}}
// {{{ $(window).resize

$(window).resize(rep2.subject.resizeTitleCell);

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
