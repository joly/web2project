<?php /* $Id$ $URL$ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}

$AppUI->savePlace();

$canEdit = !getDenyEdit($m);
$canRead = !getDenyRead($m);
if (!$canRead) {
	$AppUI->redirect('m=public&a=access_denied');
}

$sql_table = 'contacts';

//Modify this mapping to match your LDAP->contact structure
//For instance, of you want the contact_phone2 field to be populated out of, say telephonenumber2 then you would just modify
//	"physicaldeliveryofficename" => "contact_phone2",
// or
//	"telephonenumber2" => "contact_phone2",

$sql_ldap_mapping = array('givenname' => 'first_name', 'sn' => 'last_name', 'title' => 'job', 'o' => 'company', 'ou' => 'department', 'personaltitle' => 'title', 'employeetype' => 'type', 'mail' => 'email', 'telephonenumber' => 'phone', 'homephone' => 'phone2', 'fax' => 'fax', 'mobile' => 'mobile', 'postaladdress' => 'address1', 'l' => 'city', 'st' => 'state', 'postalcode' => 'zip', 'c' => 'country', 'comment' => 'notes');

$titleBlock = new CTitleBlock('Import Contacts from LDAP Directory', '', 'admin', '');
$titleBlock->addCrumb('?m=system', 'system admin');
$titleBlock->show();

if (isset($_POST['server'])) {
	$AppUI->setState('LDAPServer', $_POST['server']);
}
$server = $AppUI->getState('LDAPServer', '');

if (isset($_POST['bind_name'])) {
	$AppUI->setState('LDAPBindName', $_POST['bind_name']);
}
$bind_name = $AppUI->getState('LDAPBindName', '');

$bind_password = w2PgetParam($_POST, 'bind_password', '');

if (isset($_POST['port'])) {
	$AppUI->setState('LDAPPort', $_POST['port']);
}
$port = $AppUI->getState('LDAPPort', '389');

if (isset($_POST['dn'])) {
	$AppUI->setState('LDAPDN', $_POST['dn']);
}
$dn = $AppUI->getState('LDAPDN', '');
//$dn = 'OU=USA,O=MINEBEA';

if (isset($_POST['filter'])) {
	$AppUI->setState('LDAPFilter', $_POST['filter']);
}
$filter = $AppUI->getState('LDAPFilter', '(objectclass=Person)');

$import = w2PgetParam($_POST, 'import');
$test = w2PgetParam($_POST, 'test');

$AppUI->setState('LDAPProto', w2PgetParam($_POST, 'ldap_proto'));
$proto = $AppUI->getState('LDAPProto', '3');

?>
<form method="post" accept-charset="utf-8">
<table border="0" cellpadding="2" cellspacing="1" width="100%" class="std">
	<tr>
		<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Server'); ?>:</td>
		<td><input type="text" class="text" name="server" value="<?php echo $server; ?>" size="50" /></td>
	</tr>
	<tr>
		<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Port'); ?>:</td>
		<td><input type="text" class="text" name="port" value="<?php echo $port; ?>" size="4" /></td>
	</tr>
	<tr>
		<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Protocol'); ?>:</td>
		<td><?php
echo $AppUI->_('Version 2') . ' <input type="radio" name="ldap_proto" value="2"';
if ($proto == '2') {
	echo ' checked="checked"';
}
echo ' />  ' . $AppUI->_('Version 3') . ' <input type="radio" name="ldap_proto" value="3"';
if ($proto == '3') {
	echo ' checked="checked"';
}
echo ' />';
?>
		</td>
	</tr>
	<tr>
		<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Bind Name'); ?>:</td>
		<td><input type="text" class="text" name="bind_name" value="<?php echo $bind_name; ?>" size="50" /></td>
	</tr>
	<tr>
		<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Bind Password'); ?>:</td>
		<td><input type="password" class="text" name="bind_password" value="<?php echo $bind_password; ?>" size="25" /></td>
	</tr>
	<tr>
		<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Base DN'); ?>:</td>
		<td><input type="text" class="text" name="dn" value="<?php echo $dn; ?>" size="100" /></td>
	</tr>
	<tr>
		<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Filter'); ?>:</td>
		<td><input type="text" class="text" name="filter" value="<?php echo $filter; ?>" size="100" /></td>
	</tr>
	<tr>
		<td colspan="2" align="right"><input type="submit" name="test" value="<?php echo $AppUI->_('Test Connection and Query'); ?>" /><input type="submit" name="import" value="<?php echo $AppUI->_('Import Contacts'); ?>" /></td>
	</tr>
	<tr>
		<td colspan="2">
<pre>
<?php
$s = '<b>';
if (isset($test)) {
	$s .= $test;
}
if (isset($import)) {
	$s .= $import;
}
$s .= '</b><hr />';
if (isset($test) || isset($import)) {
	if (function_exists('ldap_connect')) {
		$ds = @ldap_connect($server, $port);
	} else {
		$s .= '<span style="color:red;font-weight:bold;">ldap_connect function is not installed.</span><br />';
		$ds = false;
	}
	 

	if (!$ds) {
		if (function_exists('ldap_error')) {
			$s .= ldap_error($ds);
		} else {
			$s .= '<span style="color:red;font-weight:bold;">ldap_connect failed.</span><br />';
		}
	} else {
		$s .= 'ldap_connect succeeded.<br />';
	}

	if (function_exists('ldap_set_option')) {
		@ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $proto);
	} else {
		$s .= '<span style="color:red;font-weight:bold;">ldap_set_option function is not installed.</span><br />';
	}

	if (!function_exists('ldap_bind') || !@ldap_bind($ds, $bind_name, $bind_password)) {
		$s .= '<span style="color:red;font-weight:bold;">ldap_bind failed.</span><br />';
		if (function_exists('ldap_error')) {
			$s .= ldap_error($ds);
		}
	} else {
		$s .= 'ldap_bind successful.<br />';
	}

	$return_types = array();
	foreach ($sql_ldap_mapping as $ldap => $sql) {
		$return_types[] = $ldap;
	}

	$s .= 'basedn: ' . $dn . '<br />';
	$s .= 'expression: ' . $filter . '<br />';

	if (function_exists('ldap_search')) {
		$sr = @ldap_search($ds, $dn, $filter, $return_types);
	} else {
		$sr = false;
	}

	if ($sr) {
		$s .= 'Search completed Sucessfully.<br />';
	} else {
		$s .= '<span style="color:red;font-weight:bold;">ldap_search failed.</span><br />';
		if (function_exists('ldap_error')) {
			$s .= 'Search Error: [' . ldap_errno($ds) . '] ' . ldap_error($ds) . '<br />';
		}		
	}

	$s .= '</pre>';

	//	$s .= "Result Count:".(ldap_count_entries($ds,$sr));
	if (function_exists('ldap_get_entries')) {
		$info = @ldap_get_entries($ds, $sr);
	} else {
		$s .= '<span style="color:red;font-weight:bold;">ldap_get_entries is not installed.</span><br />';
		$info = array();		
	}
	
	if (!$info['count']) {
		$s .= 'No contacts were found.';
	} else {
		$s .= 'Total Contacts Found:' . $info['count'] . '<hr />';
		$s .= '<table border="0" cellpadding="1" cellspacing="0" width="98%" class="std">';
		if (isset($test)) {
			foreach ($sql_ldap_mapping as $ldap => $sql) {
				$s .= '<th>' . $sql . '</th>';
			}
		} else {
			$q = new DBQuery;
			$q->addTable($sql_table);
			$q->addQuery('contact_id, contact_first_name, contact_last_name');
			$contacts = $q->loadList();
			$q->clear();

			foreach ($contacts as $contact) {
				$contact_list[$contact['contact_first_name'] . ' ' . $contact['contact_last_name']] = $contact['contact_id'];
			}
			unset($contacts);
		}

		for ($i = 0, $i_cmp = $info['count']; $i < $i_cmp; $i++) {
			$pairs = array();
			$s .= '<tr>';
			foreach ($sql_ldap_mapping as $ldap_name => $sql_name) {
				unset($val);
				if (isset($info[$i][$ldap_name][0])) {
					$val = clean_value($info[$i][$ldap_name][0]);
				}
				if ($val && $ldap_name == 'postaladdress') {
					$val = str_replace('$', "\r", $val);
				}
				if (isset($val)) {
					//if an email address is not specified in Domino you get a crazy value for this field that looks like FOO/BAR%NAME@domain.com  This'll filter those values out.
					if (isset($test) && $ldap_name == 'mail' && substr_count($val, '%') > 0) {
						$s .= '<td><span style="color:#880000;">' . $AppUI->_('bad email address') . '</span></td>';
						continue;
					}
					$pairs['contact_' . $sql_name] = $val;
					if (isset($test)) {
						$s .= '<td>' .  $val . '</td>';
					}
				} else {
					if (isset($test)) {
						$s .= '<td>-</td>';
					}
				}
			}

			if (isset($import)) {
				$pairs['contact_order_by'] = $pairs['contact_first_name'] . ' ' . $pairs['contact_last_name'];
				//Check to see if this value already exists.
				if (isset($contact_list[$pairs['contact_first_name'] . ' ' . $pairs['contact_last_name']])) {
					//if it does, remove the old one.
					$pairs['contact_id'] = $contact_list[$pairs['contact_first_name'] . ' ' . $pairs['contact_last_name']];

					//Try to find a matching company name in the system, if not them set contact_company to 0 
					$q = new DBQuery;
					$q->addQuery('company_id');
					$q->addTable('companies');
					$q->addWhere('company_name LIKE \'' . mb_trim($pairs['contact_company']) . '\'');
					$company_id = $q->loadResult();
					$pairs['contact_company'] = $company_id ? $company_id : 0;
					$q->clear();

					//Try to find a matching department name in the system, if not them set contact_department to 0 
					$q = new DBQuery;
					$q->addQuery('dept_id');
					$q->addTable('departments');
					$q->addWhere('dept_name LIKE \'' . mb_trim($pairs['contact_department']) . '\'');
					$dept_id = $q->loadResult();
					$pairs['contact_department'] = $dept_id ? $dept_id : 0;
					$q->clear();
					
					$q = new DBQuery;
					$q->updateArray($sql_table, $pairs, 'contact_id');
					$q->clear();
					$s .= '<td><span style="color:#880000;">There is a duplicate record for ' . $pairs['contact_first_name'] . ' ' . $pairs['contact_last_name'] . ', the record has been updated.</span></td>';
				} else {
					//If the contact has no name, go to the next
					if (!mb_trim($pairs['contact_first_name'] . ' ' . $pairs['contact_last_name'])) {
						continue;
					}
					$s .= '<td>Adding ' . $pairs['contact_first_name'] . ' ' . $pairs['contact_last_name'] . '.</td>';

					//Try to find a matching company name in the system, if not them set contact_company to 0 
					$q = new DBQuery;
					$q->addQuery('company_id');
					$q->addTable('companies');
					$q->addWhere('company_name LIKE \'' . mb_trim($pairs['contact_company']) . '\'');
					$company_id = $q->loadResult();
					$pairs['contact_company'] = $company_id ? $company_id : 0;
					$q->clear();

					//Try to find a matching department name in the system, if not them set contact_department to 0 
					$q = new DBQuery;
					$q->addQuery('dept_id');
					$q->addTable('departments');
					$q->addWhere('dept_name LIKE \'' . mb_trim($pairs['contact_department']) . '\'');
					$dept_id = $q->loadResult();
					$pairs['contact_department'] = $dept_id ? $dept_id : 0;
					$q->clear();

					$q = new DBQuery;
					$q->insertArray($sql_table, $pairs);
					$q->clear();
				}
			}
			$s .= '</tr>';

			/*
			for ($ii=0, $ii_cmp=$info[$i]['count']; $ii<$ii_cmp; $ii++){
			$data = $info[$i][$ii];
			for ($iii=0, $iii_cmp=$info[$i][$data]['count']; $iii<$iii_cmp; $iii++) {
			echo $data.':&nbsp;&nbsp;'.$info[$i][$data][$iii];
			}
			}
			*/
		}
		$s .= '</table>';
	}
	if (function_exists('ldap_close')) {
		ldap_close($ds);
	} else {
		$s .= '<span style="color:red;font-weight:bold;">ldap_close is not installed.</span>';
	}
}
echo $s;

function clean_value($str) {
	$bad_values = array("'");
	return str_replace($bad_values, '', $str);
}
?>
		</td>
	</tr>
</table>