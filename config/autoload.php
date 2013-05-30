<?php

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2013 Leo Feyer
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
	'easyupdate3' => 'system/modules/easyupdate3/easyupdate3.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'be_easyupdate3' => 'system/modules/easyupdate3/templates',
));
