<?php

	require_once(TOOLKIT . "/fields/field.taglist.php");
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	Class fieldEnhancedtaglist extends fieldTagList {

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function __construct(){
			Field::__construct();
			$this->_name = 'Enhanced Tag List';
		}

		public function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `handle` varchar(255) default NULL,
				  `value` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `handle` (`handle`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
			);
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

		public function findAllTags(){
			return $this->getToggleStates();
		}

		public function getToggleStates() {
			if(!is_array($this->get('pre_populate_source'))) return;

			$values = array();
			$sql = "SELECT DISTINCT `value`, COUNT(value) AS Used FROM tbl_entries_data_%d GROUP BY `value` HAVING Used >= " . $this->get('pre_populate_min') . " ORDER BY `value` ASC";

			foreach($this->get('pre_populate_source') as $item){
				if($item == 'external') {
					$sourcedata = simplexml_load_file($this->get('external_source_url'));
					$terms = $sourcedata->xpath($this->get('external_source_path'));
					$values = array_merge($values, $terms);
				}
				else {
					$result = Symphony::Database()->fetchCol('value', sprintf($sql, ($item == 'existing' ? $this->get('id') : $item)));
					if(!is_array($result) || empty($result)) continue;
					$values = array_merge($values, $result);
				}
			}

			return array_unique($values);
		}

		private static function __tagArrayToString(array $tags, $delimiter, $ordered){
			if(empty($tags)) return NULL;
			if ($ordered != 'yes') {
			  sort($tags);
			}
			return implode($delimiter . ' ', $tags);
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			Field::displaySettingsPanel($wrapper, $errors);

			// build suggestion list
			$label = Widget::Label(__('Suggestion List'));

		    $sections = SectionManager::fetch(NULL, 'ASC', 'name');
			$field_groups = array();

			if(is_array($sections) && !empty($sections))
				foreach($sections as $section) $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);

			$options = array(
				//array('none', false, __('None')),
				array('existing', (in_array('existing', $this->get('pre_populate_source'))), __('Existing Values')),
				array('external', (in_array('external', $this->get('pre_populate_source'))), __('External XML')),
			);

			foreach($field_groups as $group){
				if(!is_array($group['fields'])) continue;

				$fields = array();
				foreach($group['fields'] as $f){
					if($f->get('id') != $this->get('id') && $f->canPrePopulate()) $fields[] = array($f->get('id'), (in_array($f->get('id'), $this->get('pre_populate_source'))), $f->get('label'));
				}

				if(is_array($fields) && !empty($fields)) $options[] = array('label' => $group['section']->get('name'), 'options' => $fields);
			}

			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][pre_populate_source][]', $options, array('multiple' => 'multiple')));
			$wrapper->appendChild($label);

			// Validation
			$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]');

			$fieldset = new XMLElement('fieldset');
			$legend = new XMLElement('legend', __('Additional Options'));
			$fieldset->appendChild($legend);

			$div = new XMLElement('div', NULL, array('class' => 'group triple'));

			// Suggestion threshold
			$label = Widget::Label(__('Suggestion Threshold'));
			$input = Widget::Input('fields['.$this->get('sortorder').'][pre_populate_min]',($this->get('pre_populate_min') ? $this->get('pre_populate_min') : '' ));
			$label->appendChild($input);
			$div->appendChild($label);

			// Custom delimiter
			$label = Widget::Label(__('Tag Delimiter'));
			$input = Widget::Input('fields['.$this->get('sortorder').'][delimiter]', ($this->get('delimiter') ? $this->get('delimiter') : ',' ));
			$label->appendChild($input);
			$div->appendChild($label);

			// Preserve order option
			$label = Widget::Label(__('Tag Ordering'));
			$options = array(
				array(
					'no',
					($this->get('ordered') == 'no' ? TRUE : FALSE),
					__('Alphabetical')
				),
				array(
					'yes',
					($this->get('ordered') == 'yes' ? TRUE : FALSE),
					__('Order Entered')
				)
			);

			$select = Widget::Select('fields['.$this->get('sortorder').'][ordered]', $options);
			$label->appendChild($select);
			$div->appendChild($label);

			$fieldset->appendChild($div);
			$wrapper->appendChild($fieldset);

			// External Suggestions
			$fieldset = new XMLElement('fieldset');
			$legend = new XMLElement('legend', __('External XML Suggestions'));
			$fieldset->appendChild($legend);
			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$label = Widget::Label(__('Source URL') . '<i>Optional</i>');
			$input = Widget::Input('fields['.$this->get('sortorder').'][external_source_url]', ($this->get('external_source_url') ? $this->get('external_source_url') : ''));
			$label->appendChild($input);
			$div->appendChild($label);

			$label = Widget::Label(__('Options XPath') . '<i>Optional</i>');
			$input = Widget::Input('fields['.$this->get('sortorder').'][external_source_path]', ($this->get('external_source_path') ? $this->get('external_source_path') : ''));
			$label->appendChild($input);
			$div->appendChild($label);
			$fieldset->appendChild($div);
			$wrapper->appendChild($fieldset);

			$this->appendShowColumnCheckbox($wrapper);
		}

		public function commit(){
			if (!parent::commit() or $this->get('id') === false) {
				return false;
			}

			$id = $this->get('id');

			$fields = array();
			$fields['field_id'] = $id;
			$fields['pre_populate_source'] = (is_null($this->get('pre_populate_source')) ? NULL : implode(',', $this->get('pre_populate_source')));
			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));
			$fields['ordered'] = ($this->get('ordered') ? $this->get('ordered') : 'no');
			$fields['delimiter'] = ($this->get('delimiter') ? $this->get('delimiter') : ',');
			$fields['pre_populate_min'] = ($this->get('pre_populate_min') ? $this->get('pre_populate_min') : '0' );
			$fields['external_source_url'] = ($this->get('external_source_url') ? $this->get('external_source_url') : NULL );
			$fields['external_source_path'] = ($this->get('external_source_path') ? $this->get('external_source_path') : NULL );

			return FieldManager::saveSettings($id, $fields);
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null){
			$value = NULL;
			if(isset($data['value'])){
				$value = (is_array($data['value']) ? self::__tagArrayToString($data['value'], $this->get('delimiter'), $this->get('ordered')) : $data['value']);
			}

			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL)));

			if($flagWithError != NULL) $wrapper->appendChild(Widget::Error($label, $flagWithError));
			else $wrapper->appendChild($label);

			if($this->get('pre_populate_source') != NULL){

				$existing_tags = $this->getToggleStates();

				if(is_array($existing_tags) && !empty($existing_tags)){
					$taglist = new XMLElement('ul');
					$taglist->setAttribute('class', 'tags');
					$taglist->setAttribute('data-interactive', 'data-interactive');

					foreach($existing_tags as $tag) $taglist->appendChild(new XMLElement('li', $tag));

					$wrapper->appendChild($taglist);
				}
			}
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=NULL){
			$status = self::__OK__;

			$data = preg_split('/\\' . $this->get('delimiter') . '\s*/i', $data, -1, PREG_SPLIT_NO_EMPTY);
			$data = array_map('trim', $data);

			if(empty($data)) return;

			$data = General::array_remove_duplicates($data);

			if ($this->get('ordered') != 'yes') {
				sort($data);
			}

			$result = array();
			foreach($data as $value){
				$result['value'][] = $value;
				$result['handle'][] = Lang::createHandle($value);
			}

			return $result;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			if (!is_array($data) or empty($data)) return;

			$list = new XMLElement($this->get('element_name'));

			if (!is_array($data['handle']) and !is_array($data['value'])) {
				$data = array(
					'handle'	=> array($data['handle']),
					'value'		=> array($data['value'])
				);
			}

			foreach ($data['value'] as $index => $value) {
				$attributes['handle'] = $data['handle'][$index];
				if ($this->get('ordered') == 'yes') {
					$attributes['order'] = $index + 1;
				}
				$list->appendChild(new XMLElement(
					'item', General::sanitize($value), $attributes
				));
			}

			$wrapper->appendChild($list);
		}

		public function prepareTableValue($data, XMLElement $link=NULL, $entry_id = null){
			if(!is_array($data) || empty($data)) return;

			$value = NULL;
			if(isset($data['value'])){
				$value = (is_array($data['value']) ? self::__tagArrayToString($data['value'], $this->get('delimiter'), $this->get('ordered')) : $data['value']);
			}

			return parent::prepareTableValue(array('value' => General::sanitize($value)), $link, $entry_id);
		}

	}
