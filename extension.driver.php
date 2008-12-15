<?php

	Class extension_enhancedtaglist extends Extension{
	
		public function about(){
			return array('name' => 'Field: Enhanced Tag List',
						 'version' => '1.0',
						 'release-date' => '2008-12-14',
						 'author' => array('name' => 'craig zheng',
										   'website' => 'http://mongrl.com',
										   'email' => 'cz@mongrl.com')
				 		);
		}
		
		public function uninstall(){
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_enhancedtaglist`");
		}


		public function install(){

			return $this->_Parent->Database->query("CREATE TABLE `tbl_fields_enhancedtaglist` (
		 	  `id` int(11) unsigned NOT NULL auto_increment,
			  `field_id` int(11) unsigned NOT NULL,
			  `validator` varchar(100) default NULL,
        `pre_populate_source` varchar(255) default NULL,
        `pre_populate_min` int(11) unsigned NOT NULL,
        `ordered` enum('yes','no') NOT NULL default 'no',
			  PRIMARY KEY  (`id`),
			  KEY `field_id` (`field_id`)
			) TYPE=MyISAM;");

		}
			
	}