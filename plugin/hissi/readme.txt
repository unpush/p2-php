rep2+Wiki 必死チェッカープラグイン

インストール方法
1.ファイルの設置
read.phpと同じディレクトリに以下のようにディレクトリ構造を作る。
read.php (rep2に含まれています)
img/spacer.gif (rep2に含まれています)
hissi.php
plugin/hissi/hissi.png
plugin/hissi/hissi.class.php
2.置換ワード
置換ワードの日付に以下の項目を追加。
Match=(.*?(\d{4})/(\d{2})/(\d{2}).*)
Replace=$1<a href="hissi.php?bbs=$bbs&amp;date=$2$3$4&amp;id=$id" onmouseover="showHtmlPopUp('hissi.php?bbs=$bbs&amp;date=$2$3$4&amp;id=$id',event,0.2)" onmouseout="offHtmlPopUp()" title="このIDを必死チェッカーで表示" target="_blank"><img src="hissi.php?img=1&amp;host=$host&amp;bbs=$bbs" height=12px></a>

必死チェッカーに対応してる板では必死チェッカーのボタン[hissi]と必死チェッカーのポップアップリンクが表示されます。
対応していない板では横幅1px*12pxの画像が表示されます。

カスタマイズ
Replace=$1<a href="hissi.php?bbs=$bbs&amp;date=$2$3$4&amp;id=$id" onmouseover="showHtmlPopUp('hissi.php?bbs=$bbs&amp;date=$2$3$4&amp;id=$id',event,0.2)" onmouseout="offHtmlPopUp()" title="このIDを必死チェッカーで表示" target="_blank"><img src="hissi.php?img=1&amp;host=$host&amp;bbs=$bbs" height=12px></a>
の0.2を秒単位で変更する事で、ポップアップまでの時間を変更できます。