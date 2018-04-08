<?php

	Class extension_enhancedtaglist extends Extension{

		public function uninstall(){
			return Symphony::Database()->query("DROP TABLE `tbl_fields_enhancedtaglist`");
		}

		public function update($previousVersion = false){
			if(version_compare($previousVersion, '1.2', '<')){
				Symphony::Database()->query("ALTER TABLE `tbl_fields_enhancedtaglist`
					ADD `external_source_url` varchar(255) default NULL,
					ADD `external_source_path` varchar(255) default NULL");

				if(version_compare($previousVersion, '1.1', '<')){
					Symphony::Database()->query("ALTER TABLE `tbl_fields_enhancedtaglist` ADD `delimiter` VARCHAR(5) NOT NULL DEFAULT ','");
				}
			}
			return true;
		}

		public function install(){
			return Symphony::Database()->query("CREATE TABLE
				`tbl_fields_enhancedtaglist` (
				`id` int(11) unsigned NOT NULL auto_increment,
				`field_id` int(11) unsigned NOT NULL,
				`validator` varchar(100) default NULL,
				`pre_populate_source` varchar(255) default NULL,
				`pre_populate_min` int(11) unsigned default 0,
				`external_source_url` varchar(255) default NULL,
				`external_source_path` varchar(255) default NULL,
				`ordered` enum('yes','no') NOT NULL default 'no',
				`delimiter` varchar(5) NOT NULL default ',',
				PRIMARY KEY  (`id`),
				KEY `field_id` (`field_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
		}

	}
