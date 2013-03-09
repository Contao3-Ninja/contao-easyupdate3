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
 * 
 * @copyright	Copyright easyupdate 2009
 * @author		Lutz Schoening
 * @author		Glen Langer - offline fork 
 * @package		easyupdate
 * @license		LGPL
 */
class easyupdate3 extends \BackendModule 
{
	// template var
	protected $strTemplate = 'be_easyupdate3';
	protected $IMPORT;
	
	protected function compile() 
	{
		ini_set("memory_limit", "64M");
		// Contao 3 Hack auf die Schnelle
		$this->import('Input');
		$this->import('Environment');
		// \\
		$archive = $this->Input->GET('filename');
		$task = ($archive == 'n.a.' ? 0 : $this->Input->GET('task'));
		$config_post = $this->Input->POST('config');
		$this->Template->referer = $this->getReferer(ENCODE_AMPERSANDS);
		$this->Template->backTitle = specialchars($GLOBALS['TL_LANG']['easyupdate3']['backBT']);
		$this->Template->headline = sprintf($GLOBALS['TL_LANG']['easyupdate3']['headline'], VERSION . '.' . BUILD);
		switch ($task) 
		{
			case 1 :
				$this->Template->ModuleList = $this->showInformation($archive, $config_post);
				break;
			case 2 :
				$this->Template->ModuleList = $this->showChangelog($archive);
				break;
			case 3 :
				$this->Template->ModuleList = $this->listfiles($archive);
				break;
			case 4 :
				$this->Template->ModuleList = $this->backupfiles($archive);
				break;
			case 5 :
				$this->Template->ModuleList = $this->copyfiles($archive);
				break;
			default :
				$this->Template->ModuleFile = $this->getFiles();
				break;
		}
	}
	
	
    /**
	 * get the file select box and the readme list
	 * @return string
	 */
	protected function getFiles() 
	{
		$real_path = TL_ROOT . '/files/easyupdate3';
		if (is_dir($real_path)) 
		{
			foreach (scandir($real_path, TRUE) as $file) 
			{
				// remove hidden files and add only the zip files
				if ($file[0] != '.' && substr($file, -3) == 'zip' && substr($file, 0, 3) != 'bak') 
				{
					$strAllFiles .= sprintf('<option value="%s">%s</option>', $file, $file);
				}
			}
		}
		if (!$strAllFiles)
			$strAllFiles .= sprintf('<option value="%s">%s</option>', 'n.a.', 'n.a.');
		$real_path = $real_path . '/backup';
		if (is_dir($real_path)) 
		{
			foreach (scandir($real_path, TRUE) as $file) 
			{
				// remove hidden files and add only the zip files
				if ($file[0] != '.' && substr($file, -3) == 'zip' && substr($file, 0, 3) == 'bak')
					$strAllBackups .= sprintf('<option value="%s">%s</option>', $file, $file);
			}
			if ($strAllBackups) 
			{
				$strAllFiles = '<optgroup label=" ' . $GLOBALS['TL_LANG']['easyupdate3']['files']['original'] . '">' . $strAllFiles . '</optgroup>';
				$strAllBackups = '<optgroup label=" ' . $GLOBALS['TL_LANG']['easyupdate3']['files']['backup'] . '">' . $strAllBackups . '</optgroup>';
			}
		}
		$return .= '<form action="' . ampersand($this->Environment->request) . '" name="tl_select_file" class="tl_form" method="GET">';
		$return .= '<div class="tl_formbody_edit"><div class="tl_tbox">';
		$return .= '<h3><label for="ctrl_original">' . $GLOBALS['TL_LANG']['easyupdate3']['selectfile'] . '</label></h3>';
		$return .= '<input type="hidden" name="do" value="easyupdate3">';
		$return .= '<input type="hidden" name="task" value="1">';
		$return .= '<select name="filename" id="ctrl_original" class="tl_select" onfocus="Backend.getScrollOffset();">' . $strAllFiles . $strAllBackups . '</select> ';
		$return .= '<input type="submit" class="tl_submit" alt="select a file" accesskey="s" value="' . specialchars($GLOBALS['TL_LANG']['easyupdate3']['setfile']) . '" />';
		$return .= '<p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['easyupdate3']['description'] . '</p></form>';
		$return .= '<h2><span style="color:#CC5555;">' . $GLOBALS['TL_LANG']['easyupdate3']['readme']['headline'] . '</span></h2>';
		$return .= $GLOBALS['TL_LANG']['easyupdate3']['readme']['text1'];
		$return .= $GLOBALS['TL_LANG']['easyupdate3']['readme']['text2'];
		$return .= '<div style="float:left; width:50%" >' . $GLOBALS['TL_LANG']['easyupdate3']['readme']['text3']['left'] . '</div>';
		$return .= '<div style="float:left;">' . $GLOBALS['TL_LANG']['easyupdate3']['readme']['text3']['right'] . '</div>';
		$return .= '<div style="clear:both;"></div>';
		$return .= $GLOBALS['TL_LANG']['easyupdate3']['readme']['text4'];
		$return .= '</div></div>';
		return $return;
	}
	
