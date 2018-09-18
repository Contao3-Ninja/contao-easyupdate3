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
	
	protected $JOBSTATUS = false;
	
	protected function compile() 
	{
	    if ( $this->convertToBytes( ini_get("memory_limit") ) < 134217728 ) 
	    {
	    	@ini_set("memory_limit", "128M");
	    }
		
		// \\
		$archive = Input::get('filename');
		$task    = ($archive == 'n.a.' ? 'nofiles' : Input::get('task'));
		if ('transfer' == Input::post('task')) 
		{
			$task = 'transfer';
		}
		if ('maintenance' == Input::post('task'))
		{
		    $task = 'maintenance';
		}
		$config_post = Input::post('config');
		$this->Template->referer   = $this->getReferer(true);
		$this->Template->backTitle = specialchars($GLOBALS['TL_LANG']['easyupdate3']['backBT']);
		$this->Template->headline  = sprintf($GLOBALS['TL_LANG']['easyupdate3']['headline'], VERSION . '.' . BUILD);
		switch ($task) 
		{
			case 'transfer' :
			    $transfer_result = true;
			    $url_parts       = explode('file=', Input::post('updateurl',true));
			    $path_parts      = pathinfo($url_parts[1]);
			    try 
			    {
			        \EasyUpdate3\ea3ClientDownloader::download(Input::post('updateurl',true), TL_ROOT .'/'. $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . $path_parts['basename']);
			    } 
			    catch (Exception $e) 
			    {
			        $this->Template->ModuleFile = $this->getFiles( sprintf($GLOBALS['TL_LANG']['easyupdate3']['update_transfer_result_notok'], $path_parts['basename']) .': '. $e->getMessage() );
			        log_message($e);
			        $transfer_result = false;
			    }
			    if ($transfer_result)
			    {
    			    //Hash Test
    			    $EA3Server = EasyUpdate3\ea3ServerCommunication::getInstance();
    			    $HashRemote = $EA3Server->getEA3HashForUpdateZipByFile($url_parts[1]);
    			    $HashLocal  = md5_file( TL_ROOT .'/'. $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . $path_parts['basename'] );
    			    if ($HashRemote != $HashLocal) 
    			    {
    			        $this->Template->ModuleFile = $this->getFiles( sprintf($GLOBALS['TL_LANG']['easyupdate3']['update_transfer_wrong_hash'], $path_parts['basename']) );
    			        log_message( sprintf($GLOBALS['TL_LANG']['easyupdate3']['update_transfer_wrong_hash'], $url_parts[1]) . 'R: '.$HashRemote.' L: '.$HashLocal);
    			    	$transfer_result = false;
    			    	unlink(TL_ROOT .'/'. $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . $path_parts['basename']);
    			    }
                }
			    if ($transfer_result) 
			    {
			    	$this->Template->ModuleFile = $this->getFiles( sprintf($GLOBALS['TL_LANG']['easyupdate3']['update_transfer_result_ok'], $path_parts['basename']) );
			    	$this->writeDbafs($GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3', $path_parts['basename']);
			    }
			    break;
			case 'maintenance' :
			    $jobresult = $this->doMaintenanceJobs();
			    $this->Template->ModuleFile .= $this->getFiles();
			    break;
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
	protected function getFiles($notice = '') 
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
		else 
		{
		    //anlegen
		    new \Folder($GLOBALS['TL_CONFIG']['uploadPath'] .'/easyupdate3');		    
		}
		if (!$strAllFiles)
		{
			$strAllFiles .= sprintf('<option value="%s">%s</option>', 'n.a.', $GLOBALS['TL_LANG']['easyupdate3']['files_not_availabe']);
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
		//linke Box TODO: partial template
		$return .= '<div class="tl_formbody_edit" style="width:45%; float:left; border-right: 1px solid; padding-right: 18px;">';
		$return .= '  <div class="tl_tbox">';
		$return .= '      <form action="' . ampersand(Environment::get('request')) . '" name="tl_select_file" class="tl_form" method="GET">';
		$return .= '          <h3><label for="ctrl_original">' . $GLOBALS['TL_LANG']['easyupdate3']['selectfile'] . '</label></h3>';
		$return .= '          <input type="hidden" name="do" value="easyupdate3">';
		$return .= '          <input type="hidden" name="task" value="1">';
		$return .= '          <select name="filename" id="ctrl_original" class="tl_select" onfocus="Backend.getScrollOffset();">' . $strAllFiles . $strAllBackups . '</select> ';
		$return .= '          <input type="submit" class="tl_submit" alt="select a file" accesskey="s" value="' . specialchars($GLOBALS['TL_LANG']['easyupdate3']['setfile']) . '" />';
		$return .= '          <p class="tl_help tl_tip">' . $GLOBALS['TL_LANG']['easyupdate3']['description'] . '</p>';
        $return .= '      </form>';
		$return .= '      <h2><span class="mandatory">' . $GLOBALS['TL_LANG']['easyupdate3']['readme']['headline'] . '</span></h2>';
		$return .= '      <h2>'.$GLOBALS['TL_LANG']['easyupdate3']['readme']['text1_title'].'</h2>';
		$return .= '      <p style="text-align: justify;">'.$GLOBALS['TL_LANG']['easyupdate3']['readme']['text1_text'].'</p>';
		$return .= '      <h2>'.$GLOBALS['TL_LANG']['easyupdate3']['readme']['text2_title'].'</h2>';
		$return .= '      <p style="text-align: justify;">'.$GLOBALS['TL_LANG']['easyupdate3']['readme']['text2_text'].'</p>';
		$return .= '      <div style="margin-top:9px;"   ><strong>' . $GLOBALS['TL_LANG']['easyupdate3']['readme']['text3']['working']   . '</strong></div>';
		$return .= '      <div style="margin-bottom:9px;"><strong>' . $GLOBALS['TL_LANG']['easyupdate3']['readme']['text3']['incorrect'] . '</strong></div>';
		$return .= '      <div></div>';
		$return .= '      <p style="text-align: justify;">'.$GLOBALS['TL_LANG']['easyupdate3']['readme']['text4'].'</p>';
		$return .= '  </div>';
		$return .= '</div>';
		//rechte Box TODO: partial template
		$EA3Server = EasyUpdate3\ea3ServerCommunication::getInstance();  
		$EA3ServerStatus = $EA3Server->getEA3ServerStatus(); 
		$return .= '<div class="tl_formbody_edit" style="width:45%; float:left; padding-right: 18px;">';
		$return .= '  <div class="tl_tbox">';
		$return .= '    <h3>'.$GLOBALS['TL_LANG']['easyupdate3']['extern_title'].'</h3>';
		if ($EA3ServerStatus == 0) //offline gesetzt
		{
			$return .= '    <p class="server_status" style="line-height: 19px;">'.Image::getHtml('unpublished.gif', $GLOBALS['TL_LANG']['easyupdate3']['server_offline']).' '.$GLOBALS['TL_LANG']['easyupdate3']['server_offline'].'</p>';
			// Reason holen, Backendsprache übergeben
			$EAOfflineReason = $EA3Server->getEA3ServerOfflineReason($GLOBALS['TL_LANGUAGE'],'en');
			if ($EAOfflineReason !== false) 
			{
				$return .= '<p class="server_status" style="line-height: 19px; text-align: justify;">'.Image::getHtml('show.gif', $GLOBALS['TL_LANG']['easyupdate3']['server_offline']).' '.$EAOfflineReason.'</p>';
			}
		}
		elseif ($EA3ServerStatus == 1) //online gesetzt
		{
			$return .= '    <p class="server_status" style="line-height: 19px;">'.Image::getHtml('published.gif', $GLOBALS['TL_LANG']['easyupdate3']['server_online']).' '.$GLOBALS['TL_LANG']['easyupdate3']['server_online'].'</p>';
		}
		elseif ($EA3ServerStatus == -1) // error
		{
		    $return .= '    <p class="server_status" style="line-height: 19px;">'.Image::getHtml('error.gif', $GLOBALS['TL_LANG']['easyupdate3']['server_offline']).' '.$GLOBALS['TL_LANG']['easyupdate3']['server_error'].'</p>';
		    if ( EasyUpdate3\ea3ClientRuntime::isAllowUrlFopenEnabled() === false && 
                 EasyUpdate3\ea3ClientRuntime::isCurlEnabled()          === false
                )
		    {
		        $return .= '    <p style="text-align: justify;">'.$GLOBALS['TL_LANG']['easyupdate3']['fopen_curl_notice'].'</p>';
		    }
	        $return .= '    <p>'.$GLOBALS['TL_LANG']['easyupdate3']['server_error_notice'].'</p>';
		}
		$return .= '    <p><strong><em>'.$GLOBALS['TL_LANG']['easyupdate3']['extern_notice'].'</em></strong></p>'; // No official updates...

		// SMH? Nicht unerstuetzt
		if ($GLOBALS['TL_CONFIG']['useFTP'])
		{
		    $return .= '    <p><strong>'.$GLOBALS['TL_LANG']['easyupdate3']['smh_warning'].'</strong></p>';
		}
		//Hier nun die Transfer Möglichkeit einbauen
		if ( $EA3ServerStatus == 1 ) // Online
	    {
	        //return array (UUID als String, version_to, basename der ZIP)
	        //return array( 0,0,'') wenn kein Update vorhanden
	        //return array(-1,0,'') im Fehlerfall	        
		    $arrEA3NextUpdate = $EA3Server->getEA3NextUpdateBySource(VERSION, BUILD);
		    //TODO Debug
		    //log_message(print_r($arrEA3NextUpdate,true),'debug.log');
		}
		
		if ( $EA3ServerStatus == 1 ) // Online
		{
    		$next_update = false;
    		switch (true)
    		{
    		    case ($arrEA3NextUpdate[0] == -1):
    		        //Error
    		        $return .= '<p>'.$GLOBALS['TL_LANG']['easyupdate3']['get_next_update_error'].'<br>'.$GLOBALS['TL_LANG']['easyupdate3']['server_error_notice'].'</p>';
    		        break;
    		    case ($arrEA3NextUpdate[0] == '0'):
    		        //kein Update
    		        $return .= '<p class="server_status" style="line-height: 19px;">'.Image::getHtml('unpublished.gif', $GLOBALS['TL_LANG']['easyupdate3']['server_online']).' '.$GLOBALS['TL_LANG']['easyupdate3']['get_next_update_notfound'].'</p>';
    		        break;
    		    default :
    		        //Update vorhanden
    		        $next_update = true;
    		        $return .='<p class="server_status" style="line-height: 19px;">'.Image::getHtml('published.gif', $GLOBALS['TL_LANG']['easyupdate3']['server_online']).' '.sprintf($GLOBALS['TL_LANG']['easyupdate3']['get_next_update_found'],$arrEA3NextUpdate[1]).'</p>';
    		}
    		
    		if ($next_update) 
    		{
    		    //return false bei Fehler
    		    //return 'ERR: 404 File not found' wenn für diese UUID keine Datei gefunden wurde
    		    //return kompette URL für Downlaod / Transfer
    		    $strNextUpdateUrl = $EA3Server->getEA3TransferUrlForUpdateZipByUuid($arrEA3NextUpdate[0]); //UUID als String
    		    switch (true)
    		    {
    		        case ($strNextUpdateUrl === false):
    		            //Error
    		            $return .= '<p>'.$GLOBALS['TL_LANG']['easyupdate3']['get_next_update_url_error'].'<br>'.$GLOBALS['TL_LANG']['easyupdate3']['server_error_notice'].'</p>';
    		            break;
    		        case ($strNextUpdateUrl == 'ERR: 404 File not found'):
    		            //File not found
    		            $return .= '<p>'.$GLOBALS['TL_LANG']['easyupdate3']['get_next_update_file_notfound'].'</p>';
    		            break;
    	            default :
    	                //Ausgabe Buttons / Links
    	                $return .='
<div class="server_status" style="float:left;">
    <img width="18" height="18" alt="application/zip" src="assets/contao/images/iconRAR.gif"> '.$arrEA3NextUpdate[2].'
</div>
';
    	                $return .='
<div style="float:right;">
    <form id="transfer_form" action="' . ampersand(Environment::get('request')) . '" name="update_transfer" class="tl_form" method="POST" style="display: inline;">
    	<input type="hidden" name="do" value="easyupdate3">
        <input type="hidden" name="REQUEST_TOKEN"  value="'.REQUEST_TOKEN.'">
    	<input type="hidden" name="task" value="transfer">
        <input type="hidden" name="updateurl" value="'.$strNextUpdateUrl.'">
    	<input type="image" src="system/modules/easyupdate3/assets/inbox-download.png" alt="'.sprintf($GLOBALS['TL_LANG']['easyupdate3']['update_transfer_to'],$GLOBALS['TL_CONFIG']['uploadPath'].'/easyupdate3/').'" title="'.sprintf($GLOBALS['TL_LANG']['easyupdate3']['update_transfer_to'],$GLOBALS['TL_CONFIG']['uploadPath'].'/easyupdate3/').'">
  	    <a href="' . ampersand(Environment::get('request')) . '" onclick="document.getElementById(\'transfer_form\').submit(); return false;" title="'.sprintf($GLOBALS['TL_LANG']['easyupdate3']['update_transfer_to'],$GLOBALS['TL_CONFIG']['uploadPath'].'/easyupdate3/').'">'.$GLOBALS['TL_LANG']['easyupdate3']['update_transfer'].'</a>    	    
    </form>
    &nbsp;<a href="'.$strNextUpdateUrl.'" title="'.$GLOBALS['TL_LANG']['easyupdate3']['update_download'].' '.$arrEA3NextUpdate[2].'" onclick="return !window.open(this.href)"><img width="16" height="16" alt="'.$GLOBALS['TL_LANG']['easyupdate3']['update_download'].'" src="system/modules/easyupdate3/assets/drive-download.png"> '.$GLOBALS['TL_LANG']['easyupdate3']['update_download'].'</a>
</div>
';
    	                $return .='<div style="clear:both"></div>
';
    	                $return .= '<p style="margin-top:12px;"><strong>'.$notice.'</strong></p>
';
    		    }
    		}
		} // if online
		
		$return .= '    </div>'; //tl_box
		$return .= '  </div>'; //tl_formbody_edit
		$return .='   <div style="clear:both"></div>';
		// Wartung TODO: partial template
		
		$totalUpdates = $this->countFilesInFolder('easyupdate3', 'zip');
		$totalBackups = $this->countFilesInFolder('easyupdate3/backup', 'zip');
		$totalLogs    = $this->countFilesInFolder('easyupdate3/logs', 'log');
		
		$return .= '<div class="tl_formbody_edit" style="width:95%; float:left; padding-right: 18px;">';
		$return .= '  <div class="tl_tbox" id="tl_maintenance_cache" style="border-top: 1px solid;">';
		
		if ($this->JOBSTATUS) 
		{
		
            $return .= '    <div class="tl_message">
                              <p class="tl_confirm">'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_clear_confirm'].'</p>
                            </div>';
		}
		
		$return .= '    <h3>'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_title'].'</h3>';
		$return .= '
<form id="transfer_form" action="' . ampersand(Environment::get('request')) . '" name="maintenance" class="tl_form" method="POST" style="display: inline;">
			<input type="hidden" name="do" value="easyupdate3">
			<input type="hidden" name="REQUEST_TOKEN"  value="'.REQUEST_TOKEN.'">
			<input type="hidden" name="task" value="maintenance">
			<table>
				<thead>
					<tr>
						<th><input id="check_all" class="tl_checkbox" onclick="Backend.toggleCheckboxes(this, \'maintenance\')" type="checkbox"></th>
						<th>'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_job'].'</th>
						<th>'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_description'].'</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><input name="maintenance[files][]" id="maintenance_updates" class="tl_checkbox" value="updates" onfocus="Backend.getScrollOffset()" type="checkbox"></td>
						<td class="nw"><label for="maintenance_updates">'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_del_updates'].'</label><br>'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_files'].': <span>'.$totalUpdates.'</span></td>
						<td>'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_del_update_descr'].'</td>
					</tr>
					<tr>
						<td><input name="maintenance[files][]" id="maintenance_backups" class="tl_checkbox" value="backups" onfocus="Backend.getScrollOffset()" type="checkbox"></td>
						<td class="nw"><label for="maintenance_backups">'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_del_backups'].'</label><br>'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_files'].': <span>'.$totalBackups.'</span></td>
						<td>'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_del_backup_descr'].'</td>
					</tr>
					<tr>
						<td><input name="maintenance[files][]" id="maintenance_logs" class="tl_checkbox" value="logs" onfocus="Backend.getScrollOffset()" type="checkbox"></td>
						<td class="nw"><label for="maintenance_logs">'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_del_logs'].'</label><br>'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_files'].': <span>'.$totalLogs.'</span></td>
						<td>'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_del_log_descr'].'</td>
					</tr>
				</tbody>
			</table>
            <div class="tl_submit_container">
                <input name="clear" class="tl_submit" value="'.$GLOBALS['TL_LANG']['easyupdate3']['maintenance_commit'].'" type="submit">
            </div>
