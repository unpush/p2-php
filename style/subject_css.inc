<?php
// p2 ースタイル設定
// for subject.php スレッドリスト表示部分

if($STYLE['a_underline_none'] == 2){
	$thre_title_underline_css =<<<EOP
	a.thre_title{text-decoration:none;}
	a.thre_title_new{text-decoration:none;}
	a.thre_title_fav{text-decoration:none;}
EOP;
}

print <<<EOP

<style type="text/css" media="all">
<!--
	body{
		margin:0px 0px 8px 0px;
		line-height:130%;
		background-color:{$STYLE['sb_bgcolor']};
		color:{$STYLE['sb_color']};
	}
	body, td{font-size:{$STYLE['sb_fontsize']};}

	a:link{color:{$STYLE['sb_acolor']};}
	a:visited{color:{$STYLE['sb_acolor_v']};}
	a:hover{color:{$STYLE['sb_acolor_h']};}

	p{margin:8px 8px;}
	form{margin:0px; padding:0px;}
	form.check{margin:0px;}
	form#urlform{margin:8px 8px;}
	hr{width:98%;}
	
	.info{color:#777;} /* p2 info message */
	
	/*ツールバー*/
	table.toolbar{
		margin:0px;
		padding:2px;
		border:solid; border-width:1px; border-color:{$STYLE['sb_tool_border_color']};
		background-color:{$STYLE['sb_tool_bgcolor']};
		width:100%;
	}
	
	table.toolbar td{
		color:{$STYLE['sb_tool_sepa_color']};
	}
	
	tr.tableheader td{
		background-color:{$STYLE['sb_th_bgcolor']};
	}
	
	.itatitle{ /* 板の名前 */
		display:inline;
		margin:4px 2px 4px 12px;
		font-size:14px;
		color:{$STYLE['sb_tool_color']};
	}
	a:link.aitatitle{color:{$STYLE['sb_tool_acolor']};}
	a:visited.aitatitle{color:{$STYLE['sb_tool_acolor_v']};}
	a:hover.aitatitle{color:{$STYLE['sb_tool_acolor_h']};}
	
	a.narabi{margin:4px 2px 4px 8px;} /* 並替 */
	a:link.narabi{color:{$STYLE['sb_tool_acolor']};}
	a:visited.narabi{color:{$STYLE['sb_tool_acolor_v']};}
	a:hover.narabi{color:{$STYLE['sb_tool_acolor_h']};}
	
	a:link.matome{color:{$STYLE['sb_tool_acolor']};} /* 新着まとめ読み */
	a:visited.matome{color:{$STYLE['sb_tool_acolor_v']};}
	a:hover.matome{color:{$STYLE['sb_tool_acolor_h']};}
	
	span.matome_num{color:{$STYLE['sb_tool_newres_color']};} /* 新着まとめ読み 新着レス数 */
	
	.time{margin:4px 8px; color:{$STYLE['sb_tool_color']};} /* subject更新時間表示*/
	
	a:link.toolanchor{color:{$STYLE['sb_tool_acolor']};} /* ツールバーの▼▲ */
	a:visited.toolanchor{color:{$STYLE['sb_tool_acolor_v']};}
	a:hover.toolanchor{color:{$STYLE['sb_tool_acolor_h']};}

	
	a:link.now_sort{color:{$STYLE['sb_now_sort_color']};} /* 現在のソート形式*/
	a:visited.now_sort{color:{$STYLE['sb_now_sort_color']};}
	a:hover.now_sort{color:{$STYLE['sb_acolor_h']};}
	
	a:link.thre_title{color:{$STYLE['thre_title_color']};} /* スレタイトル */
	a:visited.thre_title{color:{$STYLE['thre_title_color_v']};}
	a:hover.thre_title{color:{$STYLE['thre_title_color_h']};}
	
	a:link.thre_title_new{color:{$STYLE['sb_thre_title_new_color']};} /* スレタイトル 新規*/
	a:visited.thre_title_new{color:{$STYLE['thre_title_color_v']};}
	a:hover.thre_title_new{color:{$STYLE['thre_title_color_h']};}
	
	/*
	a:link.thre_title_fav{color:#369;} // スレタイトル お気にスレ
	a:visited.thre_title_fav{color:#369;}
	a:hover.thre_title_fav{color:{$STYLE['thre_title_color_h']};}
	*/
	
	{$thre_title_underline_css}
	
	a.info{color:{$STYLE['sb_order_color']};} /* スレ一覧の番号 */
	a:hover.info{color:{$STYLE['sb_tacolor_h']};}
	
	a:link.un_a{color:{$STYLE['sb_newres_color']};} /* 新着レス数 */
	a:visited.un_a{ color:{$STYLE['sb_newres_color']};}
	a:hover.un_a{ color:{$STYLE['sb_acolor_h']};}
	
	.un_n{color:#999;} /*「-」（dat落ちのスレ）*/
	a:link.un_n{color:#999;}
	a:visited.un_n{color:#999;}
	a:hover.un_n{color:{$STYLE['sb_acolor_h']};}
	
	a:link.te{color:#999;} /* 並び替え */
	a:visited.te{color:#999;}
	a:hover.te{color:{$STYLE['sb_tacolor_h']};}

	/* t スレッドリスト テーブル欄 基本 */
	td.t{padding:2px 4px; background:{$STYLE['sb_tbgcolor']};}
	td.t2{padding:2px 4px; background:{$STYLE['sb_tbgcolor1']};}

	/* te スレッドリスト 並び替え欄 */
	td.te{width:70px; padding:2px 4px 2px 6px; background:{$STYLE['sb_tbgcolor']};}
	td.te2{width:70px; padding:2px 4px 2px 6px; background:{$STYLE['sb_tbgcolor1']};}
	
	/* tu スレッドリスト 新着レス数欄 */	
	td.tu{width:26px; text-align:right; padding:2px 4px 2px 6px; background:{$STYLE['sb_tbgcolor']};}
	td.tu2{width:26px; text-align:right; padding:2px 4px 2px 6px; background:{$STYLE['sb_tbgcolor1']};}
	
	/* tn スレッドリストのレス数欄*/
	td.tn{width:36px; padding:2px 4px; text-align:left; color:{$STYLE['sb_ttcolor']}; background:{$STYLE['sb_tbgcolor']};}
	td.tn2{width:36px; padding:2px 4px; text-align:left; color:{$STYLE['sb_ttcolor']}; background:{$STYLE['sb_tbgcolor1']};}
		
	/* tc スレッドリスト チェックボックス欄*/
	td.tc{width:20px; padding:0px 2px; text-align:right; background:{$STYLE['sb_tbgcolor']};}
	td.tc2{width:20px; padding:0px 2px; text-align:right; background:{$STYLE['sb_tbgcolor1']};}
		
	/* to スレッドリスト オーダー番号欄*/
	td.to{width:26px; padding:2px 4px; text-align:right; background:{$STYLE['sb_tbgcolor']};}
	td.to2{width:26px; padding:2px 4px; text-align:right; background:{$STYLE['sb_tbgcolor1']};}

	/* tl スレッドリスト タイトル名欄 */
	td.tl{padding:2px 4px; background:{$STYLE['sb_tbgcolor']};}
	td.tl2{padding:2px 4px; background:{$STYLE['sb_tbgcolor1']};}

	/* ti スレッドリスト すばやさ欄 */
	td.ts{padding:2px 4px; text-align:right; background:{$STYLE['sb_tbgcolor']};}
	td.ts2{padding:2px 4px; text-align:right; background:{$STYLE['sb_tbgcolor1']};}

	/* ti スレッドリスト 勢い欄 */
	td.ti{padding:2px 4px; text-align:right; background:{$STYLE['sb_tbgcolor']};}
	td.ti2{padding:2px 4px; text-align:right; background:{$STYLE['sb_tbgcolor1']};}
	
-->
</style>

EOP;
?>