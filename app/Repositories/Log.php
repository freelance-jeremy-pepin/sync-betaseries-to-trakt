<?php
/**
 * Created by PhpStorm.
 * User: PC-FIXE
 * Date: 12/03/2019
 * Time: 19:39
 */

namespace Repositories;

class Log {
    public static function write($log) {
        try {
            $dir = log_path();
            if (!is_dir($dir)) {
                mkdir($dir, 0755);
                chown($dir, 'www-data');
            }

            $filename = $dir.'/'.log_name();
            if (!file_exists($filename)) {
                touch($filename);
                chown($filename, 'www-data');
            }

            if (is_array($log)) {
                $log = implode("\t", $log);
            }

            $log = '['.date('Y-m-d H:i:s').']'."\t".$log;

            file_put_contents($filename, $log.PHP_EOL , FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {

        }
    }

    //src: http://biostall.com/php-snippet-deleting-files-older-than-x-days/
    public static function purge($daysMaxAge) {
        $days = $daysMaxAge;  
        $path = log_path().'/';  
          
        // Open the directory  
        if ($handle = opendir($path))  
        {  
            // Loop through the directory  
            while (false !== ($file = readdir($handle)))  
            {  
                // Check the file we're doing is actually a file  
                if (is_file($path.$file))  
                {  
                    // Check if the file is older than X days old  
                    if (filemtime($path.$file) < ( time() - ( $days * 24 * 60 * 60 ) ) )  
                    {  
                        // Do the deletion  
                        unlink($path.$file);  
                    }  
                }  
            }  
        }  
    }
}