<?php

/**
 * TYPOlight webCMS
 * Copyright (C) 2005-2009 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 * PHP version 5
 * @copyright	Copyright easyupdate 2009
 * @author		Lutz Schoening
 * @package		easyupdate
 * @license		LGPL
 */
array_insert($GLOBALS['BE_MOD']['system'], 3, array (
	'easyupdate3' => array (
        'callback'  => 'easyupdate3',
        'icon'      => 'system/modules/easyupdate3/assets/icon.png',
		
	)
));

