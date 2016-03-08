<?php

/*
Plugin Name: KMI site backup
Plugin URI: 
Description: Plugin to manage site's backup.
Author: KMI
Version: 1.0
Author URI: 
*/

if(!defined('ABSPATH')) exit; // Exit if accessed directly

require_once 'site_backup.php';

register_activation_hook(__FILE__, 'kmi_activate_site_backup');

function kmi_activate_site_backup()
{
    error_log('KMI site backup activated.');
}

register_deactivation_hook(__FILE__, 'kmi_deactivate_site_backup');

function kmi_deactivate_site_backup()
{
    error_log('KMI site backup deactivated.');
}