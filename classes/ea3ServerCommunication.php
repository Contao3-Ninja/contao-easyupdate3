<?php

namespace BugBuster\EasyUpdate3;

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
        $Status = \Tivoka\Client::request('ea3server.getStatus');
        try
        {
            \Tivoka\Client::connect($this->target)->send($Status);
        }
        catch (\Exception $e) 
        {
            $this->log('easyUpdate3 Serverstatus Error: '.$e->getMessage(), 'ea3ServerCommunication getEA3ServerStatus', TL_ERROR);
            return -1;
        }
        
        if ($Status->isError())
        {
            $this->log('easyUpdate3 Serverstatus Error: '.$Status->errorMessage, 'ea3ServerCommunication getEA3ServerStatus', TL_ERROR);
            return -1;
        }
        return $Status->result; // 0:Offline 1:Online        
    }
    
    
}

