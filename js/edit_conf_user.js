/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

/* expack - ユーザ設定管理でタブを追加するためのJavaScript */
/* 必ずtabber.jsの後に読み込む */

var oldonload = window.onload;
window.onload = (function()
{
	// ウインドウのタイトルを設定
	setWinTitle();
	if (!document.getElementsByTagName) {
		return;
	}

	// 古い onload イベントハンドラ (=タブ生成) を実行
	if (typeof oldonload == 'function') {
		oldonload();
	}

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
	for (var i = 0; i < tabs.length; i++) {
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
		return;
	}
});
