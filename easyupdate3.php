<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2014 Leo Feyer
 * 
 * Module easyupdate3
 * 
 * @copyright	Glen Langer
 * @author		Lutz Schoening (easyupdate for Contao 2)
 * @author		Glen Langer
 * @package		easyupdate
 * @license		LGPL
 */
class easyupdate3 extends \BackendModule 
{
	// template var
	protected $strTemplate = 'be_easyupdate3';
	protected $IMPORT;
	/*
	 * File list to delete
	 */
	protected $DELETE = array();
	
	protected function compile() 
	{
		@ini_set("memory_limit", "128M");
		// \\
		$archive = Input::get('filename');
		$task = ($archive == 'n.a.' ? 0 : Input::get('task'));
		$config_post = Input::post('config');
		$this->Template->referer   = $this->getReferer(ENCODE_AMPERSANDS);
		$this->Template->backTitle = specialchars($GLOBALS['TL_LANG']['easyupdate3']['backBT']);
		$this->Template->headline  = sprintf($GLOBALS['TL_LANG']['easyupdate3']['headline'], VERSION . '.' . BUILD);
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
			case 6 :
			    $this->Template->ModuleList = $this->deleteOldFiles($archive);
			    break;
			default :
				$this->Template->ModuleFile = $this->getFiles();
				break;
		}
	}
	
	/**
	 * reverse natsort
	 * @param array $files
	 */
	protected function rnatsort(&$files)
	{
        natsort($files);
        $files = array_reverse($files, false);
    }
    
    /**
	 * get the file select box and the readme list
	 * @return string
	 */
	protected function getFiles() 
	{
		$real_path = TL_ROOT . '/'. $GLOBALS['TL_CONFIG']['uploadPath'] .'/easyupdate3';
		if (is_dir($real_path)) 
		{
		    $files = scan($real_path);
		    $this->rnatsort($files);
		    
		    $setSelected = false;
			foreach ($files as $file) 
			{
			    $selected = "";
				// remove hidden files and add only the zip files
				if (substr($file,   -3) == 'zip' && 
				    substr($file, 0, 3) != 'bak' &&
                    substr($file,  -14) != 'delete.txt.zip') 
				{
				    if ( false === $setSelected &&
				         false !== strpos($file,'Contao_'.VERSION.'.'.BUILD.'-') ) 
				    {
				    	$selected =' selected="selected"';
				    	$setSelected = true;
				    }
					$strAllFiles .= sprintf('<option value="%s" %s>%s</option>', $file, $selected, $file);
				}
			}
		}
		if (!$strAllFiles)
		{
			$strAllFiles .= sprintf('<option value="%s">%s</option>', 'n.a.', 'n.a.');
		}
		$real_path = $real_path . '/backup';
		if (is_dir($real_path)) 
		{
		    $files = scan($real_path);
		    $this->rnatsort($files);
			foreach ($files as $file) 
			{
				// remove hidden files and add only the zip files
				if (substr($file, -3) == 'zip' && substr($file, 0, 3) == 'bak')
				{
				    $strAllBackups .= sprintf('<option value="%s">%s</option>', $file, $file);
				}
			}
			if ($strAllBackups) 
			{
				$strAllFiles   = '<optgroup label=" ' . $GLOBALS['TL_LANG']['easyupdate3']['files']['original'] . '">' . $strAllFiles   . '</optgroup>';
				$strAllBackups = '<optgroup label=" ' . $GLOBALS['TL_LANG']['easyupdate3']['files']['backup']   . '">' . $strAllBackups . '</optgroup>';
			}
		}
		$return .= '<form action="' . ampersand(Environment::get('request')) . '" name="tl_select_file" class="tl_form" method="GET">';
		$return .= '<div class="tl_formbody_edit"><div class="tl_tbox">';
		$return .= '<h3><label for="ctrl_original">' . $GLOBALS['TL_LANG']['easyupdate3']['selectfile'] . '</label></h3>';
		$return .= '<input type="hidden" name="do" value="easyupdate3">';
		$return .= '<input type="hidden" name="task" value="1">';
		$return .= '<select name="filename" id="ctrl_original" class="tl_select" onfocus="Backend.getScrollOffset();">' . $strAllFiles . $strAllBackups . '</select> ';
		$return .= '<input type="submit" class="tl_submit" alt="select a file" accesskey="s" value="' . specialchars($GLOBALS['TL_LANG']['easyupdate3']['setfile']) . '" />';
		$return .= '<p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['easyupdate3']['description'] . '</p></form>';
		$return .= '<h2><span style="color:#CC5555;">' . $GLOBALS['TL_LANG']['easyupdate3']['readme']['headline'] . '</span></h2>';
		$return .= '<h2>'.$GLOBALS['TL_LANG']['easyupdate3']['readme']['text1_title'].'</h2>';
		$return .= $GLOBALS['TL_LANG']['easyupdate3']['readme']['text1_text'];
		$return .= '<h2>'.$GLOBALS['TL_LANG']['easyupdate3']['readme']['text2_title'].'</h2>';
		$return .= $GLOBALS['TL_LANG']['easyupdate3']['readme']['text2_text'];
		$return .= '<div style="margin-top:9px;"   ><strong>' . $GLOBALS['TL_LANG']['easyupdate3']['readme']['text3']['working']   . '</strong></div>';
		$return .= '<div style="margin-bottom:9px;"><strong>' . $GLOBALS['TL_LANG']['easyupdate3']['readme']['text3']['incorrect'] . '</strong></div>';
		$return .= '<div></div>';
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
		$archive = $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$this->logSteps('#### Start easyUpdate3 ####', $archive);
		$this->logSteps('Show information', $archive);
		$this->logSteps('Update file selected: '.basename($archive), $archive);
		$config = $config_post ? $config_post['files'] : unserialize($GLOBALS['TL_CONFIG']['easyupdate3']);
		if ($config_post) 
		{
			$update = $config_post['update'];
			$this->IMPORT = unserialize($config_post['import']);
		} 
		else 
		{
			$objArchive = new \ZipReader($archive);
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
		$this->logSteps(sprintf($GLOBALS['TL_LANG']['easyupdate3']['updatex'], VERSION . '.' . BUILD, $this->IMPORT['VERSION'] . '.' . $this->IMPORT['BUILD']), $archive);
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
		$real_path = Environment::get('documentRoot') . Environment::get('path') . '/system/config';
		$strConfig .= "<br><b>&nbsp;" . $GLOBALS['TL_LANG']['easyupdate3']['config_legend'] . "</b><br>";
		if (is_dir($real_path)) 
		{
			$intall = $intcheck = 0;
			foreach (scan($real_path) as $file) 
			{
				// only a few php files
				if (   $file == 'ace.php'
                    || $file == 'agents.php'
                    || $file == 'tinyFlash.php'
                    || $file == 'tinyMCE.php'
                    || $file == 'tinyNews.php'
                   ) 
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
		if (is_file(Environment::get('documentRoot') . Environment::get('path') . "/" . $file)) 
		{
			$checked = $config[$file] == 1 ? checked : '';
			$strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][%s]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file, $file);
		}
		// add by BugBuster
		$file = "tinymce.css";
		if (is_file(Environment::get('documentRoot') . Environment::get('path') . "/".$GLOBALS['TL_CONFIG']['uploadPath']."/" . $file))
		{
		    $checked = $config[$file] == 1 ? checked : '';
		    $strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][%s]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file, $GLOBALS['TL_CONFIG']['uploadPath'] . '/'.$file);
		}

		//ab 3.3.1 kommt keine Demo mehr mit
		if (version_compare($this->IMPORT['VERSION'] .'.'. $this->IMPORT['BUILD'], '3.3.1', '<')) 
		{
            $file = $GLOBALS['TL_LANG']['easyupdate3']['demo'];
            $checked = $config[demo] == 1 ? checked : '';
    		$strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][demo]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file);
		}

		// add the exclude files to the config
		if ($GLOBALS['TL_CONFIG']['easyupdate3']) 
		{
			if (sizeof($config))
			{
			    $this->Config->update("\$GLOBALS['TL_CONFIG']['easyupdate3']", serialize($config));
			}
			else
			{
			    $this->Config->delete("\$GLOBALS['TL_CONFIG']['easyupdate3']", 1);
			}
		} 
		else
		{
		    $this->Config->add("\$GLOBALS['TL_CONFIG']['easyupdate3']", serialize($config));
		}
		
		$return .= '</div><div style="float:left; width:40%;">';
		$return .= '<h2>' . $GLOBALS['TL_LANG']['easyupdate3']['noupdate'] . '</h2>';
		$return .= '<form action="' . ampersand(Environment::get('request')) . '" name="tl_select_config" class="tl_form" method="POST">';
		$return .= '<input type="hidden" name="REQUEST_TOKEN"  value="' . REQUEST_TOKEN.'">';
		$return .= '<input type="hidden" name="config[update]" value="' . $update . '">';
		$return .= '<input type="hidden" name="config[import]" value="' . htmlentities(serialize($this->IMPORT)) . '">';
		$id = "'config'";
		$return .= '<input type="checkbox" onChange="Backend.toggleCheckboxes(this, ' . $id . ');document.tl_select_config.submit();"' . ($intall == $intcheck ? checked : '') . '/><label style="color:#a6a6a6;">' . $GLOBALS['TL_LANG']['easyupdate3']['all'] . '</label><br>';
		$return .= $strConfig;
		$return .= '</form></div><div style="clear:both;"></div><br><p class="tl_info" style="height: 26px;">' . $GLOBALS['TL_LANG']['easyupdate3']['noupdatetext'] . '</p>';
		$return .= '';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . Environment::get('base') . 'contao/main.php?do=easyupdate3" style="float:left;">&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</a>';
		$return .= '<a href="' . str_replace('task=1', 'task=2', Environment::get('base') . Environment::get('request')) . '" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
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
		$archive = $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$this->logSteps('Show changelog', $archive);
		$objArchive = new \ZipReader($archive);
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
					{
					    $pos2 = $i;
					}
					if (substr_count($text, VERSION . '.' . BUILD))
					{
					    $pos1 = $i;
					}
					if (substr_count($text, $this->IMPORT['VERSION'] . '.' . $this->IMPORT['BUILD']) && $this->IMPORT)
					{
					    $pos2 = $i;
					}
				}
				$i = ($pos1 < $pos2 ? $pos1 : $pos2);
				$m = ($pos1 > $pos2 ? $pos1 : $pos2);
				for ($i; $i < $m; $i++)
				{
				    $text .= $changelog[$i] . '<br>';
				}
			} 
			else
			{
			    $text = $GLOBALS['TL_LANG']['easyupdate3']['changelog']['no'];
			}
		}
		$return .= '<div style="width:700px; margin:0 auto;">';
		$return .= '<h1 style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">';
		$return .= sprintf($GLOBALS['TL_LANG']['easyupdate3']['changelog']['headline'], $this->IMPORT['VERSION'] . '.' . $this->IMPORT['BUILD'], VERSION . '.' . BUILD) . '</h1>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; height:400px; padding:0px 20px 0px 10px; overflow:auto; background:#eee; border:1px solid #999;">';
		$return .= $text . '</div>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . Environment::get('base') . 'contao/main.php?do=easyupdate3" style="float:left;">&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</a>';
		$return .= '<a href="' . str_replace('task=2', 'task=3', Environment::get('base') . Environment::get('request')) . '" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
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
		$archive = $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$this->logSteps('List files', $archive);
		$objArchive = new \ZipReader($archive);
		$arrFiles = $objArchive->getFileList();
		$i = strpos($arrFiles[0], '/') ? strpos($arrFiles[0], '/') + 1 : 0;
		$return .= '<div style="width:700px; margin:0 auto;">';
		$return .= '<h1 style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">' . $GLOBALS['TL_LANG']['easyupdate3']['content'] . '</h1>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; height:400px; overflow:auto; background:#eee; border:1px solid #999;"><ol style="margin-top:0px">';
		while ($objArchive->next()) 
		{
		    $strfile = substr($objArchive->file_name, $i);
		    if (substr($strfile, -12) == '.delete.json')
		    {
		        continue;
		    }
			$return .= '<li>' . $strfile . '</li>';
		}
		$return .= '</ol></div>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . Environment::get('base') . 'contao/main.php?do=easyupdate3" style="float:left;">&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</a>';
		$return .= '<a href="' . str_replace('task=3', 'task=4', Environment::get('base') . Environment::get('request')) . '" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
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
		$archive = $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$this->logSteps('Backup files', $archive);
		$objArchive = new \ZipReader($archive);
		$return .= '<div style="width:700px; margin:0 auto;">';
		$return .= '<h1 style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">' . $GLOBALS['TL_LANG']['easyupdate3']['backup'] . '</h1>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; height:400px; overflow:auto; background:#eee; border:1px solid #999;"><ol style="margin-top:0px">';
		//File list to replace
		$arrFiles = $objArchive->getFileList();
		
		//File list to delete
		$i = strpos($arrFiles[0], '/') + 1;
		while ($objArchive->next())
		{
		    $strfile = substr($objArchive->file_name, $i);
		    if (substr($strfile, -12) == '.delete.json')
		    {
		        $this->DELETE = json_decode( $objArchive->unzip() );
		        break;
		    }
		}

		$objBackup = new \ZipWriter($GLOBALS['TL_CONFIG']['uploadPath'] .'/easyupdate3/backup/bak' . date('YmdHi') . '-' . VERSION . '.' . BUILD . '.zip');
		$this->logSteps('Backup filename: bak' . date('YmdHi') . '-' . VERSION . '.' . BUILD . '.zip', $archive);
		$rootpath = 'contao-' . VERSION . '.' . BUILD . '/';
		
		//Backup of files that would be replaced.
		foreach ($arrFiles as $strFile) 
		{
			$strFile = substr($strFile, $i);
			if ($strFile == 'system/runonce.php') 
			{
				continue;
			}
			if (substr($strFile, -12) == '.delete.json')
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
		//Backup of files that would be deleted.
		reset($this->DELETE);
		if ( 0 < count($this->DELETE)) 
		{
		    $this->logSteps('Backup of files that would be deleted', $archive);
    		foreach ($this->DELETE as $strFile => $md5)
    		{
    		    
    		    if ($md5 == '') // Directory
    		    {
    		        continue;
    		    }
    		    try
    		    {
    		        $objBackup->addFile($strFile, $rootpath . $strFile);
    		        $return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['backuped'] . $strFile . '(for deleted)</li>';
    		    }
    		    catch (Exception $e)
    		    {
    		        $return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile . ' (' . $e->getMessage() . ')</li>';
    		    }
    		}
		}
		$objBackup->close();
		$return .= '</ol></div>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . Environment::get('base') . 'contao/main.php?do=easyupdate3" style="float:left;">&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</a>';
		$return .= '<a href="' . str_replace('task=4', 'task=5', Environment::get('base') . Environment::get('request')) . '" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
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
		$archive = $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$this->logSteps('Copy files', $archive);
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
					    $exclude[$GLOBALS['TL_CONFIG']['uploadPath'] .'/' . $key] = $value;
					    break;
					default :
						$exclude['system/config/' . $key] = $value;
						break;
				}
			}
		}
		$objArchive = new \ZipReader($archive);
		$arrFiles = $objArchive->getFileList();
		$i = strpos($arrFiles[0], '/') + 1;
		/*
		$return .= '<div style="width:700px; margin:0 auto;">';
		$return .= '<h1 style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">' . $GLOBALS['TL_LANG']['easyupdate3']['update'] . '</h1>';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; height:400px; overflow:auto; background:#eee; border:1px solid #999;">';
		$return .= '<ol style="margin-top:0px">';
		*/
		// Unzip files
		while ($objArchive->next()) 
		{
			$strFile = substr($objArchive->file_name, $i);
			if ($exclude[$strFile]) 
			{
				$return .= '<li style="color:#2500ff;">' . $GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile . ': ' . $GLOBALS['TL_LANG']['easyupdate3']['exclude'] . '</li>';
				continue;
			}
			if (substr($strFile, -12) == '.delete.json')
			{
			    $this->DELETE = json_decode( $objArchive->unzip() );
			    continue;
			}
			try 
			{
				$objFile = new File($strFile);
				$test = $objArchive->current();
				//Würgaround, Zipreader macht immer noch eine Exception
				//wenn Datei 0 Byte hat
				if ($test[uncompressed_size] != 0)
				{
				    $objFile->write($objArchive->unzip());
				}
				else 
				{
				    //$this->log('Datei mit 0 Byte gefunden: '.$strFile, 'easyupdate3 copyfiles', TL_GENERAL);
				}				
				$objFile->close();
				//$return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['updated'] . $strFile . '</li>';
				$this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['updated'] . $strFile, $archive);
			} 
			catch (Exception $e) 
			{
				//$return .= '<li style="color:#ff0000;">' . $GLOBALS['TL_LANG']['easyupdate3']['error'] . $strFile . ': ' . $e->getMessage() . '</li>';
			    $this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['error'] . $strFile . ': ' . $e->getMessage(), $archive);
			}
		}

		// purge the internal cache
		// system/cache/dca, system/cache/sql, system/cache/language
		$this->import('Automator');
		$this->Automator->purgeInternalCache();
		$this->logSteps('Purged the internal cache', $archive);
		
		$redirectUrl = str_replace('task=5', 'task=6', Environment::get('base') . Environment::get('request'));
		$this->redirect($redirectUrl);
		/*
	    $return .= '</ol></div>';
	    $return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . $redirectUrl . '" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
	    $return .= '</div>';
	    $return .= '</div>';
	    return $return;
        */
	}

	protected function deleteOldFiles($archive)
	{
	    $archive = $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
	    $this->logSteps('Delete old files', $archive);
	    $this->DELETE = array();
	    $return = '';
	    
	    $objArchive = new \ZipReader($archive);
	    $arrFiles = $objArchive->getFileList();	    
	    $i = strpos($arrFiles[0], '/') + 1;
	    while ($objArchive->next())
	    {
	        $strfile = substr($objArchive->file_name, $i);
	        if (substr($strfile, -12) == '.delete.json')
	        {
	            $this->DELETE = json_decode( $objArchive->unzip() );
	            break;
	        }
	    }
	    
	    $return .= '<div style="width:700px; margin:0 auto;">';
	    $return .= '<h1  style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">' . $GLOBALS['TL_LANG']['easyupdate3']['delete'] . '</h1>';
	    $return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; height:400px; overflow:auto; background:#eee; border:1px solid #999;">';
	    $return .= '<ol style="margin-top:0px">';
	     
	    //Delete files that would be deleted
	    reset($this->DELETE);
	    //$this->log('DELETE Array: '.print_r($this->DELETE, true), 'easyupdate copyfiles()', TL_GENERAL);
	    if ( 0 < count($this->DELETE))
	    {
	        foreach ($this->DELETE as $strFile => $md5)
	        {
	            if ($md5 == '') // Folder
	            {
	                if (is_dir(TL_ROOT . '/' . $strFile))
	                {
	                    // Delete the folder
	                    try
	                    {
	                        $objFolder = new \Folder($strFile);
	                        $objFolder->delete();
	                        $objFolder = null;
	                        unset($objFolder);
	                        $return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['deleted'] . $strFile . '</li>';
	                        $this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['deleted'] . $strFile, $archive);
	                    }
	                    catch (Exception $e)
	                    {
	                        $return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile . ' (' . $e->getMessage() . ')</li>';
	                        $this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile, $archive);
	                    }
	                }
	            }
	            else
	            {
	                if (file_exists(TL_ROOT . '/' . $strFile))
	                {
	                    // Delete the file
	                    try
	                    {
	                        $objFile = new File($strFile, true);
	                        $objFile->delete();
	                        $objFile = null;
	                        unset($objFile);
	                        $return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['deleted'] . $strFile . '</li>';
	                        $this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['deleted'] . $strFile, $archive);
	                    }
	                    catch (Exception $e)
	                    {
	                        $return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile . ' (' . $e->getMessage() . ')</li>';
	                        $this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile, $archive);
	                    }
	                }
	            }
	        }//foreach
	    }
	    else 
	    {
	        //There is nothing to delete.
	        $return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['nothing_to_delete'] . '</li>';
	        $this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['nothing_to_delete'], $archive);
	    }
	    
	    // Add log entry
	    $this->log('easyupdate3 completed', 'easyupdate3 completed', TL_GENERAL);
	    $this->logSteps('easyupdate3 completed, call install.php now', $archive);
	    $return .= '</ol></div>';
	    $return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
	    $return .= '<a href="' . Environment::get('base') . 'contao/install.php" style="float:right;">' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</a>';
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
		$IMPORT['BUILD']   = $pos_b ? $IMPORT_BUILD   : 'X';
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
	
	/**
	 * Add a log entry
	 * @param string
	 * @param string
	 */
	protected function logSteps($strMessage, $archive)
	{
	    $strLogfile = basename($archive) .'.log';
	    $file   = TL_ROOT . '/' . $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/logs/' . $strLogfile;
	    $folder = dirname($file);
	    // Create folder
	    if (!is_dir($folder))
	    {
	        new \Folder($folder);
	    }
	    //Logging
	    @error_log(sprintf("[%s] %s\n", date('d-M-Y H:i:s'), $strMessage), 3, $file);
	}
	
}
