/**
 * rep2expack - ユーザ設定管理のUIをタブ化する
 */

// {{{ GLOBALS

var _EDIT_CONF_USER_JS_PARENT_TABS = [];
var _EDIT_CONF_USER_JS_ACTIVE_TAB1 = null;
var _EDIT_CONF_USER_JS_ACTIVE_TAB2 = null;

// }}}
// {{{ _edit_conf_user_js_onload()

var _edit_conf_user_js_onload = function() {
	// ウインドウのタイトルを設定
	setWinTitle();

	// タブ用要素生成関数
	var getTab = function() {
		var aTab = document.createElement('span');
		aTab.style.marginLeft = '5px';
		aTab.style.paddingBottom = '1px';
		aTab.style.verticalAlign = 'bottom';
		return aTab;
	}

	// ボタン要素生成関数
	var getBtn = function(btn_type, btn_name, btn_value) {
		var aBtn = document.createElement('input');
		aBtn.type = btn_type;
		aBtn.name = btn_name;
		aBtn.value = btn_value;
		aBtn.style.fontSize = '80%';
		return aBtn;
	}

	// １つ目の 'tabbernav' に送信・リセット用のタブを追加する
	var tabs = document.getElementsByTagName('ul');
	var i, l = tabs.length;
	for (i = 0; i < l; i++) {
		if (tabs[i].className != 'tabbernav') {
			continue;
		}
		var targetForm = document.getElementById('edit_conf_user_form');

		// 「変更を保存する」タブ
		var saveTab = getTab();
		var saveBtn = getBtn('submit', 'submit_save', '変更を保存する');
		/*saveBtn.onclick = function() {
			var msg = '変更を保存してもよろしいですか？';
			return window.confirm(msg);
		}*/
		saveTab.appendChild(saveBtn);

		// 「変更を取り消す」タブ
		var resetTab = getTab();
		var resetBtn = getBtn('reset', 'reset_change', '変更を取り消す');
		resetBtn.onclick = function() {
			var msg = '変更を取り消してもよろしいですか？' + '\n';
				msg += '（全てのタブの変更がリセットされます）';
			return window.confirm(msg);
		}
		resetTab.appendChild(resetBtn);

		// 「デフォルトに戻す」タブ
		var defaultTab = getTab();
		var defaultBtn = getBtn('submit', 'submit_default', 'デフォルトに戻す');
		defaultBtn.onclick = function() {
			var msg = 'ユーザ設定をデフォルトに戻してもよろしいですか？' + '\n';
				msg += '（やり直しはできません）';
			return window.confirm(msg);
		}
		defaultTab.appendChild(defaultBtn);

		// タブを追加
		tabs[i].appendChild(document.createElement('li')).appendChild(saveTab);
		tabs[i].appendChild(document.createElement('li')).appendChild(resetTab);
		tabs[i].appendChild(document.createElement('li')).appendChild(defaultTab);
		break;
	}

	// タブをアクティベート
	var anchors, anchor, callback, title, group, group1, group2, j, k;

	k = _EDIT_CONF_USER_JS_PARENT_TABS.length;
	group1 = document.getElementById('active_tab1');
	group2 = document.getElementById('active_tab2');

	anchors = document.evaluate('.//ul[contains(concat(" ", @class, " "), " tabbernav ")]'
								+ '//a[@href = "javascript:void(null);" and @title]',
								document.body, null,
								XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
	l = anchors.snapshotLength;

	for (i = 0; i < l; i++) {
		anchor = anchors.snapshotItem(i);
		if (typeof anchor.onclick === 'function') {
			title = anchor.getAttribute('title');

			if (title == _EDIT_CONF_USER_JS_ACTIVE_TAB1 ||
				title == _EDIT_CONF_USER_JS_ACTIVE_TAB2)
			{
				anchor.onclick();
			}

			group = group2;
			for (j = 0; j < k; j++) {
				if (title === _EDIT_CONF_USER_JS_PARENT_TABS[j]) {
					group = group1;
					break;
				}
			}

			if (group) {
				callback = (function(field, title) {
					return function() {
						field.value = title;
					};
				})(group, title);

				if (typeof anchor.addEventListener === 'function') {
					anchor.addEventListener('click', callback, false);
				} else if (typeof anchor.attachEvent === 'function') {
					anchor.attachEvent('click', callback);
				}
			}
		}
	}
};

// }}}

(function(){
	if (typeof window.onload == 'function') {
		var oldonload = window.onload;
		window.onload = function(event) {
			oldonload(event);
			_edit_conf_user_js_onload();
		};
	} else {
		window.onload = _edit_conf_user_js_onload;
	}
})();

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

