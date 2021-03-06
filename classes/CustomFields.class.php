<?php /* $Id$ $URL$ */
if (!defined('W2P_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
*	CustomField Classes
*/

class CustomField {
	public $field_id;
	public $field_order;
	public $field_name;
	public $field_description;
	public $field_htmltype;
	public $field_published;
	// TODO - data type, meant for validation if you just want numeric data in a text input
	// but not yet implemented
	public $field_datatype;

	public $field_extratags;

	public $object_id = null;

	public $value_id = 0;

	public $value_charvalue;
	public $value_intvalue;

	public function CustomField($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published) {
		$this->field_id = $field_id;
		$this->field_name = $field_name;
		$this->field_order = $field_order;
		$this->field_description = $field_description;
		$this->field_extratags = $field_extratags;
		$this->field_published = $field_published;
	}

	public function load($object_id) {
		// Override Load Method for List type Classes
		global $db;
		$q = new DBQuery;
		$q->addTable('custom_fields_values');
		$q->addWhere('value_field_id = ' . $this->field_id);
		$q->addWhere('value_object_id = ' . $object_id);
		$rs = $q->exec();
		$row = $q->fetchRow();
		$q->clear();

		$value_id = $row['value_id'];
		$value_charvalue = $row['value_charvalue'];
		$value_intvalue = $row['value_intvalue'];

		if ($value_id != null) {
			$this->value_id = $value_id;
			$this->value_charvalue = $value_charvalue;
			$this->value_intvalue = $value_intvalue;
		}
	}

	public function store($object_id) {
		global $db;
		if ($object_id == null) {
			return 'Error: Cannot store field (' . $this->field_name . '), associated id not supplied.';
		} else {
			$ins_intvalue = $this->value_intvalue == null ? '0' : $this->value_intvalue;
			$ins_charvalue = $this->value_charvalue == null ? '' : stripslashes($this->value_charvalue);

			if ($this->value_id > 0) {
				$q = new DBQuery;
				$q->addTable('custom_fields_values');
				$q->addUpdate('value_charvalue', $ins_charvalue);
				$q->addUpdate('value_intvalue', $ins_intvalue);
				$q->addWhere('value_id = ' . $this->value_id);
			} else {
				$q = new DBQuery;
				$q->addTable('custom_fields_values');
				$q->addQuery('MAX(value_id)');
				$max_id = $q->loadResult();
				$new_value_id = $max_id ? $max_id + 1 : 1;

				$q = new DBQuery;
				$q->addTable('custom_fields_values');
				$q->addInsert('value_id', $new_value_id);
				$q->addInsert('value_module', '');
				$q->addInsert('value_field_id', $this->field_id);
				$q->addInsert('value_object_id', $object_id);

				$q->addInsert('value_charvalue', $ins_charvalue);
				$q->addInsert('value_intvalue', $ins_intvalue);
			}
			$rs = $q->exec();

			$q->clear();
			if (!$rs) {
				return $db->ErrorMsg() . ' | SQL: ';
			}
		}
	}

	public function setIntValue($v) {
		$this->value_intvalue = $v;
	}

	public function intValue() {
		return $this->value_intvalue;
	}

	public function setValue($v) {
		$this->value_charvalue = $v;
	}

	public function value() {
		return $this->value_charvalue;
	}

	public function charValue() {
		return $this->value_charvalue;
	}

	public function setValueId($v) {
		$this->value_id = $v;
	}

	public function valueId() {
		return $this->value_id;
	}

	public function fieldName() {
		return $this->field_name;
	}

	public function fieldDescription() {
		return $this->field_description;
	}

	public function fieldId() {
		return $this->field_id;
	}

	public function fieldHtmlType() {
		return $this->field_htmltype;
	}

	public function fieldExtraTags() {
		return $this->field_extratags;
	}

	public function fieldOrder() {
		return $this->field_order;
	}

	public function fieldPublished() {
		return $this->field_published;
	}

}

// CustomFieldCheckBox - Produces an INPUT Element of the CheckBox type in edit mode, view mode indicates 'Yes' or 'No'
class CustomFieldCheckBox extends CustomField {
	public function CustomFieldCheckBox($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published) {
		$this->CustomField($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published);
		$this->field_htmltype = 'checkbox';
	}

	public function getHTML($mode) {
		switch ($mode) {
			case 'edit':
				$bool_tag = ($this->intValue()) ? 'checked="checked"':
				'';
				$html = $this->field_description . ': </td><td><input type="checkbox" name="' . $this->field_name . '" value="1" ' . $bool_tag . $this->field_extratags . '/>';
				break;
			case 'view':
				$bool_text = ($this->intValue()) ? 'Yes':
				'No';
				$html = $this->field_description . ': </td><td class="hilite" width="100%">' . $bool_text;
				break;
		}
		return $html;
	}

	public function setValue($v) {
		$this->value_intvalue = $v;
	}
}

// CustomFieldText - Produces an INPUT Element of the TEXT type in edit mode
class CustomFieldText extends CustomField {
	public function CustomFieldText($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published) {
		$this->CustomField($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published);
		$this->field_htmltype = 'textinput';
	}

	public function getHTML($mode) {
		switch ($mode) {
			case 'edit':
				$html = $this->field_description . ': </td><td><input type="text" class="text" name="' . $this->field_name . '" value="' . $this->charValue() . '" ' . $this->field_extratags . ' />';
				break;
			case 'view':
				$html = $this->field_description . ': </td><td class="hilite" width="100%">' . $this->charValue();
				break;
		}
		return $html;
	}
}

// CustomFieldTextArea - Produces a TEXTAREA Element in edit mode
class CustomFieldTextArea extends CustomField {
	public function CustomFieldTextArea($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published) {
		$this->CustomField($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published);
		$this->field_htmltype = 'textarea';
	}

	public function getHTML($mode) {
		switch ($mode) {
			case 'edit':
				$html = $this->field_description . ': </td><td><textarea name="' . $this->field_name . '" ' . $this->field_extratags . '>' . $this->charValue() . '</textarea>';
				break;
			case 'view':
				$html = $this->field_description . ': </td><td class="hilite" width="100%">' . nl2br($this->charValue());
				break;
		}
		return $html;
	}
}

// CustomFieldLabel - Produces just a non editable label
class CustomFieldLabel extends CustomField {
	public function CustomFieldLabel($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published) {
		$this->CustomField($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published);
		$this->field_htmltype = 'label';
	}

	public function getHTML($mode) {
		// We don't really care about its mode
		return '<span ' . $this->field_extratags . '>' . $this->field_description . '</span>';
	}
}

// CustomFieldSeparator - Produces just an horizontal line
class CustomFieldSeparator extends CustomField {
	public function CustomFieldSeparator($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published) {
		$this->CustomField($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published);
		$this->field_htmltype = 'separator';
	}

	public function getHTML($mode) {
		// We don't really care about its mode
		return '<hr ' . $this->field_extratags . ' />';
	}
}

// CustomFieldSelect - Produces a SELECT list, extends the load method so that the option list can be loaded from a seperate table
class CustomFieldSelect extends CustomField {
	public $options;

	public function CustomFieldSelect($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published) {
		$this->CustomField($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published);
		$this->field_htmltype = 'select';
		$this->options = new CustomOptionList($field_id);
		$this->options->load();
	}

	public function getHTML($mode) {
		switch ($mode) {
			case 'edit':
				$html = $this->field_description . ': </td><td>';
				$html .= $this->options->getHTML($this->field_name, $this->intValue());
				break;
			case 'view':
				$html = $this->field_description . ': </td><td class="hilite" width="100%">' . $this->options->itemAtIndex($this->intValue());
				break;
		}
		return $html;
	}

	public function setValue($v) {
		$this->value_intvalue = $v;
	}

	public function value() {
		return $this->value_intvalue;
	}
}

/* CustomFieldWeblink
** Produces an INPUT Element of the TEXT type in edit mode 
** and a <a href> </a> weblink in display mode
*/

class CustomFieldWeblink extends CustomField {
	public function CustomFieldWeblink($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published) {
		$this->CustomField($field_id, $field_name, $field_order, $field_description, $field_extratags, $field_published);
		$this->field_htmltype = 'href';
	}

	public function getHTML($mode) {
		switch ($mode) {
			case 'edit':
				$html = $this->field_description . ': </td><td><input type="text" class="text" name="' . $this->field_name . '" value="' . $this->charValue() . '" ' . $this->field_extratags . ' />';
				break;
			case 'view':
				$html = $this->field_description . ': </td><td class="hilite" width="100%"><a href="' . $this->charValue() . '">' . $this->charValue() . '</a>';
				break;
		}
		return $html;
	}
}

// CustomFields class - loads all custom fields related to a module, produces a html table of all custom fields
// Also loads values automatically if the obj_id parameter is supplied. The obj_id parameter is the ID of the module object
// eg. company_id for companies module
class CustomFields {
	public $m;
	public $a;
	public $mode;
	public $obj_id;
	public $order;
	public $published;

	public $fields;

	public function CustomFields($m, $a, $obj_id = null, $mode = 'edit', $published = 0) {
		$this->m = $m;
		$this->a = 'addedit'; // only addedit pages can carry the custom field for now
		$this->obj_id = $obj_id;
		$this->mode = $mode;
		$this->published = $published;

		// Get Custom Fields for this Module
		$q = new DBQuery;
		$q->addTable('custom_fields_struct');
		$q->addWhere('field_module = \'' . $this->m . '\' AND field_page = \'' . $this->a . '\'');
		if ($published) {
			$q->addWhere('field_published = 1');
		}
		$q->addOrder('field_order ASC');
		$rows = $q->loadList();
		if ($rows == null) {
			// No Custom Fields Available
		} else {
			foreach ($rows as $row) {
				switch ($row['field_htmltype']) {
					case 'checkbox':
						$this->fields[$row['field_name']] = new CustomFieldCheckbox($row['field_id'], $row['field_name'], $row['field_order'], stripslashes($row['field_description']), stripslashes($row['field_extratags']), $row['field_order'], $row['field_published']);
						break;
					case 'href':
						$this->fields[$row['field_name']] = new CustomFieldWeblink($row['field_id'], $row['field_name'], $row['field_order'], stripslashes($row['field_description']), stripslashes($row['field_extratags']), $row['field_order'], $row['field_published']);
						break;
					case 'textarea':
						$this->fields[$row['field_name']] = new CustomFieldTextArea($row['field_id'], $row['field_name'], $row['field_order'], stripslashes($row['field_description']), stripslashes($row['field_extratags']), $row['field_order'], $row['field_published']);
						break;
					case 'select':
						$this->fields[$row['field_name']] = new CustomFieldSelect($row['field_id'], $row['field_name'], $row['field_order'], stripslashes($row['field_description']), stripslashes($row['field_extratags']), $row['field_order'], $row['field_published']);
						break;
					case 'label':
						$this->fields[$row['field_name']] = new CustomFieldLabel($row['field_id'], $row['field_name'], $row['field_order'], stripslashes($row['field_description']), stripslashes($row['field_extratags']), $row['field_order'], $row['field_published']);
						break;
					case 'separator':
						$this->fields[$row['field_name']] = new CustomFieldSeparator($row['field_id'], $row['field_name'], $row['field_order'], stripslashes($row['field_description']), stripslashes($row['field_extratags']), $row['field_order'], $row['field_published']);
						break;
					default:
						$this->fields[$row['field_name']] = new CustomFieldText($row['field_id'], $row['field_name'], $row['field_order'], stripslashes($row['field_description']), stripslashes($row['field_extratags']), $row['field_order'], $row['field_published']);
						break;
				}
			}

			if ($obj_id > 0) {
				//Load Values
				foreach ($this->fields as $key => $cfield) {
					$this->fields[$key]->load($this->obj_id);
				}
			}
		}

	}

	public function add($field_name, $field_description, $field_htmltype, $field_datatype, $field_extratags, $field_order, $field_published, &$error_msg) {
		global $db;
		
		$q = new DBQuery;
		$q->addTable('custom_fields_struct');
		$q->addQuery('MAX(field_id)');
		$max_id = $q->loadResult();
		$next_id = $max_id ? $max_id + 1 : 1;

		$field_order = $field_order ? $field_order : 1;
		$field_published = $field_published ? 1 : 0; 
		
		$field_a = 'addedit';

		// TODO - module pages other than addedit
		// TODO - validation that field_name doesnt already exist
		$q = new DBQuery;
		$q->addTable('custom_fields_struct');
		$q->addInsert('field_id', $next_id);
		$q->addInsert('field_module', $this->m);
		$q->addInsert('field_page', $field_a);
		$q->addInsert('field_htmltype', $field_htmltype);
		$q->addInsert('field_datatype', $field_datatype);
		$q->addInsert('field_order', $field_order);
		$q->addInsert('field_name', $field_name);
		$q->addInsert('field_description', $field_description);
		$q->addInsert('field_extratags', $field_extratags);
		$q->addInsert('field_order', $field_order);
		$q->addInsert('field_published', $field_published);

		if (!$q->exec()) {
			$error_msg = $db->ErrorMsg();
			$q->clear();
			return 0;
		} else {
			$q->clear();
			return $next_id;
		}
	}

	public function update($field_id, $field_name, $field_description, $field_htmltype, $field_datatype, $field_extratags, $field_order, $field_published, &$error_msg) {
		global $db;

		$q = new DBQuery;
		$q->addTable('custom_fields_struct');
		$q->addUpdate('field_name', $field_name);
		$q->addUpdate('field_description', $field_description);
		$q->addUpdate('field_htmltype', $field_htmltype);
		$q->addUpdate('field_datatype', $field_datatype);
		$q->addUpdate('field_extratags', $field_extratags);
		$q->addUpdate('field_order', $field_order);
		$q->addUpdate('field_published', $field_published);
		$q->addWhere('field_id = ' . $field_id);
		if (!$q->exec()) {
			$error_msg = $db->ErrorMsg();
			$q->clear();
			return 0;
		} else {
			$q->clear();
			return $field_id;
		}
	}

	public function fieldWithId($field_id) {
		foreach ($this->fields as $k => $v) {
			if ($this->fields[$k]->field_id == $field_id) {
				return $this->fields[$k];
			}
		}
	}

	public function bind(&$formvars) {
		if (!count($this->fields) == 0) {
			foreach ($this->fields as $k => $v) {
				//					if ($formvars[$k] != NULL)
				//					{
				$this->fields[$k]->setValue(@$formvars[$k]);
				//					}
			}
		}
	}

	public function store($object_id) {
		if (!count($this->fields) == 0) {
			$store_errors = '';
			foreach ($this->fields as $k => $cf) {
				$result = $this->fields[$k]->store($object_id);
				if ($result) {
					$store_errors .= 'Error storing custom field ' . $k . ':' . $result;
				}
			}

			//if ($store_errors) return $store_errors;
			if ($store_errors) {
				echo $store_errors;
			}
		}
	}

	public function deleteField($field_id) {
		global $db;
		$q = new DBQuery;
		$q->setDelete('custom_fields_struct');
		$q->addWhere('field_id = ' . $field_id);
		if (!$q->exec()) {
			$q->clear();
			return $db->ErrorMsg();
		}
	}

	public function count() {
		return count($this->fields);
	}

	public function getHTML() {
		if ($this->count() == 0) {
			return '';
		} else {
			$html = '';
			if (!$this->published) {
				$html = '<table width="100%">';
			}

			foreach ($this->fields as $cfield) {
				if (!$this->published) {
					$html .= "\t" . '<tr><td nowrap="nowrap">' . $cfield->getHTML($this->mode) . '</td></tr>';
				} else {
					$html .= "\t" . '<tr><td align="right" nowrap="nowrap">' . $cfield->getHTML($this->mode) . '</td></tr>';
				}
			}
			if (!$this->published) {
				$html .= '</table>';
			}
			return $html;
		}
	}

	public function printHTML() {
		echo $this->getHTML();
	}

	public function search($moduleTable, $moduleTableId, $moduleTableName, $keyword) {
		$q = new DBQuery;
		$q->addTable('custom_fields_values', 'cfv');
		$q->addQuery('m.' . $moduleTableId);
		$q->addQuery('m.' . $moduleTableName);
		$q->addQuery('cfv.value_charvalue');
		$q->addJoin('custom_fields_struct', 'cfs', 'cfs.field_id = cfv.value_field_id');
		$q->addJoin($moduleTable, 'm', 'm.' . $moduleTableId . ' = cfv. value_object_id');
		$q->addWhere('cfs.field_module = \'' . $this->m . '\'');
		$q->addWhere('cfv.value_charvalue LIKE \'%' . $keyword . '%\'');
		return $q->loadList();
	}
	public static function getCustomFieldList($module) {
		$q = new DBQuery;
		$q->addTable('custom_fields_struct', 'cfs');
		$q->addWhere("cfs.field_module = '$module'");
		$q->addOrder('cfs.field_order');

		return $q->loadList();
	}
	public static function getCustomFieldByModule($AppUI, $module, $objectId) {
		$perms = $AppUI->acl();
		$canRead = !$perms->checkModule($module, 'view', $objectId);

		if ($canRead) {
			$q = new DBQuery;
			$q->addTable('custom_fields_struct', 'cfs');
			$q->addQuery('cfv.value_charvalue, cfl.list_value');
			$q->leftJoin('custom_fields_values', 'cfv', 'cfv.value_field_id = cfs.field_id');
			$q->leftJoin('custom_fields_lists', 'cfl', 'cfl.list_option_id = cfv.value_intvalue');
			$q->addWhere("cfs.field_module = '$module'");
			$q->addWhere('cfv.value_object_id ='. $objectId);
			return $q->loadList();
		}
	}
}

class CustomOptionList {
	public $field_id;
	public $options;

	public function CustomOptionList($field_id) {
		$this->field_id = $field_id;
		$this->options = array();
	}

	public function load() {
		global $db;

		$q = new DBQuery;
		$q->addTable('custom_fields_lists');
		$q->addWhere('field_id = ' . $this->field_id);
		$q->addOrder('list_value');
		if (!$rs = $q->exec()) {
			$q->clear();
			return $db->ErrorMsg();
		}

		$this->options = array();

		while ($opt_row = $q->fetchRow()) {
			$this->options[$opt_row['list_option_id']] = $opt_row['list_value'];
		}
		$q->clear();
	}

	public function store() {
		global $db;

		if (!is_array($this->options)) {
			$this->options = array();
		}

		//load the dbs options and compare them with the options
		$q = new DBQuery;
		$q->addTable('custom_fields_lists');
		$q->addWhere('field_id = ' . $this->field_id);
		$q->addOrder('list_value');
		if (!$rs = $q->exec()) {
			$q->clear();
			return $db->ErrorMsg();
		}

		$dboptions = array();

		while ($opt_row = $q->fetchRow()) {
			$dboptions[$opt_row['list_option_id']] = $opt_row['list_value'];
		}
		$q->clear();

		$newoptions = array();
		$newoptions = array_diff($this->options, $dboptions);
		$deleteoptions = array_diff($dboptions, $this->options);
		//insert the new options
		foreach ($newoptions as $opt) {
			$q = new DBQuery;
			$q->addTable('custom_fields_lists');
			$q->addQuery('MAX(list_option_id)');
			$max_id = $q->loadResult();
			$optid = $max_id ? $max_id + 1 : 1;

			$q = new DBQuery;
			$q->addTable('custom_fields_lists');
			$q->addInsert('field_id', $this->field_id);
			$q->addInsert('list_option_id', $optid);
			$q->addInsert('list_value', db_escape(strip_tags($opt)));

			if (!$q->exec()) {
				$insert_error = $db->ErrorMsg();
			}
			$q->clear();
		}
		//delete the deleted options
		foreach ($deleteoptions as $opt => $value) {
			$q = new DBQuery;
			$q->setDelete('custom_fields_lists');
			$q->addWhere('list_option_id =' . $opt);

			if (!$q->exec()) {
				$delete_error = $db->ErrorMsg();
			}
			$q->clear();
		}

		return $insert_error . ' ' . $delete_error;
	}

	public function delete() {
		$q = new DBQuery;
		$q->setDelete('custom_fields_lists');
		$q->addWhere('field_id = ' . $this->field_id);
		$q->exec();
		$q->clear();
	}

	public function setOptions($option_array) {
		$this->options = $option_array;
	}

	public function getOptions() {
		return $this->options;
	}

	public function itemAtIndex($i) {
		return $this->options[$i];
	}

	public function getHTML($field_name, $selected) {
		$html = '<select name="' . $field_name . '">';
		foreach ($this->options as $i => $opt) {
			$html .= "\t" . '<option value="' . $i . '"';
			if ($i == $selected) {
				$html .= ' selected="selected" ';
			}
			$html .= '>' . $opt . '</option>';
		}
		$html .= '</select>';
		return $html;
	}
}