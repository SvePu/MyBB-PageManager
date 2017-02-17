<?php
/*
	Language admin file for MyBB-PageManager plugin for MyBB 1.8
	Language: english
	Copyright © 2017 Svepu
	Last change: 2017-02-17
*/

$l['pagemanager_info_name']='Page Manager';
$l['pagemanager_info_description']='Allows you to manage additional pages.';
$l['pagemanager_uninstall'] = 'Page Manager - Uninstallation';
$l['pagemanager_uninstall_message'] = 'Do you wish to drop all plugin entries from the database?';
$l['pagemanager_main_title']='Manage Pages';
$l['pagemanager_main_description']='This section allows you to edit and delete additional pages.';
$l['pagemanager_main_table']='Additional Pages';
$l['pagemanager_main_table_enabled']='Enabled';
$l['pagemanager_main_table_disabled']='Disabled';
$l['pagemanager_main_table_id']='ID';
$l['pagemanager_main_table_framework']='MyBB Template?';
$l['pagemanager_main_table_online']='Show online?';
$l['pagemanager_main_table_modified']='Modified';
$l['pagemanager_main_table_dateline']='{1}, {2}';
$l['pagemanager_main_table_no_pages']='No additional pages exist at this time.';
$l['pagemanager_main_open_page']='Open page: ';
$l['pagemanager_main_page_disabled']='Page disabled - You can enable it in options!';
$l['pagemanager_main_edit']='Edit: ';
$l['pagemanager_main_control_edit']='Edit Page';
$l['pagemanager_main_control_export']='Export Page';
$l['pagemanager_main_control_enable']='Enable Page';
$l['pagemanager_main_control_disable']='Disable Page';
$l['pagemanager_main_control_delete']='Delete Page';
$l['pagemanager_main_control_delete_question']='Are you sure you wish to delete this page?';
$l['pagemanager_add_title']='Add New Page';
$l['pagemanager_add_description']='Here you can create a new additional page.';
$l['pagemanager_add_form']='Add New Page';
$l['pagemanager_add_success']='The page has been created successfully.';
$l['pagemanager_import_title']='Import Page';
$l['pagemanager_import_description']='Here you can import new pages.';
$l['pagemanager_import_form']='Import Page';
$l['pagemanager_import_form_file']='Local file';
$l['pagemanager_import_form_file_description']='Select a file to import.';
$l['pagemanager_import_form_name']='Name';
$l['pagemanager_import_form_name_description']='Type a name for the imported page. If left blank, the name in the page file will be used.';
$l['pagemanager_import_form_manual']='Manual import?';
$l['pagemanager_import_form_manual_description']='By default pages are installed directly. Here you can activate manual import.';
$l['pagemanager_import_form_version']='Ignore version?';
$l['pagemanager_import_form_version_description']='Should this page be installed regardless of the version of Page Manager it was created for?';
$l['pagemanager_import_form_action']='Import Page';
$l['pagemanager_import_success']='The selected page has been imported successfully. Please note that imported pages are disabled by default.';
$l['pagemanager_import_error_no_file']='No file was uploaded.';
$l['pagemanager_import_error_php']='PHP returned error code {1} while uploading file. Please contact your server administrator with this error.';
$l['pagemanager_import_error_lost']='The file could not be found on the server.';
$l['pagemanager_import_error_no_contents']='Could not find an importable page with the file you uploaded. Please check the file is the correct and is not corrupt.';
$l['pagemanager_import_error_version']='This page has been written for another version of Page Manager. Please use option "Ignore version" to ignore this error.';
$l['pagemanager_edit_title']='Edit Page';
$l['pagemanager_edit_description']='Here you can edit an additional page.';
$l['pagemanager_edit_form']='Edit Page';
$l['pagemanager_edit_form_name']='Name';
$l['pagemanager_edit_form_name_description']='The name of your additional page.';
$l['pagemanager_edit_form_url']='URI parameter';
$l['pagemanager_edit_form_url_description']='This parameter will be used to point to your page. <strong>It is recommended to use alphanumeric characters only.</strong>';
$l['pagemanager_edit_form_framework']='Use MyBB Template?';
$l['pagemanager_edit_form_framework_description']='Set this option to yes, if you want to include MyBB header and footer automatically. <strong>This will disable the possibility to use PHP in page content!</strong>';
$l['pagemanager_edit_form_template']='Page content';
$l['pagemanager_edit_form_template_description']='Type your page content here.';
$l['pagemanager_edit_form_online']='Show in "Who is Online"?';
$l['pagemanager_edit_form_online_description']='Set this option to no, if you want to hide this page in "Who is Online"';
$l['pagemanager_edit_form_enable']='Page enabled?';
$l['pagemanager_edit_form_enable_description']='If you wish to disable this page, set this option to no.';
$l['pagemanager_edit_form_continue']='Save and Continue Editing';
$l['pagemanager_edit_form_close']='Save and Return to Listing';
$l['pagemanager_edit_success']='The selected page has been updated successfully.';
$l['pagemanager_edit_success_nothing']='The selected page has been updated successfully. But nothing changed.';
$l['pagemanager_edit_error_name']='Name can not be empty';
$l['pagemanager_edit_error_url']='URI parameter can not be empty';
$l['pagemanager_edit_error_url_duplicate']='URI parameter is already taken';
$l['pagemanager_edit_error_template']='Page content can not be empty';
$l['pagemanager_enable_success']='The selected page has been enabled successfully.';
$l['pagemanager_disable_success']='The selected page has been disabled successfully.';
$l['pagemanager_delete_success']='The selected page has been deleted successfully.';
$l['pagemanager_invalid_page']='The specified page does not exist.';
$l['pagemanager_install_error']='Your installation of Page Manager is out of date or corrupt. If possible export all pages and install the plugin again.';
$l['pagemanager_can_manage_pages']='Can manage additional pages?';