</form>
';
		$return .= '    </div>'; //tl_box
		$return .= '  </div>'; //tl_formbody_edit
		// Wartung Ende \\
		$return .='   <div style="clear:both"></div>';
		$return .= '</div>'; //main
		$return .= '<style type="text/css">
                    /* <![CDATA[ */
                    .server_status > img { vertical-align: middle; }
                    .tl_submit_container { background: rgba(0, 0, 0, 0) none repeat scroll 0 0; }
                    /* ]]> */
                    </style>';
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
	    $constants = '';
		$archive = $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
		$this->logSteps('#### Start easyUpdate3 ####', $archive, true);
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
			// check the both version, 0 => older, 1 => newer, 2 => same
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
					$checked = $config[$file] == 1 ? 'checked' : '';
					$intcheck = $checked == 'checked' ? ++ $intcheck : $intcheck;
					$strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][%s]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file, $file);
				}
			}
		}
		$strConfig .= "<br><b>&nbsp;" . $GLOBALS['TL_LANG']['easyupdate3']['other_legend'] . "</b><br>";
		$file = "robots.txt";
		if (is_file(Environment::get('documentRoot') . Environment::get('path') . "/" . $file)) 
		{
			$checked = $config[$file] == 1 ? 'checked' : '';
			$strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][%s]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file, $file);
		}
		// add by BugBuster
		$file = "tinymce.css";
		if (is_file(Environment::get('documentRoot') . Environment::get('path') . "/".$GLOBALS['TL_CONFIG']['uploadPath']."/" . $file))
		{
		    $checked = $config[$file] == 1 ? 'checked' : '';
		    $strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][%s]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file, $GLOBALS['TL_CONFIG']['uploadPath'] . '/'.$file);
		}

		//ab 3.3.1 kommt keine Demo mehr mit
		if (version_compare($this->IMPORT['VERSION'] .'.'. $this->IMPORT['BUILD'], '3.3.1', '<')) 
		{
            $file = $GLOBALS['TL_LANG']['easyupdate3']['demo'];
            $checked = $config[demo] == 1 ? 'checked' : '';
    		$strConfig .= sprintf('<input type="checkbox" id="config" name="config[files][demo]" value="1" ' . $checked . ' onChange="document.tl_select_config.submit();"/>%s<br>', $file);
		}

		// add the exclude files to the config
		if ($GLOBALS['TL_CONFIG']['easyupdate3']) 
		{
			if (is_array($config) && sizeof($config))
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
		$return .= '<input type="checkbox" onChange="Backend.toggleCheckboxes(this, ' . $id . ');document.tl_select_config.submit();"' . ($intall == $intcheck ? 'checked' : '') . '/><label style="color:#a6a6a6;">' . $GLOBALS['TL_LANG']['easyupdate3']['all'] . '</label><br>';
		$return .= $strConfig;
		$return .= '</form></div><div style="clear:both;"></div><br><p class="tl_info" style="height: 26px;">' . $GLOBALS['TL_LANG']['easyupdate3']['noupdatetext'] . '</p>';
		
		$return .= $this->checkPhpTooOld($this->IMPORT['VERSION'] .'.'. $this->IMPORT['BUILD'], PHP_VERSION);
		
		$return .= '';
		$return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
		$return .= '<a href="' . Environment::get('base') . 'contao/main.php?do=easyupdate3" style="float:left;"><strong>&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</strong></a>';
		$return .= '<a href="' . str_replace('task=1', 'task=2', Environment::get('base') . Environment::get('request')) . '" style="float:right;"><strong>' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</strong></a>';
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
	    $constants = '';
	    $changelog = '';
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
		// check the both version, 0 => older, 1 => newer, 2 => same
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
		$return .= '<a href="' . Environment::get('base') . 'contao/main.php?do=easyupdate3" style="float:left;"><strong>&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</strong></a>';
		$return .= '<a href="' . str_replace('task=2', 'task=3', Environment::get('base') . Environment::get('request')) . '" style="float:right;"><strong>' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</strong></a>';
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
		$return .= '<a href="' . Environment::get('base') . 'contao/main.php?do=easyupdate3" style="float:left;"><strong>&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</strong></a>';
		$return .= '<a href="' . str_replace('task=3', 'task=4', Environment::get('base') . Environment::get('request')) . '" style="float:right;"><strong>' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</strong></a>';
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
		if (is_array($this->DELETE) && 0 < count($this->DELETE)) 
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
		$return .= '<a href="' . Environment::get('base') . 'contao/main.php?do=easyupdate3" style="float:left;"><strong>&lt; ' . $GLOBALS['TL_LANG']['easyupdate3']['previous'] . '</strong></a>';
		$return .= '<a href="' . str_replace('task=4', 'task=5', Environment::get('base') . Environment::get('request')) . '" style="float:right;"><strong>' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</strong></a>';
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
				//$return .= '<li style="color:#2500ff;">' . $GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile . ': ' . $GLOBALS['TL_LANG']['easyupdate3']['exclude'] . '</li>';
				$this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile . ': ' . $GLOBALS['TL_LANG']['easyupdate3']['exclude'], $archive);
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
				if ($test['uncompressed_size'] != 0)
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
		// system/cache/dca, system/cache/sql, system/cache/language, system/cache/config
		$this->import('Automator');
		$this->Automator->purgeInternalCache();
		// assets/js and assets/css + system/cache/html
		$this->Automator->purgeScriptCache();
		$this->logSteps('Purged the internal cache', $archive);
		
		(function_exists('opcache_reset')) && opcache_reset();
		(function_exists('accelerator_reset')) && accelerator_reset();
		
		$redirectUrl = str_replace('task=5', 'task=6', Environment::get('base') . Environment::get('request'));
		$this->redirect($redirectUrl);
	}

	protected function deleteOldFiles($archive)
	{
	    $archive = $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/' . (substr($archive, 0, 3) == 'bak' ? 'backup/' : '') . $archive;
	    $this->logSteps('Delete old files', $archive);
	    $this->DELETE = array();
	    $return = '';
	    $filesfound = 0;
	    $htaccessfound = false;
	    	    
	    $objArchive = new \ZipReader($archive);
	    $arrFiles = $objArchive->getFileList();	    
	    $i = strpos($arrFiles[0], '/') + 1;
	    while ($objArchive->next())
	    {
	        $strfile = substr($objArchive->file_name, $i);
	        if (substr($strfile, -12) == '.delete.json')
	        {
	            $this->DELETE = json_decode( $objArchive->unzip() );
	            $filesfound++;
	        }
	        if ($strfile == '.htaccess.default')
	        {
	            $htaccessfound = true;
	            $filesfound++;
	        }
	        if ($filesfound == 2) 
	        {
	        	break;
	        }
	    }
	    
	    $return .= '<div style="width:700px; margin:0 auto;">';
	    $return .= '<h1  style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">' . $GLOBALS['TL_LANG']['easyupdate3']['update'] . '</h1>';
	    $return .= '<ul  style="margin-top:0px; margin-left: 3px;"><li style="list-style: inside none square;">'.$GLOBALS['TL_LANG']['easyupdate3']['done'].'</li></ul>';

	    $return .= '<h1  style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">' . $GLOBALS['TL_LANG']['easyupdate3']['delete'] . '</h1>';
	    $return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; height:200px; overflow:auto;">';
	    $return .= '<ul  style="margin-top:0px; margin-left: 3px;"><li style="list-style: inside none square;">'.$GLOBALS['TL_LANG']['easyupdate3']['done'].'</li></ul>';

	    if ($htaccessfound) 
	    {
	       $return .= '<h1 style="font-family:Verdana,sans-serif; font-size:16px; margin:18px 3px;">' . $GLOBALS['TL_LANG']['easyupdate3']['foundfiles'] . '</h1>';
	       $return .= '<ul style="margin-top:0px; margin-left: 3px;"><li style="list-style: inside none square;">'.$GLOBALS['TL_LANG']['easyupdate3']['foundhtaccess'].'</li></ul>';
        }
        $return .= '<br>&nbsp;<br>';	     
	    //Delete files that would be deleted
	    reset($this->DELETE);
	    //$this->log('DELETE Array: '.print_r($this->DELETE, true), 'easyupdate copyfiles()', TL_GENERAL);
	    if (is_array($this->DELETE) && 0 < count($this->DELETE))
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
	                        //$return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['deleted'] . $strFile . '</li>';
	                        $this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['deleted'] . $strFile, $archive);
	                    }
	                    catch (Exception $e)
	                    {
	                        //$return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile . ' (' . $e->getMessage() . ')</li>';
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
	                        //$return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['deleted'] . $strFile . '</li>';
	                        $this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['deleted'] . $strFile, $archive);
	                    }
	                    catch (Exception $e)
	                    {
	                        //$return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile . ' (' . $e->getMessage() . ')</li>';
	                        $this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['skipped'] . $strFile, $archive);
	                    }
	                }
	            }
	        }//foreach
	    }
	    else 
	    {
	        //There is nothing to delete.
	        //$return .= '<li>' . $GLOBALS['TL_LANG']['easyupdate3']['nothing_to_delete'] . '</li>';
	        $this->logSteps($GLOBALS['TL_LANG']['easyupdate3']['nothing_to_delete'], $archive);
	    }
	    
	    // Add log entry
	    $this->log('easyUpdate3 completed', 'easyUpdate3 completed', TL_GENERAL);
	    $this->logSteps('easyUpdate3 completed, call install.php now', $archive);
	    $return .= sprintf($GLOBALS['TL_LANG']['easyupdate3']['log_notice'], "easyupdate3/logs/".str_ireplace('.zip', '', basename($archive)) .".log") .'</div>';
	    $return .= '<div style="font-family:Verdana,sans-serif; font-size:11px; margin:18px 3px 12px 3px; overflow:hidden;">';
	    $return .= '<a href="' . Environment::get('base') . 'contao/install.php" style="float:right;"><strong>' . $GLOBALS['TL_LANG']['easyupdate3']['next'] . ' &gt;</strong></a>';
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
	    $pos_v = $pos_b = false;
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
	 * @return    integer    0 => older
	 *                       1 => newer
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
	protected function logSteps($strMessage, $archive, $delete_file = false)
	{
	    // Contao_3.3.7-3.4.0.zip ==> Contao_3.3.7-3.4.0.log
	    $strLogfile = str_ireplace('.zip', '', basename($archive)) .'.log';
	    $file   = $GLOBALS['TL_CONFIG']['uploadPath'] . '/easyupdate3/logs/' . $strLogfile;
	    $folder = dirname($file);
	    // Create folder
	    if (!is_dir($folder))
	    {
	        new \Folder($folder);
	    }
	    //Truncate old logging file
	    if ($delete_file == true && file_exists(TL_ROOT .'/'. $file)) 
	    {
	    	$file = new File($file, true); //true = do not create
	    	$file->truncate();
	    	$file->write(sprintf("[%s] %s\n", date('d-M-Y H:i:s'), $strMessage));
	    	$file->close();
	    }
	    else 
	    {
            //Logging
            @error_log(sprintf("[%s] %s\n", date('d-M-Y H:i:s'), $strMessage), 3, TL_ROOT .'/'. $file);
	    }
	}
	
	/**
	 * Generate the DBafs entries
	 * (copied from FormFileUpload.php)
	 *   
	 * @param string $strUploadFolder  without Prefix TL_ROOT/, without Suffix /
	 * @param string $filename
	 */
	protected function writeDbafs($strUploadFolder, $filename)
	{
	    // Generate the DB entries
	    $strFile = $strUploadFolder . '/' . $filename;
	    $objFile = \FilesModel::findByPath($strFile);
	    
	    // Existing file is being replaced (see contao/core#4818)
	    if ($objFile !== null)
	    {
	        $objFile->tstamp = time();
	        $objFile->path   = $strFile;
	        $objFile->hash   = md5_file(TL_ROOT . '/' . $strFile);
	        $objFile->save();
	    }
	    else
	    {
	        \Dbafs::addResource($strFile);
	    }
	    
	    // Update the hash of the target folder
	    \Dbafs::updateFolderHashes($strUploadFolder);
	    
	}
	
	/**
	 * Convert size information with unit to bytes (number)
	 * 
	 * https://php.net/manual/en/function.ini-get.php#96996
	 * 
	 * @param  string  $size_str   Size with unit (128M, 64m, ...)
	 * @return number              Size in bytes 
	 */
	protected function convertToBytes ($size_str)
	{
	    switch (substr ($size_str, -1))
	    {
	    	case 'M': case 'm': return (int)$size_str * 1048576;
	    	case 'K': case 'k': return (int)$size_str * 1024;
	    	case 'G': case 'g': return (int)$size_str * 1073741824;
	    	default: return $size_str;
	    }
	}
	
	/**
	 * Maintenance jobs
	 * Status is set in JOBSTATUS
	 */
	protected function doMaintenanceJobs()
	{
	    $maintenance = Input::post('maintenance');
	    if (!empty($maintenance) && is_array($maintenance))
	    {
	        foreach ($maintenance as $group=>$jobs)
	        {
	            foreach ($jobs as $job)
	            {
	                //updates
	                //backups
	                //logs
	                switch ($job)
	                {
	                	case 'updates' :
	                	    $this->delFilesInFolder('easyupdate3', 'zip');
	                	    $this->JOBSTATUS = true;
	                	    break;
                	    case 'backups' :
                	        $this->delFilesInFolder('easyupdate3/backup', 'zip');
                	        $this->JOBSTATUS = true;
                	        break;
            	        case 'logs' :
            	            $this->delFilesInFolder('easyupdate3/logs', 'log');
            	            $this->JOBSTATUS = true;
            	            break;
            	        default :
            	            $this->JOBSTATUS = false;
            	            break;
	                }
	            }
	        }
	    }
	    else 
	    {
	        $this->JOBSTATUS = false;
	    }
	    return;
	}
	
	/**
	 * Count Files in folder with special extension
	 * 
	 * @param string $folder       relative to the upload path
	 * @param string $extension    'zip', 'log'
	 * @return number              Number of Files
	 */
	protected function countFilesInFolder($folder, $extension)
	{
	    $total = 0;
	    $folder = $GLOBALS['TL_CONFIG']['uploadPath'] . '/' . $folder;
	    // Only check existing folders
	    if (is_dir(TL_ROOT . '/' . $folder))
	    {
	        $arrFiles = glob(TL_ROOT . '/' . $folder . '/*.' . $extension);
	        if (is_array($arrFiles))
	        {
	            foreach ($arrFiles as $strFile)
	            {
	                ++$total;
	            }
	        }
	    }
	    return $total;
	}
	
	/**
	 * Delete files in folder with special extension
	 * 
	 * @param string $folder       relative to the upload path
	 * @param string $extension    'zip', 'log'
	 */
	protected function delFilesInFolder($folder, $extension)
	{
	    $folder = $GLOBALS['TL_CONFIG']['uploadPath'] . '/' . $folder;
	    // Only check existing folders
	    if (is_dir(TL_ROOT . '/' . $folder))
	    {
	        $arrFiles = glob(TL_ROOT . '/' . $folder . '/*.' . $extension);
	        if (is_array($arrFiles))
	        {
	            foreach ($arrFiles as $strFile)
	            {
	                $objFile = new \File($folder .'/'. basename($strFile), true);
	                $objFile->delete();
	            }
	        }
	    }
	    return;
	}
	
	/**
	 * Check if PHP version too old for the new Contao version
	 * 
	 * @param string $newcontaoversion     New Contao version
	 * @param string $phpversion           Installed PHP version
	 * @return string                      Warning text if PHP version is too old
	 */
	protected function checkPhpTooOld($newcontaoversion, $phpversion)
	{
	    if ($newcontaoversion == 'X.X.X') 
	    {
	    	return '';
	    }
	    if ( version_compare( $newcontaoversion, '3.5.0', '>=' ) && version_compare($phpversion, '5.4.0', '<') )
	    {
	        //'Your PHP version (%s) is too old for Contao %s! You need at least version %s.'
	        return '<p class="tl_error" style="height: 26px;">' . sprintf($GLOBALS['TL_LANG']['easyupdate3']['phpversiontolow'],
                                                            	            $phpversion,
                                                            	            $newcontaoversion,
                                                            	            '5.4.0') . '</p>';
	    }
	    elseif ( version_compare( $newcontaoversion, '3.4.0', '>=' ) && version_compare($phpversion, '5.3.7', '<') )
	    {
	        //'Your PHP version (%s) is too old for Contao %s! You need at least version %s.'
	        return '<p class="tl_error" style="height: 26px;">' . sprintf($GLOBALS['TL_LANG']['easyupdate3']['phpversiontolow'],
                                                            	            $phpversion,
                                                            	            $newcontaoversion,
                                                            	            '5.3.7') . '</p>';
	    }
	    elseif ( version_compare( $newcontaoversion, '3.0.0', '>=' ) && version_compare($phpversion, '5.3.2', '<') )
	    {
	        //'Your PHP version (%s) is too old for Contao %s! You need at least version %s.'
	        return '<p class="tl_error" style="height: 26px;">' . sprintf($GLOBALS['TL_LANG']['easyupdate3']['phpversiontolow'],
                                                                            $phpversion,
                                                                            $newcontaoversion,
                                                                            '5.3.2') . '</p>';
	    }
	    return '';
	}
	
}
