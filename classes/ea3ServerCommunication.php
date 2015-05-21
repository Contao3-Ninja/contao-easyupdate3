<?php

namespace BugBuster\EasyUpdate3;

use BugBuster\EasyUpdate3\ea3ClientRuntime;

class ea3ServerCommunication extends \Backend
{
    protected $target = '';
    
    /**
     * Current object instance
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->target = $GLOBALS['EA3SERVER']['TARGET'];
    }
    
    /**
     * Return the current object instance (Singleton)
     * @return BotStatisticsHelper
     */
    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new ea3ServerCommunication();
        }
    
        return self::$instance;
    }
    
    
    
    /**
     * Get EA3Server Status
     * 
     * @return number   -1:Error, 0:Offline, 1:Online
     */
    public function getEA3ServerStatus()
    {
        $connect_possible = true;
        if ( ea3ClientRuntime::isAllowUrlFopenEnabled() === false && ea3ClientRuntime::isCurlEnabled() === false) 
        {
            $connect_possible = false;
        }
        //Tivoka installiert?
        if (!in_array('tivoka', $this->Config->getActiveModules()))
        {
            $this->log('easyUpdate3 Serverstatus Error: Please install the required extension "bugbuster/tivoka"', 'ea3ServerCommunication getEA3ServerStatus', TL_ERROR);
            return -1;
        }
        
        $Status = \Tivoka\Client::request('ea3server.getStatus');
        try
        {
            \Tivoka\Client::connect($this->target)->send($Status);
        }
        catch (\Exception $e) 
        {
            $this->log('easyUpdate3 Serverstatus Error: '.$e->getMessage(), 'ea3ServerCommunication getEA3ServerStatus', TL_ERROR);
            if (!$connect_possible) 
            {
                $this->log($GLOBALS['TL_LANG']['easyupdate3']['allow_url_fopen_not_set'].'<br>'.$GLOBALS['TL_LANG']['easyupdate3']['curl_not_available'].'<br>'.$GLOBALS['TL_LANG']['easyupdate3']['fopen_curl_notice'], 'ea3ServerCommunication getEA3ServerStatus', TL_ERROR);
            }
            return -1;
        }
        
        if ($Status->isError())
        {
            $this->log('easyUpdate3 Serverstatus Error: '.$Status->errorMessage, 'ea3ServerCommunication getEA3ServerStatus', TL_ERROR);
            if (!$connect_possible)
            {
                $this->log($GLOBALS['TL_LANG']['easyupdate3']['allow_url_fopen_not_set'].'<br>'.$GLOBALS['TL_LANG']['easyupdate3']['curl_not_available'].'<br>'.$GLOBALS['TL_LANG']['easyupdate3']['fopen_curl_notice'], 'ea3ServerCommunication getEA3ServerStatus', TL_ERROR);
            }
            
            return -1;
        }
        return $Status->result; // 0:Offline 1:Online        
    }
    
    public function getEA3NextUpdateBySource($version, $build)
    {
        $NextUpdate = \Tivoka\Client::request('ea3server.getNextUpdate',array($version.'.'.$build)); //Parameter muss ein Array sein!
        try 
        {
            \Tivoka\Client::connect($this->target)->send($NextUpdate);
        } 
        catch (\Exception $e) 
        {
            $this->log('easyUpdate3 getEA3NextUpdateBySource Error: '.$e->getMessage(), 'ea3ServerCommunication getEA3NextUpdateBySource', TL_ERROR);
            return array(-1,0,'');
        }
        
        if ($NextUpdate->isError())
        {
            $this->log('easyUpdate3 getEA3NextUpdateBySource Error: '.$e->getMessage(), 'ea3ServerCommunication getEA3NextUpdateBySource', TL_ERROR);
            return array(-1,0,'');
        }
        return $NextUpdate->result; // array (UUID als String, version_to, basename der ZIP) 
    }
    
    public function getEA3TransferUrlForUpdateZipByUuid($strUUID)
    {
        $transferURL = \Tivoka\Client::request( 'ea3server.getTransferUrl', array($strUUID) );
        try
        {
            \Tivoka\Client::connect($this->target)->send($transferURL);
        }
        catch (\Exception $e)
        {
            $this->log('easyUpdate3 getEA3TransferUrlForUpdateZipByUuid Error: '.$e->getMessage(), 'ea3ServerCommunication getEA3TransferUrlForUpdateZipByUuid', TL_ERROR);
            return false;
        }
        
        if ($transferURL->isError())
        {
            $this->log('easyUpdate3 getEA3TransferUrlForUpdateZipByUuid Error: '.$e->getMessage(), 'ea3ServerCommunication getEA3TransferUrlForUpdateZipByUuid', TL_ERROR);
            return false;
        }
        return base64_decode($transferURL->result);
    }
    
    public function getEA3HashForUpdateZipByFile($strPathFile)
    {
        $Hash = \Tivoka\Client::request( 'ea3server.getHash', array($strPathFile) );
        try
        {
            \Tivoka\Client::connect($this->target)->send($Hash);
        }
        catch (\Exception $e)
        {
            $this->log('easyUpdate3 getEA3HashForUpdateZipByFile Error: '.$e->getMessage(), 'ea3ServerCommunication getEA3HashForUpdateZipByFile', TL_ERROR);
            return false;
        }
    
        if ($Hash->isError())
        {
            $this->log('easyUpdate3 getEA3HashForUpdateZipByFile Error: '.$e->getMessage(), 'ea3ServerCommunication getEA3HashForUpdateZipByFile', TL_ERROR);
            return false;
        }
        return $Hash->result;
    }
}

