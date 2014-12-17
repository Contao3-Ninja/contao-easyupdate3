<?php

namespace BugBuster\EasyUpdate3;
use BugBuster\EasyUpdate3\ea3ClientRuntime;

/**
 * Class ea3ClientDownloader
 *
 * Convenience class for downloading files.
 * 
 * @author Contao Community Alliance (original: Composer Download.php)
 * @author Glen Langer (current version, add timeout and user agent)
 */
class ea3ClientDownloader
{
    /**
     * Download an url and return or store contents.
     *
     * @param string $url
     * @param bool   $file
     *
     * @return bool|null|string
     * @throws \Exception
     */
    public static function download($url, $file = false)
    {
        if (ea3ClientRuntime::isCurlEnabled()) 
        {
            return static::curlDownload($url, $file);
        } 
        else 
        {
            if (ea3ClientRuntime::isAllowUrlFopenEnabled()) 
            {
                return static::fgetDownload($url, $file);
            } 
            else 
            {
                throw new \RuntimeException('No download mechanism available');
            }
        }
    }

    /**
     * @param      $url
     * @param bool $file
     *
     * @return bool|null|string
     * @throws \Exception
     *
     * @SuppressWarnings("unused")
     */
    public static function fgetDownload($url, $file = false)
    {
        $return = null;

        if ($file === false) {
            $return = true;
            $file   = 'php://temp';
        }
        set_time_limit(120);
        
        $fileStream = fopen($file, 'wb+');

        $context = array(
            'http' => array('user_agent' => 'Tivoka/3.4.0 (easyUpdate3 fopen)')
        );
        
        
        fwrite($fileStream, file_get_contents( $url, false, stream_context_create($context)) );
        $headers              = $http_response_header;
        $firstHeaderLine      = $headers[0];
        $firstHeaderLineParts = explode(' ', $firstHeaderLine);

        if ($firstHeaderLineParts[1] == 301 || $firstHeaderLineParts[1] == 302) {
            foreach ($headers as $header) {
                $matches = array();
                preg_match('/^Location:(.*?)$/', $header, $matches);
                $url = trim(array_pop($matches));
                return static::fgetDownload($url, $file);
            }
            throw new \Exception("Can't get the redirect location");
        }

        if ($return) {
            rewind($fileStream);
            $return = stream_get_contents($fileStream);
        }

        fclose($fileStream);

        return $return;
    }

    /**
     * @param      $url
     * @param bool $file
     *
     * @return bool|null|string
     * @throws \Exception
     */
    public static function curlDownload($url, $file = false)
    {
        $return = null;

        if ($file === false) {
            $return = true;
            $file   = 'php://temp';
        }
        set_time_limit(120); // 2 minutes for PHP
        
        $curl = curl_init($url);

        $headerStream = fopen('php://temp', 'wb+');
        $fileStream   = fopen($file, 'wb+');

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 2 minutes for cURL
        curl_setopt($curl, CURLOPT_WRITEHEADER, $headerStream);
        curl_setopt($curl, CURLOPT_FILE, $fileStream);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Tivoka/3.4.0 (easyUpdate3 curl)');

        curl_exec($curl);

        rewind($headerStream);
        $header = stream_get_contents($headerStream);

        if ($return) {
            rewind($fileStream);
            $return = stream_get_contents($fileStream);
        }

        fclose($headerStream);
        fclose($fileStream);

        if (curl_errno($curl)) {
            throw new \Exception(
                curl_error($curl),
                curl_errno($curl)
            );
        }

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($code == 301 || $code == 302) {
            preg_match('/Location:(.*?)\n/', $header, $matches);
            $url = trim(array_pop($matches));

            return static::curlDownload($url, $file);
        }

        return $return;
    }
}
