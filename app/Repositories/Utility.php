<?php
	namespace Repositories;
/**
 * Created by PhpStorm.
 * User: PC-FIXE
 * Date: 12/03/2019
 * Time: 19:39
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require base_path().'/vendor/phpmailer/phpmailer/src/Exception.php';
require base_path().'/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require base_path().'/vendor/phpmailer/phpmailer/src/SMTP.php';

class Utility {
	public static function sendMail($subject, $body, $recipients, $attachments=[], $mail=null) {
		if (empty($mail)) {
            $mail = new PHPMailer(true);
        }

		try {
			$config = Config::get();

			//Server settings
			$mail->isSMTP();
			$mail->Host = $config['mail']['mailHost'];


			if ($config['mail']['mailAuthentification'] == 'yes') {
				$mail->SMTPAuth = true;
				$mail->Username = $config['mail']['mailUsername'];
				$mail->Password = Utility::decryptEncrypt('decrypt', $config['mail']['mailPassword']);
			} else {
				$mail->SMTPAuth = false;
			}

			if ($config['mail']['mailEncryption'] != 'no') {
				$mail->SMTPSecure = $config['mail']['mailEncryption'];
			}

			$mail->Port = $config['mail']['mailPort'];

			//Recipients
			$mail->setFrom($config['mail']['mailFrom'], 'Synchronize Netflix and Trakt.tv');
			foreach ($recipients as $recipient) {
				$mail->addAddress($recipient);
			}

			//Attachments
			foreach ($attachments as $attachment) {
				$mail->addAttachment($attachment);
			}

			//Content
			$mail->isHTML(true);
			$mail->Subject = $subject;
			$mail->Body = $body;

			$mail->send();
		} catch (Exception $e) {
			throw new Exception($mail->ErrorInfo);
		}
	}

    public static function getDataJson($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        return json_decode($data);
    }

    public static function decryptEncrypt($action, $string) {
	    $output = false;
	 
	    $encrypt_method = "AES-256-CBC";
	    $secret_key = 'F5Z9Q6FG7';
	    $secret_iv = 'S8DF73S42';
	 
	    // hash
	    $key = hash('sha256', $secret_key);
	    
	    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
	    $iv = substr(hash('sha256', $secret_iv), 0, 16);
	 
	    if( $action == 'encrypt' ) {
	        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
	        $output = base64_encode($output);
	    }
	    else if( $action == 'decrypt' ){
	        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
	    }
	 
	    return $output;
	}
}