rep2+Wiki IDストーカーポップアッププラグイン

インストール方法
1.ファイルの設置
read.phpと同じディレクトリに以下のようにディレクトリ構造を作る。
read.php (rep2に含まれています)
img/spacer.gif (rep2に含まれています)
stalker.php
plugin/stalker/stalker.png
plugin/stalker/stalker.class.php
2.置換ワード
置換ワードの日付に以下の項目を追加。
Match=$
Replace=<a href="stalker.php?bbs=$bbs&amp;id=$id" onmouseover="showHtmlPopUp('stalker.php?bbs=$bbs&amp;id=$id',event,0.2)" onmouseout="offHtmlPopUp()" title="このIDをIDストーカーで表示" target="_blank"><img src="stalker.php?img=1&amp;host=$host&amp;bbs=$bbs" height=12px></a>

IDストーカーに対応してる板ではIDストーカーのボタン[stalker]とIDストーカーのポップアップリンクが表示されます。
対応していない板では横幅1px*12pxの画像が表示されます。