<?php /* $Id$ $URL$ */

if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

// check permissions
$perms = &$AppUI->acl();
if (!$perms->checkModule('system', 'edit')) {
	$AppUI->redirect('m=public&a=access_denied');
}
$reset = intval(w2PgetParam($_GET, 'reset', 0));
if ($reset == 1) {
	$obj = &$AppUI->acl();
	$obj->recalcPermissions();
} 

$w2Pcfg = new CConfig();

// retrieve the system configuration data
$rs = $w2Pcfg->loadAll('config_group');

$tab = $AppUI->processIntState('ConfigIdxTab', $_GET, 'tab', 0);

$active = intval(!$AppUI->getState('ConfigIdxTab'));

$titleBlock = new CTitleBlock('System Configuration', 'control-center.png', $m);
$titleBlock->addCrumb('?m=system', 'system admin');
$titleBlock->addCrumb('?m=system&a=addeditpref', 'default user preferences');
$titleBlock->show();

// prepare the automated form fields based on db system configuration data
$output = null;
$last_group = '';
foreach ($rs as $c) {

	$tooltip = $AppUI->_($c['config_name'] . '_tooltip');
	// extraparse the checkboxes and the select lists
	$extra = '';
	$value = '';
	switch ($c['config_type']) {
		case 'select':
			// Build the select list.
			$entry = '<select class="text" name="w2Pcfg[' . $c['config_name'] . ']">';
			// Find the detail relating to this entry.
			$children = $w2Pcfg->getChildren($c['config_id']);
			foreach ($children as $child) {
				$entry .= '<option value="' . $child['config_list_name'] . '"';
				if ($child['config_list_name'] == $c['config_value']) {
					$entry .= ' selected="selected"';
				}
				$entry .= '>' . $AppUI->_($child['config_list_name'] . '_item_title') . '</option>';
			}
			$entry .= '</select>';
			break;
		case 'checkbox':
			$extra = ($c['config_value'] == 'true') ? 'checked="checked"' : '';
			$value = 'true';
			// allow to fallthrough
		default:
			if (!$value) {
				$value = $c['config_value'];
			}
			$entry = '<input class="text" type="' . $c['config_type'] . '" name="w2Pcfg[' . $c['config_name'] . ']" value="' . $value . '" ' . $extra . '/>';
			break;
	}

	if ($c['config_group'] != $last_group) {
		$output .= '<tr><td colspan="2"><b>' . $AppUI->_($c['config_group'] . '_group_title') . '</b></td></tr>';
		$last_group = $c['config_group'];
	}
	$output .= '<tr><td class="item" width="20%">' . $AppUI->_($c['config_name'] . '_title') .
			'</td><td align="left">' . $entry . w2PtoolTip($AppUI->_($c['config_name'] . '_title'), $tooltip, true) . w2PshowImage('log-info.gif') . w2PendTip() . '
				<input class="button" type="hidden"  name="w2PcfgId[' . $c['config_name'] . ']" value="' . $c['config_id'] . '" />
			</td>
        </tr>
	';

}
?>
<form name="cfgFrm" action="index.php?m=system&a=systemconfig" method="post" accept-charset="utf-8">
	<input type="hidden" name="dosql" value="do_systemconfig_aed" />
	<table cellspacing="0" cellpadding="3" border="0" class="std" width="100%" align="center">
		<tr><td colspan="2"><?php echo $AppUI->_('syscfg_intro'); ?></td></tr>
		<?php echo $output; ?>
		<tr>
	 		<td align="right" colspan="2"><input class="button" type="submit" name="do_save_cfg" value="<?php echo $AppUI->_('Save'); ?>" /></td>
		</tr>
	</table>
</form>