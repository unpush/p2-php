/*
 * rep2expack - ポップアップメニュー for iPhone
 */

// {{{ GLOBALS

var _SPM_IPHONE_JS_ACTIVE_THREAD, _SPM_IPHONE_JS_ACTIVE_NUMBER;
var SPM = new Object();

// }}}
// {{{ SPM.show()

/*
 * SPMを表示する
 *
 * @param Object thread
 * @param Number no
 * @param String id
 * @param MouseEvent evt
 * @return void
 */
SPM.show = (function(thread, no, id, evt)
{
	var spm = document.getElementById('spm');
	if (!spm) {
		return;
	}

	_SPM_IPHONE_JS_ACTIVE_THREAD = thread;
	_SPM_IPHONE_JS_ACTIVE_NUMBER = no;

	var num = document.getElementById('spm-num');
	if (num) {
		while (num.childNodes.length) {
			num.removeChild(num.firstChild);
		}
		num.appendChild(document.createTextNode(no.toString()));
	}

	spm.style.display = 'block';
	spm.style.top = (evt.getOffsetY() + 10).toString() + 'px';

	//document.body.addEventListener('touchmove', this.hide, true);
});

// }}}
// {{{ SPM.show()

/*
 * SPMを非表示にする
 *
 * @param MouseEvent evt
 * @return void
 */
SPM.hide = (function(evt)
{
	//document.body.removeEventListener('touchmove', this.hide, true);

	var spm = document.getElementById('spm');
	if (!spm) {
		return;
	}

	spm.style.display = 'none';
});

// }}}
// {{{ SPM.replyTo()

/*
 * レスする
 *
 * @param Boolean quote
 * @return void
 */
SPM.replyTo = (function(quote)
{
	var url = 'spm_k.php?ktool_name=res';
	if (quote) {
		url += '_quote';
	}
	url += '&ktool_value=' + _SPM_IPHONE_JS_ACTIVE_NUMBER.toString();
	url += _SPM_IPHONE_JS_ACTIVE_THREAD.query;

	window.open(url);
});

// }}}
// {{{ SPM.doAction()

/*
 * あぼーん・NGワード・検索
 *
 * @return void
 */
SPM.doAction = (function()
{
	var action = document.getElementById('spm-select-action');
	var target = document.getElementById('spm-select-target');
	var url = 'spm_k.php?ktool_name=';

	switch (action.options[action.selectedIndex].value) {
	  case 'aborn':
	  case 'ng':
		url += action.options[action.selectedIndex].value + '_';
		break;
	  default:
		alert('SPM: Invalid Action!');
		return;
	}

	switch (target.options[target.selectedIndex].value) {
	  case 'name':
	  case 'mail':
	  case 'id':
	  case 'msg':
		url += target.options[target.selectedIndex].value;
		break;
	  default:
		alert('SPM: Invalid Target!');
		return;
	}

	url += '&ktool_value=' + _SPM_IPHONE_JS_ACTIVE_NUMBER.toString();
	url += _SPM_IPHONE_JS_ACTIVE_THREAD.query;

	window.open(url);
});

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
