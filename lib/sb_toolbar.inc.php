<?php
// p2 -  サブジェクト -  ツールバー表示
// for subject.php

require_once P2_LIB_DIR . '/sb_toolbar.funcs.php';

// 主なHTML表示用変数は sb_header.inc.php で設定されている

$sb_tool_i = updateSbToolI(); // int

//=============================================================================
// HTMLプリント
//=============================================================================
?>
<table id="sbtoolbar<?php eh($sb_tool_i); ?>" class="toolbar" cellspacing="0">
	<tr>
		<td align="left" valign="middle" nowrap>
			<?php echo $ptitle_ht; ?>
		</td>
		<td align="left" valign="middle" nowrap>
			<form class="toolbar" method="GET" action="<?php eh($_conf['subject_php']) ?>" accept-charset="<?php eh($_conf['accept_charset']); ?>" target="_self">
				<?php echo $sb_form_hidden_ht; ?>
				<input type="submit" name="submit_refresh" value="更新">
				<?php echo $sb_disp_num_ht; ?>
			</form>
		</td>
		<td align="left" valign="middle" nowrap>
			<?php echo $filter_form_ht; ?>
		</td>
		<td align="left" valign="middle" nowrap>
			<?php echo $edit_ht; ?>
		</td>
		<td align="right" valign="middle" nowrap>
			<?php echo getSbToolbarShinchakuMatomeHtml($aThreadList, $shinchaku_num); ?>
			<span class="time"><?php eh($reloaded_time); ?></span>
			<?php echo getSbToolAnchorHtml($sb_tool_i); ?>
		</td>
	</tr>
</table>
<?php

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
