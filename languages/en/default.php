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
 * @author		Glen Langer - offline fork 
 * @package		easyupdate
 * @license		LGPL
 */
$GLOBALS['TL_LANG']['MOD']['easyupdate3'] = array (
	'easyUpdate3',
	'Update Contao 3 from the Backend'
);
$GLOBALS['TL_LANG']['easyupdate3']['backBT'] = 'Go back';
$GLOBALS['TL_LANG']['easyupdate3']['headline'] = 'easyUpdate your Contao version (current version: %s)';
$GLOBALS['TL_LANG']['easyupdate3']['selectfile'] = 'Please select an archive (ZIP-file)';
$GLOBALS['TL_LANG']['easyupdate3']['description'] = 'Please select an archive from the directory (tl_files/easyupdate). The script do not search sub folders an accepted only ZIP-files.';
$GLOBALS['TL_LANG']['easyupdate3']['setfile'] = 'select';
$GLOBALS['TL_LANG']['easyupdate3']['files']['original'] = 'Original files';
$GLOBALS['TL_LANG']['easyupdate3']['files']['backup'] = 'Backup files';
$GLOBALS['TL_LANG']['easyupdate3']['noupdate'] = 'Exclude files for update';
$GLOBALS['TL_LANG']['easyupdate3']['demo'] = 'Demo Files';
$GLOBALS['TL_LANG']['easyupdate3']['config_legend'] = 'Config Files';
$GLOBALS['TL_LANG']['easyupdate3']['other_legend'] = 'other Files';
$GLOBALS['TL_LANG']['easyupdate3']['all'] = 'Select all';
$GLOBALS['TL_LANG']['easyupdate3']['noupdatetext'] = 'You can select files which are not updated.<br>But the backup catch also these files.';
$GLOBALS['TL_LANG']['easyupdate3']['updatex'] = 'version (%s) ======> version (%s)';
$GLOBALS['TL_LANG']['easyupdate3']['update0'] = 'You will install an older version of Contao. This is not impossible, are you sure? So an update to a lower version can be become not so easy.';
$GLOBALS['TL_LANG']['easyupdate3']['update1'] = 'You will install a newer version of Contao.';
$GLOBALS['TL_LANG']['easyupdate3']['update2'] = 'You will install the same version .';
$GLOBALS['TL_LANG']['easyupdate3']['next'] = 'Next step';
$GLOBALS['TL_LANG']['easyupdate3']['previous'] = 'Back to home';
$GLOBALS['TL_LANG']['easyupdate3']['changelog']['headline'] = 'Changelog between the version %s and %s';
$GLOBALS['TL_LANG']['easyupdate3']['changelog']['same'] = 'You try to install the same version. (Notice: There are no changes.)';
$GLOBALS['TL_LANG']['easyupdate3']['changelog']['no'] = 'The changelog is unable to read. For more information you can ask in the Contao-Community (http://www.contao.org).';
$GLOBALS['TL_LANG']['easyupdate3']['content'] = 'Content of the archive';
$GLOBALS['TL_LANG']['easyupdate3']['backup'] = 'Backup your files';
$GLOBALS['TL_LANG']['easyupdate3']['backuped'] = 'Backed up: ';
$GLOBALS['TL_LANG']['easyupdate3']['update'] = 'Update your files';
$GLOBALS['TL_LANG']['easyupdate3']['updated'] = 'Updated: ';
$GLOBALS['TL_LANG']['easyupdate3']['skipped'] = 'Skipped: ';
$GLOBALS['TL_LANG']['easyupdate3']['error'] = 'Error: ';
$GLOBALS['TL_LANG']['easyupdate3']['exclude'] = 'exclude';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['headline'] = 'Please read it complete before use';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['text1'] = '<h2>The update have 4 steps</h2> 
														<ul><li>List the files in the archive</li>
														<li>Backup your current files</li>
														<li>Update the files</li>
														<li>Run the install tool</li></ul>
														It is possible that you must update the Contao database, in this case you see the install tool.
														If you have delete the config file, the password for the install tool is: "contao"';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['text2'] = '<h2>Security notice</h2>
														<ul><li>Nothing is 100% secure. Be carefull with this tool.</li>
														<li>You should make a database backup before run this tool the first time.</li>
														<li>This tool change the file system, a data loss is possible.</li>
														<li>You make all on your own risk.</li></ul>';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['text3']['left'] = '<h2>Check and run</h2>
																<ul><li>3.0.0 ====> 3.0.1</li>
																</ul>';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['text3']['right'] = '<h2>Check and error</h2>
																<ul><li>2.x.x ====> 2.x.x</li></ul>';
$GLOBALS['TL_LANG']['easyupdate3']['readme']['text4'] = 'The installation until to the Contao version 3.0.1 was checked. Not possible with Contao 2.x!.
														If you see as the version information X.X.X the ZIP-file could be damaged.';
