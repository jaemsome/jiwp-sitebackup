<?php

if(!defined('ABSPATH')) exit; // Exit if accessed directly

class KMI_Site_Backup
{
    // Public variables
    public $general_settings = array();
    // Private variables
    private $_general_settings_key = 'kmi_site_backup_general_settings';
    private $_plugin_options_key = 'kmi_site_backup_menu_option';
    private $_backup_files_dir;
    private $_backup_files_arr = array();
    private $_plugin_settings_tabs = array();
    private $_message = array();
    
    public function __construct()
    {
        $this->_backup_files_dir = plugin_dir_path(__FILE__).'backup-files/';
        $this->_Setup_Shortcodes();
        $this->_Setup_Action_Hooks();
    }
    
    public function Add_Admin_Option_Page()
    {
        if(empty($GLOBALS['admin_page_hooks']['kmi_menu_options']))
            add_menu_page('KMI Options', 'KMI Options', 'manage_options', 'kmi_menu_options', array($this, 'KMI_Options_Page'));
        
        if(empty($GLOBALS['admin_page_hooks'][$this->_plugin_options_key]))
        {
            $option_page = add_submenu_page('kmi_menu_options', 'KMI Site Backup', 'Site Backup', 'manage_options', $this->_plugin_options_key, array($this, 'Site_Backup_Option_Page'));
            // Add css to the option page
            add_action('admin_print_styles-'.$option_page, array($this, 'Add_Option_Page_Styles'));
            // Add javascript to the option page
            add_action('admin_print_scripts-'.$option_page, array($this, 'Add_Option_Page_Scripts'));
        }
    }
    
    /*
     * KMI option page UI
     */
    public function KMI_Options_Page()
    {
        ?>
        <div class="wrap">
            <h2>Welcome to KMI Technology plugins. You can select the items under this menu to edit the desired plugin's settings.</h2>
        </div>
        <?php
    }
    
    public function Site_Backup_Option_Page()
    {
        ?>
        <div class="wrap">
            <?php if(!empty($this->_message['error'])): ?>
            <p class="error message">
                <?php
                    foreach($this->_message['error'] as $error)
                    {
                        echo $error.'<br/>';
                    }
                ?>
            </p>
            <?php elseif(!empty($this->_message['success'])): ?>
                <p class="success message"><?php echo $this->_message['success']; ?></p>
            <?php endif; ?>
                
            <form method="POST" action="">
                <input type="hidden" name="kmi_site_backup" value="true" />
                <?php
//                    submit_button('Create Backup', 'primary', 'kmi_site_backup_submit', true, array('id'=>'ajax-click'));
                    
                    settings_fields($this->_general_settings_key);
                    
                    do_settings_sections($this->_general_settings_key);
                ?>
            </form>
        </div>
        <?php
    }
    
    /*
     * Adding css for the option page
     */
    public function Add_Option_Page_Styles()
    {
        if(!wp_style_is('kmi_global_style', 'registered'))
        {
            wp_register_style('kmi_global_style', plugins_url('css/global.css', __FILE__));
        }
        
        if(!wp_style_is('kmi_global_style', 'enqueued'))
        {
            wp_enqueue_style('kmi_global_style');
        }
    }
    
