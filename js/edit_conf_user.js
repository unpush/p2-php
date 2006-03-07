/* vim: set fileencoding=cp932 ai noet ts=4 sw=4 sts=4: */
/* mi: charset=Shift_JIS */

/* expack - ユーザ設定管理でタブを追加するためのJavaScript */
/* 必ずtabber.jsの後に読み込む */

var oldonload = window.onload;
window.onload = function() {
	// ウインドウのタイトルを設定
	setWinTitle();
	if (!document.getElementsByTagName) {
		return;
	}

	// 古い onload イベントハンドラ (=タブ生成) を実行
	if (typeof oldonload == 'function') {
		oldonload();
	}

	// １つ目の 'tabbernav' に送信・リセット用のタブを追加する
	var tabs = document.getElementsByTagName('ul');
	for (var i = 0; i < tabs.length; i++) {
		if (tabs[i].className != 'tabbernav') {
			continue;
		}
		var targetForm = document.getElementById('edit_conf_user_form');

		// 「変更を保存する」タブ
		var saveTab = document.createElement('a');
		saveTab.appendChild(document.createTextNode('[変更を保存する]'));
		saveTab.href = 'javascript:void(null);';
		saveTab.style.fontSize = '80%';
		saveTab.onclick = function() {
			if (window.confirm('設定を変更してもよろしいですか？')) {
				var saveElem = document.createElement('input');
				saveElem.type = 'hidden';
				saveElem.name = 'submit_save';
				saveElem.value = 'true';
				targetForm.appendChild(saveElem);
				targetForm.submit();
			}
		}

		// 「変更を取り消す」タブ
		var resetTab = document.createElement('a');
		resetTab.appendChild(document.createTextNode('[変更を取り消す]'));
		resetTab.href = 'javascript:void(null);';
		resetTab.style.fontSize = '80%';
		resetTab.onclick = function() {
			if (window.confirm('変更を取り消してもよろしいですか？（全てのタブの変更がリセットされます）')) {
				targetForm.reset();
			}
		}

		// 「デフォルトに戻す」タブ
		var defaultTab = document.createElement('a');
		defaultTab.appendChild(document.createTextNode('[デフォルトに戻す]'));
		defaultTab.href = 'javascript:void(null);';
		defaultTab.style.fontSize = '80%';
		defaultTab.onclick = function() {
			if (window.confirm('ユーザ設定をデフォルトに戻してもよろしいですか？（やり直しはできません）')) {
				var defaultElem = document.createElement('input');
				defaultElem.type = 'hidden';
				defaultElem.name = 'submit_default';
				defaultElem.value = 'true';
				targetForm.appendChild(defaultElem);
				targetForm.submit();
			}
		}

		// タブを追加
		tabs[i].appendChild(document.createElement('li')).appendChild(saveTab);
		tabs[i].appendChild(document.createElement('li')).appendChild(resetTab);
		tabs[i].appendChild(document.createElement('li')).appendChild(defaultTab);
		return;
	}
}
