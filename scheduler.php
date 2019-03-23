<?php
/**
 * Created by PhpStorm.
 * User: PC-FIXE
 * Date: 13/03/2019
 * Time: 20:12
 */

require __DIR__ . '/autoload.php';	

use GO\Scheduler;
use Cron\CronExpression;

use Repositories\App;
use Repositories\Config;
use Repositories\Utility;
use Repositories\Log;

use PHPMailer\PHPMailer\PHPMailer;

$config = Config::get();

// Create a new scheduler
$scheduler = new Scheduler();

// Send Daily report
try {
	if (!empty($config['app']['emailsReceiveLogs'])) {
		$scheduler->call(function() {
			$config = Config::get();

            $mail = new PHPMailer(true);
            $table = Log::buildHTML(log_name_yesterday(), $mail);
            $date = date('m/d/Y',strtotime("-1 days"));

            $body = file_get_contents(base_path().'/public/views/mail_log.php');
            $body = str_replace('{date}', $date, $body);
            $body = str_replace('{table}', $table, $body);

            Utility::sendMail(
                'Daily report '.$date.' - Synchonize Netflix and Trakt.tv',
                $body,
                explode(';', $config['app']['emailsReceiveLogs']),
                [log_path().'/'.log_name_yesterday()],
                $mail
            );
		})->daily('06:00');
	}
} catch (Exception $e) {
	echo $e->getMessage();
}

// Sync now auto
try {
	if (!empty($config['app']['scheduleSyncNow']) && CronExpression::isValidExpression($config['app']['scheduleSyncNow'])) {
		$scheduler->call(function() {
			App::synchronize();
		})->at($config['app']['scheduleSyncNow']);
	}
} catch(Exception $e) {
	echo $e->getMessage();
}

// Delete log older than x days
try {
	if (!empty($config['app']['deleteLogAfterXDays'])) {
		$scheduler->call(function() {
			$config = Config::get();
			Log::purge($config['app']['deleteLogAfterXDays']);
		})->daily();
	}
} catch(Exception $e) {
	echo $e->getMessage();
}

// Check expiration date token trakt
try {
	if (!empty($config['trakt_tv']['expires'])) {
		$scheduler->call(function() {
			$config = Config::get();
			$expires = new DateTime(date('Y-m-d H:i:s', $config['trakt_tv']['expires']));
			$now = new DateTime(date('Y-m-d H:i:s'));
			$dateDiff = date_diff($now, $expires);

			if ($dateDiff->format('%R%a') == '+2') {
				Utility::sendMail(
					'Refresh your Trakt.tv token - '.$dateDiff->format('%a').' days remaining', 
					'Your Trakt.tv token expires in '.$dateDiff->format('%a').' days. Refresh it in the config page.',
					explode(';', $config['app']['emailsReceiveLogs'])
				);
			}

			if ($dateDiff->format('%a') == '0') {
				Utility::sendMail(
					'Refresh your Trakt.tv token - Expires today', 
					'Your Trakt.tv token expires today. Refresh it in the config page.',
					explode(';', $config['app']['emailsReceiveLogs'])
				);
			}

			if ($dateDiff->format('%R%a') == '-2') {
				Utility::sendMail(
					'Refresh your Trakt.tv token - Outdated of '.$dateDiff->format('%a').' days', 
					'Your Trakt.tv token has expires of '.$dateDiff->format('%a').' days. Refresh it in the config page.',
					explode(';', $config['app']['emailsReceiveLogs'])
				);
			}
		})->daily();
	}
} catch(Exception $e) {
	echo $e->getMessage();
}

$scheduler->run();