    /*
     * Adding javascripts for the option page
     */
    public function Add_Option_Page_Scripts()
    {
        // Register then include the script
        wp_register_script('kmi_site_backup_script', plugins_url('js/kmi-site-backup.js', __FILE__), array(), false, true);
        wp_enqueue_script('kmi_site_backup_script');
        // Ajax object variables
        $ajax_obj_variables_arr = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'option_page' => $this->_plugin_options_key,
            'ajax_loader' => get_site_url().'/wp-admin/images/loading.gif'
        );
        wp_localize_script('kmi_site_backup_script', 'ajax_object', $ajax_obj_variables_arr);
    }
    
    public function Register_Admin_Option_Settings()
    {
        // Register general settings
        $this->_plugin_settings_tabs[$this->_general_settings_key] = 'General';
        register_setting($this->_general_settings_key, $this->_general_settings_key);
        // Add section on general settings
        add_settings_section('kmi_site_backup_list_section', 'KMI Site Backup', array($this, 'Site_Backup_List_Section'), $this->_general_settings_key);
        
        // Process site backup
        if(isset($_POST['kmi_site_backup_submit']))
        {
            $this->_Create_Backup();
        }
        
        // Retrieve all backup files
        $this->_backup_files_arr = glob($this->_backup_files_dir.'*.zip');
        
        // Delete a backup file
        if($_GET['page'] == $this->_plugin_options_key && !empty($_GET['delete']))
        {
            $this->_Delete_Backup($_GET['delete']);
        }
        
        // Download a backup file
        if($_GET['page'] == $this->_plugin_options_key && !empty($_GET['download']))
        {
            $this->_Download_Backup_File($_GET['download']);
        }
    }
    
    public function Site_Backup_List_Section()
    {
        ?>
        <p><input type="submit" id="kmi_create_backup_btn" class="button button-primary" value="Create Backup" name="kmi_site_backup_submit" /></p>
        <table id="kmi_site_backup_list" class="kmi-table kmi-one-column">
            <tr id="kmi_table_header">
                <th class="bg-grey align-left">Filename</th>
                <th class="bg-grey kmi-four-columns">Size</th>
                <th class="bg-grey kmi-four-columns">Actions</th>
            </tr>
            <?php if(count($this->_backup_files_arr) > 0): ?>
                <?php foreach($this->_backup_files_arr as $bckup_file): $path_parts = pathinfo($bckup_file);  ?>
                    <tr id="backup_<?php echo $path_parts['filename']; ?>">
                        <td><?php echo $path_parts['basename']; ?></td>
                        <td class="align-center"><?php echo $this->_Readable_File_Size(filesize($bckup_file)); ?></td>
                        <td class="align-center">
                            <a href="?page=<?php echo $this->_plugin_options_key; ?>&download=<?php echo $path_parts['basename']; ?>" class="dashicons dashicons-download" title="Download File" alt="Download File"></a>
                            <a href="?page=<?php echo $this->_plugin_options_key; ?>&delete=<?php echo $path_parts['basename']; ?>" class="dashicons dashicons-trash kmi_delete_backup_btn" id="delete_<?php echo $path_parts['filename']; ?>" title="Delete File"></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr id="kmi_empty_row">
                    <td class="align-center" colspan="3">No backup files found.</td>
                </tr>
            <?php endif; ?>
        </table>
        <?php
    }
    
    public function Initialization()
    {
        // For the tab control
        $this->general_settings = (array)get_option($this->_general_settings_key);
        
        // Merge with defaults
	$this->general_settings = array_merge(
            array(
                'general_option' => 'General value'
            ),
            $this->general_settings
        );
    }
    
    public function Create_Backup()
    {
        $response = array();
        
        $this->_Create_Backup();
        
        if(!empty($this->_message['error']))
            $response['error'] = $this->_message['error'];
        else if(!empty ($this->_message['success']))
            $response['success'] = $this->_message['success'];
        
        // Add the file basic info to the response
        if(!empty($this->_message['file']))
            $response['file'] = $this->_message['file'];
        
        echo json_encode($response);
        wp_die();
    }
    
    public function Delete_Backup()
    {
        $response = array();
        
        list($action, $root_folder, $date, $time, $AM_PM) = explode('_', $_POST['filename']);
        
        if($action == 'delete')
        {
            $filename = trim($root_folder.'_'.$date.'_'.$time.'_'.$AM_PM).'.zip';
        
            $this->_Delete_Backup($filename);

            if(!empty($this->_message['error']))
                $response['error'] = $this->_message['error'];
            else if(!empty ($this->_message['success']))
                $response['success'] = $this->_message['success'];

            // Add the file basic info to the response
            if(!empty($this->_message['file']))
                $response['file'] = $this->_message['file'];
        }
        else
            $response['error'] = 'Sorry, unrecognized command.';
        
        echo json_encode($response);
        wp_die();
    }
    
    private function _Create_Backup()
    {
        // The directory or file to be backed up
        $source_folder = $_SERVER['DOCUMENT_ROOT']; // Default: plugin_dir_path(__FILE__);
        // Filename of the new generated backup archive
        $zip_filename = basename($source_folder).'_'.date('Y-m-d_g-i-s_A').'.zip';
        // Full path of the newly generated backup archive
        $zip_file = $this->_backup_files_dir.$zip_filename;
        // Time limit for the backup process
        $timeout = 10000; // Default: 5000

        // Instantate an iterator (before creating the zip archive, just
        // in case the zip file is created inside the source folder)
        // and traverse the directory to get the file list.
        $dir_list = new RecursiveDirectoryIterator($source_folder);
        $file_list = new RecursiveIteratorIterator($dir_list);

        // Set script timeout value 
        ini_set('max_execution_time', $timeout);

        // instantate object
        $zip = new ZipArchive();

        // Create and open the archive 
        if ($zip->open($zip_file, ZipArchive::CREATE) !== TRUE)
        {
            $this->_message['error'][] = 'Could not creat and open '.$zip_filename.' archive.';
        }

        // Add each file in the file list to the archive
        foreach ($file_list as $key => $value)
        {
            // Remove the full path structure of the file
            $local_name = str_replace($source_folder, '', $key);
            
            // Get last string of the path either a directory or a file
            $filename = basename($local_name);
            
            // Exclude unnecessary files
            if($filename == '.' || $filename == '..')
                continue;
            
            if(!$zip->addFile(realpath($key), $local_name))
            {
                $this->_message['error'][] = 'ERROR: Could not add file '.$key;
            }
        }
        
        // Close the archive
        $zip->close();
        $this->_message['success'] = 'Archive '.$zip_filename.' created successfully.';
        
        // Save the file basic info
        $this->_message['file']['filename'] = basename($zip_filename, '.zip');
        $this->_message['file']['size'] = $this->_Readable_File_Size(filesize($zip_file));
    }
    
    private function _Delete_Backup($filename='')
    {
        $filename = trim($filename);
        
        $backup_file = $this->_backup_files_dir.$filename;
        
        if(in_array($backup_file, $this->_backup_files_arr))
        {
            if(unlink($backup_file))
            {
                $this->_backup_files_arr = array_diff($this->_backup_files_arr, array($backup_file));
                $this->_message['success'] = 'Deleted '.$filename.' archive.';
                
                // Save the file basic info
                $this->_message['file']['filename'] = basename($filename, '.zip');
            }
            else
                $this->_message['error'][] = 'Unable to delete '.$filename.', please try again later.';
        }
    }
    
    private function _Download_Backup_File($filename='')
    {
        $filename = trim($filename);
        
        $backup_file = $this->_backup_files_dir.$filename;
        
        if(in_array($backup_file, $this->_backup_files_arr))
        {
            header('Content-Type: application/zip');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Content-Transfer-Encoding: binary');
            header('Content-length: '.filesize($backup_file));
            ob_end_clean();
            readfile($backup_file);
            exit();
        }
    }
    
    /*
     * Converts bytes to a human readable filesize
     */
    private function _Readable_File_Size($bytes, $decimals=2)
    {
        $size_arr = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
        
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $size_arr[$factor];
    }
    
    /*
     * Add shortcode hooks
     */
    private function _Setup_Shortcodes()
    {
        
    }
    
    /*
     * Add action hooks
     */
    private function _Setup_Action_Hooks()
    {
        // Add option page in the admin panel
        add_action('admin_menu', array($this, 'Add_Admin_Option_Page'));
        // Register the settings to use on the admin option pages
        add_action('admin_init', array($this, 'Register_Admin_Option_Settings'));
        // Register the settings to use on the user control pages
        add_action('init', array($this, 'Initialization'));
        // Ajax functions
        add_action('wp_ajax_create_backup', array($this, 'Create_Backup'));
        add_action('wp_ajax_delete_backup', array($this, 'Delete_Backup'));
    }
}

$kmi_site_backup = new KMI_Site_Backup();