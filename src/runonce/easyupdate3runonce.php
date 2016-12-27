<?php   
/**
 * Contao Open Source CMS, Copyright (C) 2005-2012 Leo Feyer
 *
 *
 * Modul easyupdate - /config/runonce.php
 *
 * @copyright  Glen Langer 2013
 * @author     Glen Langer
 * @package    easyupdate
 * @license    LGPL
 */

/**
 * Class BannerRunonceJob
 *
 * @copyright  Glen Langer 2013
 * @author     Glen Langer
 * @package    easyupdate
 * @license    LGPL
 */
class RunonceJob extends Controller
{
	public function __construct()
	{
	    parent::__construct();
	}
	
	public function run()
	{
		// delete old class files
	    foreach (array('SystemTL.php', 'ZipReaderTL.php', 'ZipWriterTL.php', 'easyupdate3.php') as $file)
	    {
	        // Purge the file
    		if (is_file(TL_ROOT . '/system/modules/easyupdate3/'.$file))
    		{
    		    $objFile = new File('system/modules/easyupdate3/'.$file);
    		    $objFile->delete();
    		    $objFile->close();
    		    $objFile=null;
    		    unset($objFile);
    		}
	    }
	}
}

$objRunonceJob = new RunonceJob();
$objRunonceJob->run();

?>