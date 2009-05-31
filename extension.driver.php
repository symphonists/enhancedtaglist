<?php

	Class extension_enhancedtaglist extends Extension{
	
		public function about(){
			return array('name' => 'Field: Enhanced Tag List',
						 'version' => '1.2',
						 'release-date' => '2009-06-01',
						 'author' => array('name' => 'craig zheng',
										   'email' => 'cz@mongrl.com')
				 		);
		}
		
		public function uninstall(){
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_enhancedtaglist`");
		}

		public function update($previousVersion){	
			if(version_compare($previousVersion, '1.2', '<')){
				$this->_Parent->Database->query("ALTER TABLE `tbl_fields_enhancedtaglist` 
					ADD `external_source_url` varchar(255) default NULL,
					ADD `external_source_path` varchar(255) default NULL");
				if(version_compare($previousVersion, '1.1', '<')){
					$this->_Parent->Database->query("ALTER TABLE `tbl_fields_enhancedtaglist` ADD `delimiter` VARCHAR(5) NOT NULL DEFAULT ','");
				}
			}
			return true;
		}

		public function install(){

			return $this->_Parent->Database->query("CREATE TABLE 		
				`tbl_fields_enhancedtaglist` (
				`id` int(11) unsigned NOT NULL auto_increment,
				`field_id` int(11) unsigned NOT NULL,
				`validator` varchar(100) default NULL,
				`pre_populate_source` varchar(255) default NULL,
				`pre_populate_min` int(11) unsigned NOT NULL,
				`external_source_url` varchar(255) default NULL,
				`external_source_path` varchar(255) default NULL,
				`ordered` enum('yes','no') NOT NULL default 'no',
				`delimiter` varchar(5) NOT NULL default ',',
				PRIMARY KEY  (`id`),
				KEY `field_id` (`field_id`)
			) TYPE=MyISAM;");

		}
			
	}
