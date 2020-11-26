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
array_insert($GLOBALS['BE_MOD']['system'], 3, array (
	'easyupdate3' => array (
        'callback'  => 'easyupdate3',
        'icon'      => 'system/modules/easyupdate3/assets/icon.png',
		
	)
));

//TODO
$GLOBALS['EA3SERVER']['TARGET'] = 'https://ea3server.contao.ninja/system/modules/easyupdate3_server/public/index.php';