	/**
	 * get the version number and compare it
	 * @param string $archive
	 * @param array $config_post
	 * @return string
	 */
	protected function showInformation($archive, $config_post) 
	{
		$archive = 'files/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$config = $config_post ? $config_post['files'] : unserialize($GLOBALS['TL_CONFIG']['easyupdate3']);
		if ($config_post) 
		{
			$update = $config_post['update'];
			$this->IMPORT = unserialize($config_post['import']);
		} 
		else 
		{
			$objArchive = new ZipReaderTL($archive);
			$arrFiles = $objArchive->getFileList();
			$i = strpos($arrFiles[0], '/') + 1;
			array_shift($arrFiles);
			while ($objArchive->next()) 
			{
				$strfile = substr($objArchive->file_name, $i);
				if ($strfile == 'system/config/constants.php') 
				{
					$constants = ($objArchive->unzip());
					break;
				}
			}
			// get the version an build number
			$this->IMPORT = $this->getVersionAndBuild($constants);
			// check the both version
			$update = $this->checkVersion($this->IMPORT);
		}
		$return .= '<div style="width:700px; margin:0 auto;">';
		$return .= '<div style="float:right; width:60%;">';
		$return .= '<h2 style="padding:0px 0px 0px 10px;">' . sprintf($GLOBALS['TL_LANG']['easyupdate3']['updatex'], VERSION . '.' . BUILD, $this->IMPORT['VERSION'] . '.' . $this->IMPORT['BUILD']) . '</h2>';
		switch ($update) 
		{
			case 1 :
				$return .= '<div class="tl_confirm">' . $GLOBALS['TL_LANG']['easyupdate3']['update1'] . '</div>';
				break;
			case 2 :
				$return .= '<div class="tl_new">'     . $GLOBALS['TL_LANG']['easyupdate3']['update2'] . '</div>';
				break;
			default :
				$return .= '<div class="tl_error">'   . $GLOBALS['TL_LANG']['easyupdate3']['update0'] . '</div>';
				break;
		}
		$real_path = $this->Environment->documentRoot . $this->Environment->path . '/system/config';
		$strConfig .= "<br><b>&nbsp;" . $GLOBALS['TL_LANG']['easyupdate3']['config_legend'] . "</b><br>";
		if (is_dir($real_path)) 
		{
			$intall = $intcheck = 0;
			foreach (scandir($real_path) as $file) {
				// remove hidden files and add only the zip files
				if ($file[0] != '.' && substr($file, -3) == 'php') 
				{
					$intall++;
					$checked = $config[$file] == 1 ? checked : '';
					$intcheck = $checked == checked ? ++ $intcheck : $intcheck;
					$strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][%s]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file, $file);
				}
			}
		}
		$strConfig .= "<br><b>&nbsp;" . $GLOBALS['TL_LANG']['easyupdate3']['other_legend'] . "</b><br>";
		$file = "robots.txt";
		if (is_file($this->Environment->documentRoot . $this->Environment->path . "/" . $file)) 
		{
			$checked = $config[$file] == 1 ? checked : '';
			$strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][%s]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file, $file);
		}
		// add by BugBuster
		$file = "tinymce.css";
		if (is_file($this->Environment->documentRoot . $this->Environment->path . "/files/" . $file))
		{
		    $checked = $config[$file] == 1 ? checked : '';
		    $strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][%s]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file, 'files/'.$file);
		}
		$file = $GLOBALS['TL_LANG']['easyupdate3']['demo'];
		$checked = $config[demo] == 1 ? checked : '';
		$strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][demo]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file);
		// add the exclude files to the config
		if ($GLOBALS['TL_CONFIG']['easyupdate3']) 
		{
			if (sizeof($config))
				$this->Config->update("\$GLOBALS['TL_CONFIG']['easyupdate3']", serialize($config));
			else
				$this->Config->delete("\$GLOBALS['TL_CONFIG']['easyupdate3']", 1);
		} 
		else
			$this->Config->add("\$GLOBALS['TL_CONFIG']['easyupdate3']", serialize($config));
		
		$return .= '</div><div style="float:left; width:40%;">';
		$return .= '<h2>' . $GLOBALS['TL_LANG']['easyupdate3']['noupdate'] . '</h2>';
		$return .= '<form action="' . ampersand($this->Environment->request) . '" name="tl_select_config" class="tl_form" method="POST">';
		$return .= '<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">';
		$return .= '<input type="hidden" name="config[update]" value="' . $update . '">';
		$return .= '<input type="hidden" name="config[import]" value="' . htmlentities(serialize($this->IMPORT)) . '">';
		$id = "'config'";
		$return .= '<input type="checkbox" onChange="Backend.toggleCheckboxes(this, ' . $id . ');document.tl_select_config.submit();"' . ($intall == $intcheck ? checked : '') . '/><label style="color:#a6a6a6;">' . $GLOBALS['TL_LANG']['easyupdate3']['all'] . '</label><br>';
		$return .= $strConfig;
		$return .= '</form></div><div style="clear:both;"></div><br><p class="tl_info" style="height: 26px;">' . $GLOBALS['TL_LANG']['easyupdate3']['noupdatetext'] . '</p>';
		$return .= '';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . $this->Environment->base . 'contao/main.php?do=easyupdate3" style="float:left;">&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</a>';
		$return .= '<a href="' . str_replace('task=1', 'task=2', $this->Environment->base . $this->Environment->request) . '" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
	
	/**
	 * showChangelog
	 * @param string $archive
	 * @return string
	 */
	protected function showChangelog($archive) 
	{
		$archive = 'files/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$objArchive = new ZipReaderTL($archive);
		$arrFiles = $objArchive->getFileList();
		$i = strpos($arrFiles[0], '/') + 1;
		array_shift($arrFiles);
		while ($objArchive->next()) 
		{
			$strfile = substr($objArchive->file_name, $i);
			if ($strfile == 'system/config/constants.php') 
			{
				$constants = ($objArchive->unzip());
				break;
			}
		}
		$objArchive->reset();
		// get the version an build number
		$this->IMPORT = $this->getVersionAndBuild($constants);
		// check the both version
		$update = $this->checkVersion($this->IMPORT);
		switch ($update) 
		{
			case 1 :
				while ($objArchive->next()) 
				{
					$strfile = substr($objArchive->file_name, $i);
					if ($strfile == 'system/docs/CHANGELOG.md' && $update != 0) 
					{
						$changelog = explode("\n", htmlentities($objArchive->unzip()));
						break;
					}
				}
				break;
			case 2 :
				$text = $GLOBALS['TL_LANG']['easyupdate3']['changelog']['same'];
				break;
			default :
				$objFile = new File('system/docs/CHANGELOG.md');
				$changelog = explode("\n", htmlentities($objFile->getContent()));
				$objFile->close();
				break;
		}
		if ($update != 2) 
		{
			if (sizeof($changelog) > 1) 
			{
				$pos1 = $pos2 = 0;
				foreach ($changelog as $i => $text) 
				{
					if (substr_count($text, 'Version') & !$pos2)
						$pos2 = $i;
					if (substr_count($text, VERSION . '.' . BUILD))
						$pos1 = $i;
					if (substr_count($text, $this->IMPORT['VERSION'] . '.' . $this->IMPORT['BUILD']) && $this->IMPORT)
						$pos2 = $i;
				}
				$i = ($pos1 < $pos2 ? $pos1 : $pos2);
				$m = ($pos1 > $pos2 ? $pos1 : $pos2);
				for ($i; $i < $m; $i++)
					$text .= $changelog[$i] . '<br>';
			} 
			else
				$text = $GLOBALS['TL_LANG']['easyupdate3']['changelog']['no'];
		}
		$return .= '<div style="width:700px; margin:0 auto;">';
		$return .= '<h1 style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">';
		$return .= sprintf($GLOBALS['TL_LANG']['easyupdate3']['changelog']['headline'], $this->IMPORT['VERSION'] . '.' . $this->IMPORT['BUILD'], VERSION . '.' . BUILD) . '</h1>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; height:500px; padding:0px 20px 0px 10px; overflow:auto; background:#eee; border:1px solid #999;">';
		$return .= $text . '</div>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . $this->Environment->base . 'contao/main.php?do=easyupdate3" style="float:left;">&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</a>';
		$return .= '<a href="' . str_replace('task=2', 'task=3', $this->Environment->base . $this->Environment->request) . '" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
		$return .= '</div></div>';
		return $return;
	}

	/**
	 * list the files
	 * @param string $archive
	 * @return string
	 */
	protected function listfiles($archive) 
	{
		$archive = 'files/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$objArchive = new ZipReaderTL($archive);
		$arrFiles = $objArchive->getFileList();
		$i = strpos($arrFiles[0], '/') ? strpos($arrFiles[0], '/') + 1 : 0;
		$return .= '<div style="width:700px; margin:0 auto;">';
		$return .= '<h1 style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">' . $GLOBALS['TL_LANG']['easyupdate3']['content'] . '</h1>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; height:500px; overflow:auto; background:#eee; border:1px solid #999;"><ol style="margin-top:0px">';
		while ($objArchive->next()) 
		{
			$strfile = substr($objArchive->file_name, $i);
			$return .= '<li>' . $strfile . '</li>';
		}
		$return .= '</ol></div>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . $this->Environment->base . 'contao/main.php?do=easyupdate3" style="float:left;">&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</a>';
		$return .= '<a href="' . str_replace('task=3', 'task=4', $this->Environment->base . $this->Environment->request) . '" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}

	/**
	 * backup the current files
	 * @param string $archive
	 */
	protected function backupfiles($archive) 
	{
		$archive = 'files/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$objArchive = new ZipReaderTL($archive);
		$return .= '<div style="width:700px; margin:0 auto;">';
		$return .= '<h1 style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">' . $GLOBALS['TL_LANG']['easyupdate3']['backup'] . '</h1>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; height:500px; overflow:auto; background:#eee; border:1px solid #999;"><ol style="margin-top:0px">';
		$arrFiles = $objArchive->getFileList();
		$i = strpos($arrFiles[0], '/') + 1;
		$objBackup = new ZipWriterTL('files/easyupdate3/backup/bak' . date('YmdHi') . '-' . VERSION . '.' . BUILD . '.zip');
		$rootpath = 'contao-' . VERSION . '.' . BUILD . '/';
		
		foreach ($arrFiles as $strFile) 
		{
			$strFile = substr($strFile, $i);
			if ($strFile == 'system/runonce.php') 
			{
				continue;
			}
			try 
			{
				$objBackup->addFile($strFile, $rootpath . $strFile);
				$return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['backuped'] . $strFile . '</li>';
			} 
			catch (Exception $e) 
			{
				$return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile . ' (' . $e->getMessage() . ')</li>';
			}
		}
		$objBackup->close();
		$return .= '</ol></div>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . $this->Environment->base . 'contao/main.php?do=easyupdate3" style="float:left;">&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</a>';
		$return .= '<a href="' . str_replace('task=4', 'task=5', $this->Environment->base . $this->Environment->request) . '" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}

	/**
	 * unzip and copy the files
	 * @param unknown_type $archive
	 * @return string
	 */
	protected function copyfiles($archive) 
	{
		$archive = 'files/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$config  = unserialize($GLOBALS['TL_CONFIG']['easyupdate3']);
		if ($config) 
		{
			foreach ($config as $key => $value) 
			{
				switch ($key) 
				{
					case ("demo") :
						$exclude['basic.css'] = $value;
						$exclude['print.css'] = $value;
						$exclude['music_academy.css'] = $value;
						$exclude['templates/example_website.sql'] = $value;
						break;
					case ("robots.txt") :
						$exclude[$key] = $value;
						break;
					// add by BugBuster
					case ("tinymce.css") :
					    $exclude['files/' . $key] = $value;
					    break;
					default :
						$exclude['system/config/' . $key] = $value;
						break;
				}
			}
		}
		$objArchive = new ZipReaderTL($archive);
		$arrFiles = $objArchive->getFileList();
		$i = strpos($arrFiles[0], '/') + 1;
		$return .= '<div style="width:700px; margin:0 auto;">';
		$return .= '<h1 style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">' . $GLOBALS['TL_LANG']['easyupdate3']['update'] . '</h1>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; height:500px; overflow:auto; background:#eee; border:1px solid #999;"><ol style="margin-top:0px">';
		// Unzip files
		while ($objArchive->next()) 
		{
			$strFile = substr($objArchive->file_name, $i);
			if ($exclude[$strFile]) 
			{
				$return .= '<li style="color:#2500ff;">' . $GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile . ': ' . $GLOBALS['TL_LANG']['easyupdate3']['exclude'] . '</li>';
				continue;
			}
			try 
			{
				$objFile = new File($strFile);
				$test = $objArchive->current();
				$objFile->write($objArchive->unzip());
				$objFile->close();
				$return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['updated'] . $strFile . '</li>';
			} 
			catch (Exception $e) 
			{
				$return .= '<li style="color:#ff0000;">' . $GLOBALS['TL_LANG']['easyupdate3']['error'] . $strFile . ': ' . $e->getMessage() . '</li>';
			}
		}
		// Add log entry
		$this->log('localremote update completed', 'easyupdate getFiles(), listfiles(), backupfiles(), copyfiles()', TL_GENERAL);
		$return .= '</ol></div>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . $this->Environment->base . 'contao/install.php" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}

	/**
	 * get the version and build from the constants.php
	 * @param string $temp
	 */
	protected function getVersionAndBuild($temp) 
	{
		foreach (explode("\n", $temp) as $text) 
		{
			if (substr_count($text, 'VERSION')) 
			{
				$pos_v = strpos($text, "'", strpos($text, ",")) + 1;
				$IMPORT_VERSION = substr($text, $pos_v, strrpos($text, "'") - $pos_v);
			}
			if (substr_count($text, 'BUILD')) 
			{
				$pos_b = strpos($text, "'", strpos($text, ",")) + 1;
				$IMPORT_BUILD = substr($text, $pos_b, strrpos($text, "'") - $pos_b);
			}
		}
		$IMPORT['VERSION'] = $pos_v ? $IMPORT_VERSION : 'X.X';
		$IMPORT['BUILD'] = $pos_b ? $IMPORT_BUILD : 'X';
		return $IMPORT;
	}

	/**
	 * check if new, same or old version
	 * @param array $IMPORT
	 * @return    integer    0 => old
	 *                       1 => News
	 *                       2 => same
	 */
	protected function checkVersion($IMPORT) 
	{
		$BUILD = BUILD;
		$VERSION = explode(".", VERSION);
		$VERSION_IMPORT = explode(".", $IMPORT['VERSION']);
		$BUILD_IMPORT = $IMPORT['BUILD'];
		if ($VERSION[0] > $VERSION_IMPORT[0]) 
		{
			$update = 0;
		}
		elseif ($VERSION[0] < $VERSION_IMPORT[0]) 
		{
			$update = 1;
		} 
		else 
		{
			if ($VERSION[1] > $VERSION_IMPORT[1]) 
			{
				$update = 0;
			}
			elseif ($VERSION[1] < $VERSION_IMPORT[1]) 
			{
				$update = 1;
			} 
			else 
			{
				if ($BUILD > $BUILD_IMPORT) 
				{
					$update = 0;
				}
				elseif ($BUILD < $BUILD_IMPORT) 
				{
					$update = 1;
				} 
				else 
				{
					$update = 2;
				}
			}
		}
		return $update;
	}
}
