<?php

namespace BugBuster\EasyUpdate3;

/**
 * Class ea3ClientRuntime
 * 
 * @author BugBuster
 * @author Contao Community Alliance (Thanks)
 */
class ea3ClientRuntime
{

    /**
     * Determinate if curl is enabled.
     *
     * @return bool
     */
    public static function isCurlEnabled()
    {
        return function_exists('curl_init');
    }
    
    /**
     * Determinate if allow_url_fopen is enabled.
     *
     * @return bool
     */
    public static function isAllowUrlFopenEnabled()
    {
        return (bool) ini_get('allow_url_fopen');
    }
    
}

