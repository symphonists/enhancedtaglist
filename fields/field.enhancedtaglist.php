<?php

	require_once(TOOLKIT . "/fields/field.taglist.php");
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	Class fieldEnhancedtaglist extends fieldTagList {
	
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
	
		public function __construct(&$parent){
			Field::__construct($parent);
			$this->_name = 'Enhanced Tag List';
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
		
	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/
	
		function findAllTags(){
			
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
					$result = $this->_engine->Database->fetchCol('value', sprintf($sql, ($item == 'existing' ? $this->get('id') : $item)));
					if(!is_array($result) || empty($result)) continue;	
					$values = array_merge($values, $result);
				}
			}
			return array_unique($values);
		}
		
		static private function __tagArrayToString(array $tags, $delimiter, $ordered){
			if(empty($tags)) return NULL;
			if ($ordered != 'yes') {
			  sort($tags);
			}
			return implode($delimiter . ' ', $tags);
		}
	
	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/
	
		function displaySettingsPanel(&$wrapper, $errors=NULL){
			
			Field::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			
			// build suggestion list
			$label = Widget::Label(__('Suggestion List'));
			
			$sectionManager = new SectionManager($this->_engine);
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'name');
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
			$div->appendChild($label);
			
			$label = Widget::Label('Tag List Options');
			$div->appendChild($label);
			
			// Suggestion threshold
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][pre_populate_min]',($this->get('pre_populate_min') ? $this->get('pre_populate_min') : '' ));
			$input->setAttribute('size', '3');
			$label->setValue(__('Only suggest a tag if it has been used at least %s times', array($input->generate())));
			$div->appendChild($label);
			
			// Custom delimiter
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][delimiter]', ($this->get('delimiter') ? $this->get('delimiter') : ',' ));
			$input->setAttribute('size', '5');
			$label->setValue(__('Use %s as a delimiter to separate tags', array($input->generate())));
			$div->appendChild($label);
			
			// Preserve order option
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][ordered]', 'yes', 'checkbox');
			if($this->get('ordered') == 'yes') $input->setAttribute('checked', 'checked');     
			$label->setValue($input->generate() . 'Preserve list order');
			$div->appendChild($label);
			$wrapper->appendChild($div);
			
			// External Suggestions
			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$label = Widget::Label('External Source URL <i>Optional</i>');
			$input = Widget::Input('fields['.$this->get('sortorder').'][external_source_url]', ($this->get('external_source_url') ? $this->get('external_source_url') : ''));
			$label->appendChild($input);
			$div->appendChild($label);
			
			$label = Widget::Label('External Source XPath <i>Optional</i>');
			$input = Widget::Input('fields['.$this->get('sortorder').'][external_source_path]', ($this->get('external_source_path') ? $this->get('external_source_path') : ''));
			$label->appendChild($input);
			$div->appendChild($label);
			$wrapper->appendChild($div);
			
			// Validation
			$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]');
			
			$this->appendShowColumnCheckbox($wrapper);
						
		}
		
		function commit(){

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
					
			$this->_engine->Database->query("
				DELETE FROM
				`tbl_fields_enhancedtaglist`
				WHERE
				`field_id` = '$id'
				LIMIT 1
			");
      
			return $this->_engine->Database->insert($fields, 'tbl_fields_enhancedtaglist');
					
		}
		
	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/
	
		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){

			$value = NULL;
			if(isset($data['value'])){
				$value = (is_array($data['value']) ? self::__tagArrayToString($data['value'], $this->get('delimiter'), $this->get('ordered')) : $data['value']);
			}
			
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
		
	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/
	
		function processRawFieldData($data, &$status, $simulate=false, $entry_id=NULL){
						
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
		
		function prepareTableValue($data, XMLElement $link=NULL){
			
			if(!is_array($data) || empty($data)) return;
			
			$value = NULL;
			if(isset($data['value'])){
				$value = (is_array($data['value']) ? self::__tagArrayToString($data['value'], $this->get('delimiter'), $this->get('ordered')) : $data['value']);
			}

			return parent::prepareTableValue(array('value' => General::sanitize($value)), $link);
		}
		
	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');
			
			if (self::isFilterRegex($data[0])) {
				$this->_key++;
				$pattern = str_replace('regexp:', '', $this->cleanValue($data[0]));
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.value REGEXP '{$pattern}'
						OR t{$field_id}_{$this->_key}.handle REGEXP '{$pattern}'
					)
				";
				
			} elseif ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND (
							t{$field_id}_{$this->_key}.value = '{$value}'
							OR t{$field_id}_{$this->_key}.handle = '{$value}'
						)
					";
				}
				
			} else {
				if (!is_array($data)) $data = array($data);
				
				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}
				
				$this->_key++;
				$data = implode("'" . $this->get('delimiter') . " '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.value IN ('{$data}')
						OR t{$field_id}_{$this->_key}.handle IN ('{$data}')
					)
				";
			}
			
			return true;
		}
		
	/*-------------------------------------------------------------------------
		Sorting:
	-------------------------------------------------------------------------*/
		
		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "INNER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = "ORDER BY `e`.`id` $order";
		}
	}
