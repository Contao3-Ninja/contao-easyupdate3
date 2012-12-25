<?php

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2012 Leo Feyer
 * 
 * @package Easyupdate3
 * @link    http://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	'SystemTL'    => 'system/modules/easyupdate3/SystemTL.php',
	'ZipWriterTL' => 'system/modules/easyupdate3/ZipWriterTL.php',
	'easyupdate3' => 'system/modules/easyupdate3/easyupdate3.php',
	'ZipReaderTL' => 'system/modules/easyupdate3/ZipReaderTL.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'be_easyupdate3' => 'system/modules/easyupdate3/templates',
));
