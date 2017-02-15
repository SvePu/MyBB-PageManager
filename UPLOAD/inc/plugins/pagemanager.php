<?php

/*
Page Manager Plugin for MyBB
Copyright (C) 2010 Sebastian Wunderlich
Edited for MyBB 1.8 & maintained by Svepu

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if(!defined('IN_MYBB'))
{
	die('This file cannot be accessed directly.');
}

if(THIS_SCRIPT == 'misc.php')
{
	global $mybb, $cache;
	$pagecache = $cache->read('pages');
	if($mybb->input['page'] && isset($pagecache[$mybb->input['page']]) && $pagecache[$mybb->input['page']]['online'] != 1)
	{
		define('NO_ONLINE',1);
	}
}

if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_config_action_handler','pagemanager_admin_action');
	$plugins->add_hook('admin_config_menu','pagemanager_admin_menu');
	$plugins->add_hook('admin_config_permissions','pagemanager_admin_permissions');
	$plugins->add_hook('admin_load','pagemanager_admin');
}
else
{
	$plugins->add_hook('misc_start','pagemanager');
	$plugins->add_hook('build_friendly_wol_location_end','pagemanager_online');
}

function pagemanager_info()
{
	global $lang;
	$lang->load("config_pagemanager");
	
	$editedby = '*Edited for MyBB 1.8 &amp; maintained by: <a href="https://community.mybb.com/user-91011.html" target="_blank">SvePu</a>';
	$sources = '*Sources: <a href="https://github.com/SvePu/MyBB-PageManager" target="_blank">GitHub</a>';
	
	
	$info = array
	(
		'name'		=>	$lang->pagemanager_info_name,
		'description'	=>	$lang->pagemanager_info_description,
		'website'	=>	'https://community.mybb.com/thread-208230.html',
		'author'	=>	'Sebastian "querschlaeger" Wunderlich',
		'authorsite'	=>	'',
		'version'	=>	'2.0.1',
		'codename'	=>	'mybbpagemanager',
		'compatibility'	=>	'18*'
	);
	
	$info['description'] = $info['description'].'<br />'.$editedby.'<br />'.$sources;
	
	return $info;
}

function pagemanager_activate()
{
	change_admin_permission('tools','pagemanager');
	pagemanager_cache();
}

function pagemanager_deactivate()
{
	change_admin_permission('tools','pagemanager',-1);
	pagemanager_cache(true);
}

function pagemanager_install()
{
	global $db;
	pagemanager_uninstall();
	if($db->engine=='mysql'||$db->engine=='mysqli')
	{
		$db->query("CREATE TABLE `".TABLE_PREFIX."pages` (
  `pid` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(120) NOT NULL default '',
  `url` varchar(30) NOT NULL default '',
  `framework` int(1) NOT NULL default '0',
  `template` text NOT NULL,
  `online` int(1) NOT NULL default '1',
  `enabled` int(1) NOT NULL default '1',
  `dateline` bigint(30) NOT NULL default '0',
  PRIMARY KEY (`pid`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM".$db->build_create_table_collation());
	}
}

function pagemanager_is_installed()
{
	global $db;
	if($db->table_exists('pages'))
	{
		$fields=$db->show_fields_from('pages');
		$list = array();
		$check = array
		(
			'pid',
			'name',
			'url',
			'framework',
			'template',
			'online',
			'enabled',
			'dateline'
		);
		foreach($fields as $key=>$val)
		{
			array_push($list,$val['Field']);
		}
		$diff = array_diff($check,$list);
		if(empty($diff))
		{
			return true;
		}
	}
	return false;
}

function pagemanager_uninstall()
{
	global $db;
	$db->drop_table('pages');
}

function pagemanager_admin_action($action)
{
	$action['pagemanager'] = array
	(
		'active'=>'pagemanager'
	);
	return $action;
}

function pagemanager_admin_menu($sub_menu)
{
	global $lang;
	$lang->load("config_pagemanager");
	end($sub_menu);
	$key=(key($sub_menu))+10;
	$sub_menu[$key] = array
	(
		'id'=>'pagemanager',
		'title'=>$lang->pagemanager_info_name,
		'link'=>'index.php?module=config-pagemanager'
	);
	return $sub_menu;
}

function pagemanager_admin_permissions($admin_permissions)
{
	global $lang;
	$lang->load("config_pagemanager");
	$admin_permissions['pagemanager'] = $lang->pagemanager_can_manage_pages;
	return $admin_permissions;
}

function pagemanager_admin()
{
	global $mybb,$page,$db,$lang;
	$lang->load("config_pagemanager");
	if($page->active_action != 'pagemanager')
	{
		return false;
	}
	$info = pagemanager_info();
	$sub_tabs['pagemanager'] = array
	(
		'title'=>$lang->pagemanager_main_title,
		'link'=>'index.php?module=config-pagemanager',
		'description'=>$lang->pagemanager_main_description
	);
	$sub_tabs['pagemanager_add'] = array
	(
		'title'=>$lang->pagemanager_add_title,
		'link'=>'index.php?module=config-pagemanager&amp;action=add',
		'description'=>$lang->pagemanager_add_description
	);
	$sub_tabs['pagemanager_import'] = array
	(
		'title'=>$lang->pagemanager_import_title,
		'link'=>'index.php?module=config-pagemanager&amp;action=import',
		'description'=>$lang->pagemanager_import_description
	);
	if(!$mybb->input['action'])
	{
		$page->add_breadcrumb_item($lang->pagemanager_info_name);
		$page->output_header($lang->pagemanager_info_name);
		if(!pagemanager_is_installed())
		{
			$page->output_error('<p><em>'.$lang->pagemanager_install_error.'</em></p>');
		}
		$page->output_nav_tabs($sub_tabs,'pagemanager');
		$table=new Table;
		$table->construct_header($lang->name);
		$table->construct_header($lang->pagemanager_main_table_id);
		$table->construct_header($lang->pagemanager_main_table_framework);
		$table->construct_header($lang->pagemanager_main_table_online);
		$table->construct_header($lang->pagemanager_main_table_modified);
		$table->construct_header($lang->controls);
		$query=$db->simple_select('pages','*','', array('order_by'=>'name', 'order_dir'=>'ASC'));
		if($db->num_rows($query)>0)
		{
			while($pages=$db->fetch_array($query))
			{
				if($mybb->input['highlight']==$pages['pid'])
				{
					$highlight = array('style'=>'font-size: inherit; background:#fffbd9');
				}
				else
				{
					$highlight = array('style'=>'font-size: inherit;');
				}
				if($pages['enabled'])
				{
					$status_icon='<img src="styles/'.$page->style.'/images/icons/page_active.png" alt="'.$lang->pagemanager_main_table_enabled.'" title="'.$lang->pagemanager_main_table_enabled.'" style="vertical-align:middle;" /> ';
					$status_lang=$lang->pagemanager_main_control_disable;
					$status_action='disable';
					$pagelink = '<br /><small>'.$lang->pagemanager_main_open_page.'<a href="'.$mybb->settings['bburl'].'/misc.php?page='.$pages['url'].'" target="_blank">'.$mybb->settings['bburl'].'/misc.php?page='.$pages['url'].'</a></small>'; 
				}
				else
				{
					$status_icon='<img src="styles/'.$page->style.'/images/icons/page_inactive.png" alt="'.$lang->pagemanager_main_table_disabled.'" title="'.$lang->pagemanager_main_table_disabled.'" style="vertical-align:middle;" /> ';
					$status_lang=$lang->pagemanager_main_control_enable;
					$status_action='enable';
					$pagelink = '<br /><span style="color:#f00">'.$lang->pagemanager_main_page_disabled.'</span>';
				}
				if($pages['framework'])
				{
					$framework_status=$lang->yes;
				}
				else
				{
					$framework_status=$lang->no;
				}
				if($pages['online'])
				{
					$online_status=$lang->yes;
				}
				else
				{
					$online_status=$lang->no;
				}
				$table->construct_cell($status_icon.'<strong><a href="'.$sub_tabs['pagemanager']['link'].'&amp;action=edit&amp;pid='.$pages['pid'].'" title="'.$lang->pagemanager_main_edit.$pages['name'].'">'.$pages['name'].'</a></strong>'.$pagelink, $highlight);
				$table->construct_cell($pages['pid'],$highlight);
				$table->construct_cell($framework_status,$highlight);
				$table->construct_cell($online_status,$highlight);
				$table->construct_cell($lang->sprintf($lang->pagemanager_main_table_dateline,my_date($mybb->settings['dateformat'],$pages['dateline']),my_date($mybb->settings['timeformat'],$pages['dateline'])),$highlight);
				$popup=new PopupMenu('page_'.$pages['pid'],$lang->options);
				$popup->add_item($lang->pagemanager_main_control_edit,$sub_tabs['pagemanager']['link'].'&amp;action=edit&amp;pid='.$pages['pid']);
				$popup->add_item($lang->pagemanager_main_control_export,$sub_tabs['pagemanager']['link'].'&amp;action=export&amp;pid='.$pages['pid']);
				$popup->add_item($status_lang,$sub_tabs['pagemanager']['link'].'&amp;action='.$status_action.'&amp;pid='.$pages['pid'].'&amp;my_post_key='.$mybb->post_code);
				$popup->add_item($lang->pagemanager_main_control_delete,$sub_tabs['pagemanager']['link'].'&amp;action=delete&amp;pid='.$pages['pid'].'&amp;my_post_key='.$mybb->post_code,'return AdminCP.deleteConfirmation(this,\''.$lang->pagemanager_main_control_delete_question.'\')');
				$table->construct_cell($popup->fetch(),$highlight);
				$table->construct_row();
			}
		}
		else
		{
			$table->construct_cell($lang->pagemanager_main_table_no_pages,array('colspan'=>6));
			$table->construct_row();
		}
		$table->output($lang->pagemanager_main_table);
		$page->output_footer();
	}
	if($mybb->input['action']=='add')
	{
		if($mybb->request_method=='post')
		{
			if($mybb->input['import'])
			{
				if(!$_FILES['file']||$_FILES['file']['error']==4)
				{
					$error=$lang->pagemanager_import_error_no_file;
				}
				elseif($_FILES['file']['error'])
				{
					$error=$lang->sprintf($lang->pagemanager_import_error_php,$_FILES['file']['error']);
				}
				else
				{
					if(!is_uploaded_file($_FILES['file']['tmp_name']))
					{
						$error=$lang->pagemanager_import_error_lost;
					}
					else
					{
						$contents=@file_get_contents($_FILES['file']['tmp_name']);
						@unlink($_FILES['file']['tmp_name']);
						if(!trim($contents))
						{
							$error=$lang->pagemanager_import_error_no_contents;
						}
					}
				}
				if(!$error)
				{
					require_once MYBB_ROOT.'inc/class_xml.php';
					$parser=new XMLParser($contents);
					$tree=$parser->get_tree();
					if(!is_array($tree)||!is_array($tree['pagemanager'])||!is_array($tree['pagemanager']['attributes'])||!is_array($tree['pagemanager']['page']))
					{
						$error=$lang->pagemanager_import_error_no_contents;
					}
					if(!$error)
					{
						foreach($tree['pagemanager']['page'] as $property=>$value)
						{
							if($property=='tag'||$property=='value')
							{
								continue;
							}
							$input_array[$property]=$value['value'];
						}
						if(!$mybb->input['version']&&$info['version']!=$tree['pagemanager']['attributes']['version'])
						{
							$error=$lang->pagemanager_import_error_version;
						}
						if($mybb->input['name_overwrite'])
						{
							$input_array['name']=$mybb->input['name_overwrite'];
						}
						$form_array=pagemanager_setinput($input_array,true);
						if(!$form_array['name']||!$form_array['url']||!$form_array['template'])
						{
							$error=$lang->pagemanager_import_error_no_contents;
						}
					}
				}
				if($error)
				{
					flash_message($error,'error');
					admin_redirect($sub_tabs['pagemanager']['link'].'&amp;action=import');
				}
			}
			else
			{
				$form_array=pagemanager_setinput($mybb->input);
			}
			$querycheck=$db->simple_select('pages','pid','url="'.$db->escape_string($form_array['url']).'"');
			$check=$db->fetch_array($querycheck);
			if(!$form_array['name'])
			{
				$errors[]=$lang->pagemanager_edit_error_name;
			}
			if(!$form_array['url'])
			{
				$errors[]=$lang->pagemanager_edit_error_url;
			}
			if($check['pid'])
			{
				$errors[]=$lang->pagemanager_edit_error_url_duplicate;
			}
			if(!$form_array['template'])
			{
				$errors[]=$lang->pagemanager_edit_error_template;
			}
			if(!$errors&&!$mybb->input['manual'])
			{
				$updated_page = array
				(
					'name'=>$db->escape_string($form_array['name']),
					'url'=>$db->escape_string($form_array['url']),
					'framework'=>$form_array['framework'],
					'template'=>$db->escape_string($form_array['template']),
					'online'=>$form_array['online'],
					'enabled'=>$form_array['enabled'],
					'dateline'=>TIME_NOW,
				);
				$db->insert_query('pages',$updated_page);
				$query=$db->simple_select('pages','*','url="'.$db->escape_string($form_array['url']).'"');
				$pages=$db->fetch_array($query);
				pagemanager_cache();
				if($mybb->input['import'])
				{
					flash_message($lang->pagemanager_import_success,'success');
				}
				else
				{
					flash_message($lang->pagemanager_add_success,'success');
				}
				admin_redirect($sub_tabs['pagemanager']['link'].'&amp;highlight='.$pages['pid']);
			}
		}
		else
		{
			$form_array=pagemanager_setinput();
		}
		$queryadmin=$db->simple_select('adminoptions','*','uid='.$mybb->user['uid']);
		$admin_options=$db->fetch_array($queryadmin);
		if($admin_options['codepress']!=0)
		{
			$page->extra_header='<link href="./jscripts/codemirror/lib/codemirror.css" rel="stylesheet">
<link href="./jscripts/codemirror/theme/mybb.css?ver=1804" rel="stylesheet">
<script src="./jscripts/codemirror/lib/codemirror.js"></script>
<script src="./jscripts/codemirror/mode/xml/xml.js"></script>
<script src="./jscripts/codemirror/mode/javascript/javascript.js"></script>
<script src="./jscripts/codemirror/mode/css/css.js"></script>
<script src="./jscripts/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="./jscripts/codemirror/mode/clike/clike.js"></script>
<script src="./jscripts/codemirror/mode/php/php.js"></script>
<link href="./jscripts/codemirror/addon/dialog/dialog-mybb.css" rel="stylesheet">
<script src="./jscripts/codemirror/addon/dialog/dialog.js"></script>
<script src="./jscripts/codemirror/addon/search/searchcursor.js"></script>
<script src="./jscripts/codemirror/addon/search/search.js?ver=1808"></script>
<script src="./jscripts/codemirror/addon/fold/foldcode.js"></script>
<script src="./jscripts/codemirror/addon/fold/xml-fold.js"></script>
<script src="./jscripts/codemirror/addon/fold/foldgutter.js"></script>
<link href="./jscripts/codemirror/addon/fold/foldgutter.css" rel="stylesheet">';
		}
		$page->add_breadcrumb_item($lang->pagemanager_info_name,$sub_tabs['pagemanager']['link']);
		$page->add_breadcrumb_item($sub_tabs['pagemanager_add']['title']);
		$page->output_header($lang->pagemanager_info_name.' - '.$sub_tabs['pagemanager_add']['title']);
		if(!pagemanager_is_installed())
		{
			$page->output_error('<p><em>'.$lang->pagemanager_install_error.'</em></p>');
		}
		$page->output_nav_tabs($sub_tabs,'pagemanager_add');
		$form=new Form($sub_tabs['pagemanager_add']['link'],'post','add_template');
		if($errors)
		{
			$page->output_inline_error($errors);
		}
		$form_container=new FormContainer($lang->pagemanager_add_form);
		$form_container->output_row($lang->pagemanager_edit_form_name.' <em>*</em>',$lang->pagemanager_edit_form_name_description,$form->generate_text_box('name',$form_array['name'],array('id'=>'name')),'name');
		$form_container->output_row($lang->pagemanager_edit_form_url.' <em>*</em>',$lang->pagemanager_edit_form_url_description,$form->generate_text_box('url',$form_array['url'],array('id'=>'url')),'url');
		$form_container->output_row($lang->pagemanager_edit_form_framework,$lang->pagemanager_edit_form_framework_description,$form->generate_yes_no_radio('framework',$form_array['framework']));
		$form_container->output_row($lang->pagemanager_edit_form_template.' <em>*</em>',$lang->pagemanager_edit_form_template_description,$form->generate_text_area('template',$form_array['template'],array('id'=>'template','style'=>'width:100%;height:500px;')));
		$form_container->output_row($lang->pagemanager_edit_form_online,$lang->pagemanager_edit_form_online_description,$form->generate_yes_no_radio('online',$form_array['online']));
		$form_container->output_row($lang->pagemanager_edit_form_enable,$lang->pagemanager_edit_form_enable_description,$form->generate_yes_no_radio('enabled',$form_array['enabled']));
		$form_container->end();
		$buttons[]=$form->generate_submit_button($lang->pagemanager_edit_form_close);
		$form->output_submit_wrapper($buttons);
		$form->end();
		if($admin_options['codepress']!=0)
		{
			echo '<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("template"), {
	lineNumbers: true,
	lineWrapping: true,
	foldGutter: true,
	gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
	viewportMargin: Infinity,
	indentWithTabs: true,
	indentUnit: 4,
	mode: "php",
	theme: "mybb"
});
</script>';
		}
		$page->output_footer();
	}
	if($mybb->input['action']=='import')
	{
		$page->add_breadcrumb_item($lang->pagemanager_info_name,$sub_tabs['pagemanager']['link']);
		$page->add_breadcrumb_item($sub_tabs['pagemanager_import']['title']);
		$page->output_header($lang->pagemanager_info_name.' - '.$sub_tabs['pagemanager_import']['title']);
		if(!pagemanager_is_installed())
		{
			$page->output_error('<p><em>'.$lang->pagemanager_install_error.'</em></p>');
		}
		$page->output_nav_tabs($sub_tabs,'pagemanager_import');
		$form=new Form($sub_tabs['pagemanager_add']['link'],'post','',1);
		$form_container=new FormContainer($lang->pagemanager_import_form);
		$form_container->output_row($lang->pagemanager_import_form_file.' <em>*</em>',$lang->pagemanager_import_form_file_description,$form->generate_file_upload_box('file'));
		$form_container->output_row($lang->pagemanager_import_form_name,$lang->pagemanager_import_form_name_description,$form->generate_text_box('name_overwrite','',array('id'=>'name_overwrite')),'name_overwrite');
		$form_container->output_row($lang->pagemanager_import_form_manual,$lang->pagemanager_import_form_manual_description,$form->generate_on_off_radio('manual',0));
		$form_container->output_row($lang->pagemanager_import_form_version,$lang->pagemanager_import_form_version_description,$form->generate_yes_no_radio('version',0));
		$form_container->end();
		$buttons[]=$form->generate_submit_button($lang->pagemanager_import_form_action,array('name'=>'import'));
		$form->output_submit_wrapper($buttons);
		$form->end();
		$page->output_footer();
	}
	if($mybb->input['action']=='edit')
	{
		$query=$db->simple_select('pages','*','pid='.intval($mybb->input['pid']));
		$pages=$db->fetch_array($query);
		if(!$pages['pid'])
		{
			flash_message($lang->pagemanager_invalid_page,'error');
			admin_redirect($sub_tabs['pagemanager']['link']);
		}
		if($mybb->request_method=='post')
		{
			$form_array=pagemanager_setinput($mybb->input);
			$querycheck=$db->simple_select('pages','pid','url="'.$db->escape_string($form_array['url']).'" AND pid != '.$pages['pid']);
			$check=$db->fetch_array($querycheck);
			if(!$form_array['name'])
			{
				$errors[]=$lang->pagemanager_edit_error_name;
			}
			if(!$form_array['url'])
			{
				$errors[]=$lang->pagemanager_edit_error_url;
			}
			if($check['pid'])
			{
				$errors[]=$lang->pagemanager_edit_error_url_duplicate;
			}
			if(!$form_array['template'])
			{
				$errors[]=$lang->pagemanager_edit_error_template;
			}
			if(!$errors)
			{
				if($form_array['name']==$pages['name']&&$form_array['url']==$pages['url']&&$form_array['framework']==$pages['framework']&&$form_array['template']==$pages['template']&&$form_array['online']==$pages['online'])
				{
					$modified=$pages['dateline'];
					if($form_array['enabled']==$pages['enabled'])
					{
						$update_lang=$lang->pagemanager_edit_success_nothing;
					}
					else
					{
						if($form_array['enabled'])
						{
							$update_lang=$lang->pagemanager_enable_success;
						}
						else
						{
							$update_lang=$lang->pagemanager_disable_success;
						}
					}
				}
				else
				{
					$modified=TIME_NOW;
					$update_lang=$lang->pagemanager_edit_success;
				}
				$updated_page = array
				(
					'name'=>$db->escape_string($form_array['name']),
					'url'=>$db->escape_string($form_array['url']),
					'framework'=>$form_array['framework'],
					'template'=>$db->escape_string($form_array['template']),
					'online'=>$form_array['online'],
					'enabled'=>$form_array['enabled'],
					'dateline'=>$modified,
				);
				$db->update_query('pages',$updated_page,'pid='.$pages['pid']);
				pagemanager_cache();
				flash_message($update_lang,'success');
				if($mybb->input['continue'])
				{
					admin_redirect($sub_tabs['pagemanager']['link'].'&amp;action=edit&amp;pid='.$pages['pid']);
				}
				else
				{
					admin_redirect($sub_tabs['pagemanager']['link'].'&amp;highlight='.$pages['pid']);
				}
			}
		}
		else
		{
			$form_array=pagemanager_setinput($pages);
		}
		$queryadmin=$db->simple_select('adminoptions','*','uid='.$mybb->user['uid']);
		$admin_options=$db->fetch_array($queryadmin);
		$sub_tabs['pagemanager_edit'] = array
		(
			'title'=>$lang->pagemanager_edit_title,
			'link'=>'index.php?module=config-pagemanager&amp;action=edit&amp;pid='.$pages['pid'],
			'description'=>$lang->pagemanager_edit_description
		);
		if($admin_options['codepress']!=0)
		{
			$page->extra_header='<link href="./jscripts/codemirror/lib/codemirror.css" rel="stylesheet">
<link href="./jscripts/codemirror/theme/mybb.css?ver=1804" rel="stylesheet">
<script src="./jscripts/codemirror/lib/codemirror.js"></script>
<script src="./jscripts/codemirror/mode/xml/xml.js"></script>
<script src="./jscripts/codemirror/mode/javascript/javascript.js"></script>
<script src="./jscripts/codemirror/mode/css/css.js"></script>
<script src="./jscripts/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="./jscripts/codemirror/mode/clike/clike.js"></script>
<script src="./jscripts/codemirror/mode/php/php.js"></script>
<link href="./jscripts/codemirror/addon/dialog/dialog-mybb.css" rel="stylesheet">
<script src="./jscripts/codemirror/addon/dialog/dialog.js"></script>
<script src="./jscripts/codemirror/addon/search/searchcursor.js"></script>
<script src="./jscripts/codemirror/addon/search/search.js?ver=1808"></script>
<script src="./jscripts/codemirror/addon/fold/foldcode.js"></script>
<script src="./jscripts/codemirror/addon/fold/xml-fold.js"></script>
<script src="./jscripts/codemirror/addon/fold/foldgutter.js"></script>
<link href="./jscripts/codemirror/addon/fold/foldgutter.css" rel="stylesheet">';
		}
		$page->add_breadcrumb_item($lang->pagemanager_info_name,$sub_tabs['pagemanager']['link']);
		$page->add_breadcrumb_item($sub_tabs['pagemanager_edit']['title']);
		$page->output_header($lang->pagemanager_info_name.' - '.$sub_tabs['pagemanager_edit']['title']);
		if(!pagemanager_is_installed())
		{
			$page->output_error('<p><em>'.$lang->pagemanager_install_error.'</em></p>');
		}
		$page->output_nav_tabs($sub_tabs,'pagemanager_edit');
		$form=new Form($sub_tabs['pagemanager_edit']['link'],'post','edit_template');
		if($errors)
		{
			$page->output_inline_error($errors);
		}
		$form_container=new FormContainer($lang->pagemanager_edit_form);
		$form_container->output_row($lang->pagemanager_edit_form_name.' <em>*</em>',$lang->pagemanager_edit_form_name_description,$form->generate_text_box('name',$form_array['name'],array('id'=>'name')),'name');
		$form_container->output_row($lang->pagemanager_edit_form_url.' <em>*</em>',$lang->pagemanager_edit_form_url_description,$form->generate_text_box('url',$form_array['url'],array('id'=>'url')),'url');
		$form_container->output_row($lang->pagemanager_edit_form_framework,$lang->pagemanager_edit_form_framework_description,$form->generate_yes_no_radio('framework',$form_array['framework']));
		$form_container->output_row($lang->pagemanager_edit_form_template.' <em>*</em>',$lang->pagemanager_edit_form_template_description,$form->generate_text_area('template',$form_array['template'],array('id'=>'template','style'=>'width:100%;height:500px;')));
		$form_container->output_row($lang->pagemanager_edit_form_online,$lang->pagemanager_edit_form_online_description,$form->generate_yes_no_radio('online',$form_array['online']));
		$form_container->output_row($lang->pagemanager_edit_form_enable,$lang->pagemanager_edit_form_enable_description,$form->generate_yes_no_radio('enabled',$form_array['enabled']));
		$form_container->end();
		$buttons[]=$form->generate_submit_button($lang->pagemanager_edit_form_continue,array('name'=>'continue'));
		$buttons[]=$form->generate_submit_button($lang->pagemanager_edit_form_close,array('name'=>'close'));
		$form->output_submit_wrapper($buttons);
		$form->end();
		if($admin_options['codepress']!=0)
		{
			echo '<script type="text/javascript">
var editor = CodeMirror.fromTextArea(document.getElementById("template"), {
	lineNumbers: true,
	lineWrapping: true,
	foldGutter: true,
	gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
	viewportMargin: Infinity,
	indentWithTabs: true,
	indentUnit: 4,
	mode: "php",
	theme: "mybb"
});
</script>';
		}
		$page->output_footer();
	}
	if($mybb->input['action']=='export')
	{
		$query=$db->simple_select('pages','*','pid='.intval($mybb->input['pid']));
		$pages=$db->fetch_array($query);
		if(!$pages['pid'])
		{
			flash_message($lang->pagemanager_invalid_page,'error');
			admin_redirect($sub_tabs['pagemanager']['link']);
		}
		$extra_xml='';
		if($pages['framework'])
		{
			$extra_xml.='
		<framework>'.$pages['framework'].'</framework>';
		}
		if(isset($pages['online'])&&$pages['online']==0)
		{
			$extra_xml.='
		<online>'.$pages['online'].'</online>';
		}
		$xml='<?xml version="1.0" encoding="'.$lang->settings['charset'].'"?>
<pagemanager version="'.$info['version'].'" xmlns="'.$info['website'].'">
	<page>
		<name><![CDATA['.$pages['name'].']]></name>
		<url><![CDATA['.$pages['url'].']]></url>
		<template><![CDATA['.base64_encode($pages['template']).']]></template>
		<checksum>'.md5($pages['template']).'</checksum>'.$extra_xml.'
	</page>
</pagemanager>';
		header('Content-Disposition: attachment; filename='.rawurlencode($pages['url']).'.xml');
		header('Content-Type: application/xhtml+xml');
		header('Content-Length: '.strlen($xml));
		header('Pragma: no-cache');
		header('Expires: 0');
		echo $xml;
	}
	if($mybb->input['action']=='enable'||$mybb->input['action']=='disable')
	{
		$highlight='&amp;highlight='.intval($mybb->input['pid']);
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			$highlight='';
			flash_message($lang->invalid_post_verify_key2,'error');
		}
		else
		{
			$query=$db->simple_select('pages','pid','pid='.intval($mybb->input['pid']));
			$pages=$db->fetch_array($query);
			if(!$pages['pid'])
			{
				$highlight='';
				flash_message($lang->pagemanager_invalid_page,'error');
			}
			else
			{
				if($mybb->input['action']=='enable')
				{
					$status_lang=$lang->pagemanager_enable_success;
					$status_action = array('enabled'=>1);
				}
				else
				{
					$status_lang=$lang->pagemanager_disable_success;
					$status_action = array('enabled'=>0);
				}
				$db->update_query('pages',$status_action,'pid='.$pages['pid']);
				pagemanager_cache();
				flash_message($status_lang,'success');
			}
		}
		admin_redirect($sub_tabs['pagemanager']['link'].$highlight);
	}
	if($mybb->input['action']=='delete')
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			flash_message($lang->invalid_post_verify_key2,'error');
		}
		else
		{
			$query=$db->simple_select('pages','pid','pid='.intval($mybb->input['pid']));
			$pages=$db->fetch_array($query);
			if(!$pages['pid'])
			{
				flash_message($lang->pagemanager_invalid_page,'error');
			}
			else
			{
				$db->delete_query('pages','pid='.$pages['pid']);
				pagemanager_cache();
				flash_message($lang->pagemanager_delete_success,'success');
			}
		}
		admin_redirect($sub_tabs['pagemanager']['link']);
	}
	exit();
}

function pagemanager()
{
	global $mybb,$cache;
	$pagecache=$cache->read('pages');
	if($mybb->input['page'] && !isset($pagecache[$mybb->input['page']]))
	{
		global  $db, $lang;
		$lang->load("pagemanager");
		redirect("index.php", $lang->pagemanager_page_disabled_redirect, '', true);
		exit();
	}
	
	if($mybb->input['page'] && isset($pagecache[$mybb->input['page']]))
	{
		global $db;
		$query=$db->simple_select('pages','*','pid='.$pagecache[$mybb->input['page']]['pid']);
		$pages=$db->fetch_array($query);
		if($pages['framework'])
		{
			global $headerinclude,$header,$theme,$footer;
			$template='<html>
<head>
<title>'.$pages['name'].' - '.$mybb->settings['bbname'].'</title>
{$headerinclude}
</head>
<body>
{$header}
'.$pages['template'].'
{$footer}
</body>
</html>';
			$template=str_replace("\\'","'",addslashes($template));
			add_breadcrumb($pages['name']);
			eval("\$page=\"".$template."\";");
			output_page($page);
		}
		else
		{
			eval('?>'.$pages['template']);
		}
		exit();		
	}
}

function pagemanager_online(&$plugin_array)
{
	if($plugin_array['user_activity']['activity']=='misc'&&my_strpos($plugin_array['user_activity']['location'],'page='))
	{
		global $cache;
		$pagecache=$cache->read('pages');
		$location=parse_url($plugin_array['user_activity']['location']);
		while(my_strpos($location['query'],'&amp;'))
		{
			$location['query']=html_entity_decode($location['query']);
		}
		$var=explode('&',$location['query']);
		foreach($var as $val)
		{
			$param=explode('=',$val);
			$list[$param[0]]=$param[1];
		}
		if(isset($pagecache[$list['page']]))
		{
			global $lang;
			$lang->load("pagemanager");
			$plugin_array['location_name']=$lang->sprintf($lang->pagemanager_online,$pagecache[$list['page']]['url'],$pagecache[$list['page']]['name']);
		}
	}
}

function pagemanager_cache($clear=false)
{
	global $cache;
	if($clear==true)
	{
		$cache->update('pages',false);
	}
	else
	{
		global $db;
		$pages = array();
		$query=$db->simple_select('pages','pid,name,url,online','enabled=1');
		while($page=$db->fetch_array($query))
		{
			$pages[$page['url']] = $page;
		}
		$cache->update('pages',$pages);
	}
}

function pagemanager_setinput($input=false,$import=false)
{
	$default=array
	(
		'name'=>'',
		'url'=>'',
		'framework'=>0,
		'template'=>'',
		'online'=>1,
		'enabled'=>1
	);
	if($input!=false)
	{
		if($input['name'])
		{
			$default['name']=trim(my_substr($input['name'],0,120));
		}
		if($input['url'])
		{
			$default['url']=trim(my_substr($input['url'],0,30));
		}
		if($input['framework']==1)
		{
			$default['framework']=1;
		}
		if($input['template'])
		{
			if($import==true)
			{
				if($input['checksum'])
				{
					if(my_strtolower(md5(base64_decode($input['template']))) == my_strtolower($input['checksum']))
					{
						$default['template']=trim(base64_decode($input['template']));
					}
				}
				else
				{
					$default['template']=trim($input['template']);
				}
			}
			else
			{
				$default['template']=trim($input['template']);
			}
		}
		if(isset($input['online'])&&$input['online']==0)
		{
			$default['online']=0;
		}
		if($input['enabled']==0||$import==true)
		{
			$default['enabled']=0;
		}
	}
	return $default;
}
