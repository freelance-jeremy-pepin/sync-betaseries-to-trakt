<?php

function base_path() {
	return __DIR__;
}

function config_file() {
	return base_path().'/config.ini';
}

function log_path() {
	return base_path().'/log';
}

function log_name() {
	return date('Y-m-d').'.log';
}

function log_name_yesterday() {
	return date('Y-m-d',strtotime("-1 days")).'.log';
}