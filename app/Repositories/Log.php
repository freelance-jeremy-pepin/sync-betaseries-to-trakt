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

    public static function buildHTML($filename, &$mail=null) {
        $filename = log_path().'/'.$filename;

        if (!file_exists($filename)) {
            throw new \Exception("File do not exist.");
        }

        $handle = fopen($filename, "r");
        $coutLines = 0;

        $html = '';

        $html .= '<table>';
        $html .= '    <thead>';
        $html .= '        <tr>';
        $html .= '            <th></th>';
        $html .= '            <th>Sync at</th>';
        $html .= '            <th>Title</th>';
        $html .= '            <th>Type</th>';
        $html .= '            <th>Watched at</th>';
        $html .= '            <th class="center-align">Status</th>';
        $html .= '            <th>Infos</th>';
        $html .= '        </tr>';
        $html .= '    </thead>';
        $html .= '    <tbody>';

        $rootPathTMDB = 'https://image.tmdb.org/t/p/w500';

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $parts = explode("\t", $line);

                foreach ($parts as $index => $part) {
                    $parts[$index] = trim($part);
                    $parts[$index] = str_replace('•', '-', $parts[$index]);
                }

                if ($parts[1] == '___Start sync___' || $parts[1] == '___End sync___') {
                    continue;
                }

                $coutLines ++;

                if (!empty($parts[5]) && $mail != null) {
                    $poster = file_get_contents($rootPathTMDB.'/'.basename($parts[5]));
                    $f = finfo_open();
                    $mimetype = finfo_buffer($f, $poster, FILEINFO_MIME_TYPE);
                    $base = base64_encode($poster);
                    $resource = base64_decode(str_replace(" ", "+", substr($base, strpos($base, ","))));
                    $mail->AddStringEmbeddedImage($resource, 'img_'.$coutLines, 'img_'.$coutLines, "base64", $mimetype);
                }

                $date =  str_replace(']', '', str_replace('[', '', $parts[0]));
                $date = \DateTime::createFromFormat('Y-m-d H:i:s', $date);

                $html .= '<tr>';
                if ($mail != null) {
                    $html .= '    <td>'.(empty($parts[5]) ? '' : '<img style="max-width: 100px;" src="cid:img_'.$coutLines.'">').'</td>'; // POSTER
                } else {
                    $html .= '    <td>'.(empty($parts[5]) ? '' : '<img style="max-width: 100px;" src="'.$parts[5].'">').'</td>'; // POSTER
                }
                $html .= '    <td>'.$date->format('H:i').'</td>'; // DATE
                $html .= '    <td style="font-weight: bold">'.(empty($parts[3]) ? '' : $parts[3]).'</td>'; // TITLE
                $html .= '    <td>'.(empty($parts[2]) ? '' : $parts[2]).'</td>'; // TYPE
                $html .= '    <td>'.(empty($parts[4]) ? '' : $parts[4]).'</td>'; // WATCHED AT

                // ADDED
                if ($parts[1]=='Added') {
                    $html .= '    <td style="font-weight: bold"><font color="green">Success</font></td>';
                } else if ($parts[1]=='Already added') {
                    $html .= '    <td style="font-weight: bold"><font color="orange">Skipped</font></td>';
                } else {
                    $html .= '    <td style="font-weight: bold"><font color="red">Error</font></td>';
                }

                $html .= '    <td style="font-weight: bold">'.(!empty($parts[7]) ? 'Error: '.$parts[7] : '').' '.($parts[1]=='Already added' ? 'Already added' : '').'</td>'; // ERROR
                $html .= '</tr>';
            }

            fclose($handle);
        } else {
            return '';
        }

        if ($coutLines == 0) {
            return '<h3>Nothing synchronized this day.</h3>';
        }

        $html .= '    </tbody>';
        $html .= '</table>';
        $html .= '</body>';

        return $html;
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

    public static function getIds() {
        $dir = log_path();
        if (!is_dir($dir)) {
            mkdir($dir, 0755);
            chown($dir, 'www-data');
        }

        $filename = $dir.'/sync.log';
        if (!file_exists($filename)) {
            touch($filename);
            chown($filename, 'www-data');

            return [];
        }

        $handle = fopen($filename, "r");

        $ids = [];
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $parts = explode("\t", $line);

                if (empty($parts[0]) || empty($parts[1])) continue;

                $ids[trim($parts[0])] = [
                    "id" => trim($parts[0]),
                    "status" => trim($parts[1]),
                    "in_timeline" => false
                ];
            }
            fclose($handle);

            return $ids;
        } else {
            return [];
        }

    }

    public static function initIds($events) {
        $ids = [];

        foreach ($events as $event) {
            $ids[$event->id] = [
                "id" => $event->id,
                "status" => "ok"
            ];
        }
        self::writeIds($ids);
    }

    public static function writeIds($ids) {
        $dir = log_path();
        if (!is_dir($dir)) {
            mkdir($dir, 0755);
            chown($dir, 'www-data');
        }

        $filename = $dir.'/sync.log';
        if (!file_exists($filename)) {
            touch($filename);
            chown($filename, 'www-data');

            return [];
        }

        file_put_contents($filename, "");

        foreach ($ids as $id) {

            if (!isset($id["status"])) $id["status"] = "ok";
            if (!isset($id["in_timeline"])) $id["in_timeline"] = true;

            if ($id["in_timeline"] == false) {
                continue;
            };

            $line = $id["id"]."\t".$id["status"]."\n";
            file_put_contents($filename, $line, FILE_APPEND);
        }
    }
}