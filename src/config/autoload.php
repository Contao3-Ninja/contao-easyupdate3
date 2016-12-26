<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Easyupdate3
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'BugBuster',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	'easyupdate3'                                  => 'system/modules/easyupdate3/modules/easyupdate3.php',
	// Classes
	'BugBuster\EasyUpdate3\ea3ServerCommunication' => 'system/modules/easyupdate3/classes/ea3ServerCommunication.php',
	'BugBuster\EasyUpdate3\ea3ClientDownloader'    => 'system/modules/easyupdate3/classes/ea3ClientDownloader.php',
	'BugBuster\EasyUpdate3\ea3ClientRuntime'       => 'system/modules/easyupdate3/classes/ea3ClientRuntime.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'be_easyupdate3' => 'system/modules/easyupdate3/templates',
));
