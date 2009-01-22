<?php

	require_once(TOOLKIT . "/fields/field.taglist.php");
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	Class fieldEnhancedtaglist extends fieldTagList {
		public function __construct(&$parent){
			Field::__construct($parent);
			$this->_name = 'Enhanced Tag List';
		}
		
		public function appendFormattedElement(&$wrapper, $data, $encode = false) {
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

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){

			$value = $this->prepareTableValue($data);
			
			$label = Widget::Label($this->get('label'));
			
			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL)));
		
			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);

			if($this->get('pre_populate_source') != NULL){
				
				$existing_tags = $this->findAllTags();

				if(is_array($existing_tags) && !empty($existing_tags)){
					$taglist = new XMLElement('ul');
					$taglist->setAttribute('class', 'tags');
					
					foreach($existing_tags as $tag) $taglist->appendChild(new XMLElement('li', $tag));
							
					$wrapper->appendChild($taglist);
				}
			}
		}
		
		function findAllTags(){			

			$sql = "SELECT DISTINCT `value`, `id`, COUNT(value) AS COUNT FROM `tbl_entries_data_" . ($this->get('pre_populate_source') == 'existing' ? $this->get('id') : $this->get('pre_populate_source')) . "` GROUP BY `value` having COUNT >=" . $this->get('pre_populate_min');

			return $this->_engine->Database->fetchCol('value', $sql);
		}
		
		function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
						
			$status = self::__OK__;
			
			$data = preg_split('/\,\s*/i', $data, -1, PREG_SPLIT_NO_EMPTY);
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
		
		function prepareTableValue($data, XMLElement $link=NULL){
			
			if(!is_array($data) || empty($data)) return;
			
			if(is_array($data['value']) && !empty($data['value'])) $data['value'];
			
			$values = (count($data['value']) > 1) ? @implode(', ', $data['value']) : $data['value'];

			return parent::prepareTableValue(array('value' => General::sanitize($values)), $link);
		}
		
		function commit(){

			if (!parent::commit() or $this->get('id') === false) {
			return false;
			}
			$id = $this->get('id');

			$fields = array();
			
			$fields['field_id'] = $id;
			$fields['pre_populate_source'] = ($this->get('pre_populate_source') == 'none' ? NULL : $this->get('pre_populate_source'));
			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));
			$fields['ordered'] = ($this->get('ordered') ? $this->get('ordered') : 'no');
			$fields['pre_populate_min'] = ($this->get('pre_populate_min') ? $this->get('pre_populate_min') : '0' );
					
			$this->_engine->Database->query("
				DELETE FROM
				`tbl_fields_enhancedtaglist`
				WHERE
				`field_id` = '$id'
				LIMIT 1
			");
      
			return $this->_engine->Database->insert($fields, 'tbl_fields_enhancedtaglist');
					
		}
		
		function displaySettingsPanel(&$wrapper, $errors=NULL){
			
			Field::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$label = Widget::Label('Suggestion List');
			
			$sectionManager = new SectionManager($this->_engine);
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'name');
			$field_groups = array();

			if(is_array($sections) && !empty($sections))
				foreach($sections as $section) $field_groups[$section->get('id')] = array('fields' => $section->fetchFields(), 'section' => $section);
			
			$options = array(
				array('none', false, 'None'),
				array('existing', ($this->get('pre_populate_source') == 'existing'), 'Existing Values'),
			);
			
			foreach($field_groups as $group){
				
				if(!is_array($group['fields'])) continue;
				
				$fields = array();
				foreach($group['fields'] as $f){
					if($f->get('id') != $this->get('id') && $f->canPrePopulate()) $fields[] = array($f->get('id'), ($this->get('pre_populate_source') == $f->get('id')), $f->get('label'));
				}
				
				if(is_array($fields) && !empty($fields)) $options[] = array('label' => $group['section']->get('name'), 'options' => $fields);
			}
			
			$label->appendChild(Widget::Select('fields['.$this->get('sortorder').'][pre_populate_source]', $options));
			$div->appendChild($label);

			$label = Widget::Label('Suggestion Threshold');
			$label->appendChild(Widget::Input(
				'fields['.$this->get('sortorder').'][pre_populate_min]',
				($this->get('pre_populate_min') ? $this->get('pre_populate_min') : '' )
			));
			$div->appendChild($label);
			$wrapper->appendChild($div);

			$help = new XMLElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue('
				Suggestion threshold allows you to set the number of times a tag must be used before it appears in the suggestion list.
			');
			$wrapper->appendChild($help);
				
				$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]');

			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][ordered]', 'yes', 'checkbox');
			if($this->get('ordered') == 'yes') $input->setAttribute('checked', 'checked');     
			$label->setValue($input->generate() . 'Preserve list order');
			$wrapper->appendChild($label);
			
			$this->appendShowColumnCheckbox($wrapper);
						
		}

		function createTable(){
			
			return $this->_engine->Database->query(
			
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `handle` varchar(255) default NULL,
				  `value` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `handle` (`handle`),
				  KEY `value` (`value`)
				) TYPE=MyISAM;"
			
			);
		}
		
		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "INNER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = "ORDER BY `e`.`id` $order";
		}
	}