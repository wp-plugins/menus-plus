<?php
/*
Plugin Name: Menus Plus+
Plugin URI: http://www.keighl.com/plugins/menus-plus/
Description: Create a customized list of pages and categories in any order you want! To return the list use the template tag <code>&lt;?php menusplus(); ?&gt;</code></code> in your template. <a href="themes.php?page=menusplus">Configuration Page</a>
Version: 1.0
Author: Kyle Truscott
Author URI: http://www.keighl.com
*/


/*  Copyright 2009 Kyle Truscott  (email : keighl@keighl.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


$mp_version = "1.0";
update_option('mp_version', $mp_version);


register_activation_hook(__FILE__,'menusplus_install');

add_action('admin_menu', 'menusplus_add_admin');

add_action("admin_print_scripts", 'menusplus_js_libs');

add_action('wp_ajax_menusplus_list', 'menusplus_list');
add_action('wp_ajax_menusplus_add', 'menusplus_add');
add_action('wp_ajax_menusplus_sort', 'menusplus_sort');
add_action('wp_ajax_menusplus_remove', 'menusplus_remove');

// Template tags

function menusplus() {

	global $wpdb;
	$table_name = $wpdb->prefix . "menusplus";
	$wpdb->show_errors();
	
	$mp_display_children = get_option("mp_display_children");
	
	$items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY list_order ASC", ARRAY_A);
	
	if (count($items) > 0) :
		foreach ($items as $item) :
		  
		  $id = $item['id'];
		  $wp_id = $item['wp_id'];
		  $list_order = $item['list_order'];
		  $type = $item['type'];
		  $class = $item['class'];
		  $url = $item['url'];
		  $label = $item['label'];
		  
		  if ($type == 'page') :
			
			if ($mp_display_children == "1") :
				
				$children = get_pages("child_of=$wp_id");
				
				foreach ($children as $child) :
					$wp_id = $wp_id . "," . $child->ID;
				endforeach;
				
			endif;
			
			wp_list_pages("title_li=&include=$wp_id");
			
		  elseif ($type == 'cat') :
		  
		  	if ($mp_display_children == "1") :
				
				$children = get_categories("child_of=$wp_id&hide_empty=0");
								
				foreach ($children as $child) :
					$wp_id = $wp_id . "," . $child->cat_ID;
				endforeach;
				
			endif;
		  
		  	wp_list_categories("include=$wp_id&title_li=&hide_empty=0");
			
		  elseif ($type == 'url') :
		  
		  	echo "<li class=\"$class\">";
			echo "<a href=\"$url\">$label</a>";
			echo "</li>";
			
		  else :
		  
		  endif;
		  
		endforeach;
    endif;

}


// Views

function menusplus_admin() {

	menusplus_js();
	menusplus_style();
	
	?> 
	
	<div class="wrap menusplus">
    	<div class="wrap">
        	<h2>Menus Plus +</h2>
            <p><h4>v. <?php echo get_option('mp_version'); ?> | <a href="http://www.keighl.com">Keighl</a></h4></p>
        </div>
      <div class="wrap menusplus_adding">
      	
        <div class="add_box postbox"> 
			<table cellspacing="6" cellpadding="0">
              <tr>
                <td align="right"><?php wp_dropdown_pages(); ?></td>
                <td><input type="button" class="menusplus_add" id="mp_add_page" value="Add page"></td>
              </tr>
              <tr>
                <td align="right"><?php wp_dropdown_categories('hide_empty=0'); ?></td>
                <td><input type="button" class="menusplus_add" id="mp_add_cat" value="Add Category"></td>
              </tr>
              <tr>
              	<form method="post" action="options.php">
                <td align="right">
                	
                    
                    <input type="hidden" name="action" value="update" />
                    <input type="hidden" name="page_options" value="mp_display_children" />
					
					<?php $mp_display_children = get_option("mp_display_children"); ?>

					<select name="mp_display_children" id="mp_display_children">
                      
                      <option value="0" <?php if ($mp_display_children == "0") { echo "selected=\"selected\""; } ?>>No</option>
                      <option value="1" <?php if ($mp_display_children == "1") { echo "selected=\"selected\""; } ?> >Yes</option>
                    </select>
                    
                    <?php wp_nonce_field('update-options'); ?>
                
                </td>
                <td>
                <input type="submit" value="Display Children?" />
				
                
                </td>
                </form>
              </tr>
            </table>
       	</div>
        
        <div class="add_box postbox">
        
            <table cellspacing="6" cellpadding="0">
              <tr>
                <td valign="middle">URL<span class="mp_red">*</span></td>
                <td valign="middle"><input class="mp_url" type="text" /></td>
                <td rowspan="3" valign="middle"><input type="button" class="menusplus_add" id="mp_add_url" value="Add URL" /></td>
              </tr>
              <tr>
                <td valign="middle">Label<span class="mp_red">*</span></td>
                <td valign="middle"><input class="mp_label" type="text"  /></td>
              </tr>
              <tr>
                <td valign="middle">Class</td>
                <td valign="middle"><input class="mp_class" type="text"  /></td>
              </tr>
            </table>
		</div>
        <div class="clear"></div>

        </div>
        <div class="wrap postbox">
         	<ul id="menuslist" >
    
    		</ul>
		</div>
        <div class="wrap ">
      		<p>In your template files, use:
        	<code>&lt;?php menusplus(); ?&gt;</code></p>
       
      <p>To remove an item, simply drag it out of the list. </p>
      <p><a href="http://www.keighl.com/plugins/menus-plus/">Documentation &rarr;</a> </p>
    
        </div>
    
    </div>
	
 
    <?php 

}

function menusplus_style() { ?>

	<style>
		.menusplus {
		margin:12px;
		width:auto;
		}
		
		.menusplus h2 {
		font-size:3em;
		margin-bottom:6px;
		}
		
		.menusplus .clear {
		clear:both;
		visibility:hidden;
		}
		
		.menusplus_adding {
		margin-bottom:12px;;
		}
		
		.add_box {
			float:left;
			width:auto;
			background-color:#ffffff;
			margin-right:12px;
		}
		
			.mp_red {
			color:#990000;
			}
		
		#menuslist {
		padding:6px;
		margin:0px;
		list-style-type:none;
		}
		
		#menuslist li {
			background-color:#21759b;
			font-weight: bold;
		 	color:#FFFFFF;
		 	padding:12px;
		 	font-size:1.5em;
		 	cursor:move;
			margin-top:0px;
			margin-left:0px;
			margin-right:0px;
			margin-bottom:6px;
		}
		
			.order {
			
			color:#1a5c7a;}
			
			.type {
			color:#1a5c7a;
			font-size:.8em;}
	
	
	</style>

<?php

}


function menusplus_js() {
	?>

	<script type="text/javascript">
		jQuery(document).ready(function($) {
		
			menusplus_list();
			
			$("ul#menuslist").sortable({
				update : function (event, ui) {
					var list_order = $(this).sortable("serialize");
					$.post(
						"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
						{action:"menusplus_sort", list_order:list_order},
						function(str) {
						   //alert(str);
						   menusplus_list();
						}
					);
				} , 
			});
			
			$("ul#menuslist").droppable({
				accept: '.mp_list_item',
				out: function (event, ui) {
					var id = $(ui.draggable).attr('id');
					$(ui.draggable).fadeOut();
					$.post(
						"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
						{action:"menusplus_remove", id:id},
						function(str) {
						   //alert(str);
						   //menusplus_list();
						}
					);
				}
			});
			
			$('.menusplus_add').click(
				function () { 
					var type = $(this).attr('id');
					switch(type) {
						case "mp_add_page":
							var wp_id = $('select#page_id').val();
							$.post(
								"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
								{action:"menusplus_add" , wp_id:wp_id , type:"page"},
								function(str) {
									 //alert(str);
									 menusplus_list();
								}
							);
							break;
						case "mp_add_cat":
							var wp_id = $('select#cat').val();
							$.post(
								"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
								{action:"menusplus_add" , wp_id:wp_id , type:"cat"},
								function(str) {
									 //alert(str);
									 menusplus_list();
								}
							);
							break;
						case "mp_add_url":
							var url = $('.mp_url').val(); 
							var label = $('.mp_label').val();
							var class = $('.mp_class').val();
								var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
								if (!regexp.test(url)) {
									alert("You must supply a valid URL.");
									return false;
								}
								if (label == "") { 
									alert("You must enter a label for the URL.");
									return false;
								}
							$.post(
								"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
								{action:"menusplus_add" , url:url , label:label, class:class , type:"url"},
								function(str) {
									 //alert(str);
									 menusplus_list();
								}
							);
							break;
						default:
						  //
					}
					 
					
				}
			);
			
			function menusplus_list() {
			
				$.post(
					"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
					{action:"menusplus_list"},
					function(str) {
						//alert(str);
						$('ul#menuslist').fadeOut(300).html(str).fadeIn(1000);
					}
				);
			
			}
			
		
		});
	</script>
	
    <?php
}

function menusplus_list() {

	global $wpdb;
	$table_name = $wpdb->prefix . "menusplus";
	$wpdb->show_errors();
	
	$items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY list_order ASC", ARRAY_A );
	
	if (count($items) > 0) :
		foreach ($items as $item) :
			
			$id = $item['id'];
			$wp_id = $item['wp_id'];
			$type = $item['type'];
			$list_order = $item['list_order'];
			$url = $item['url'];
			$label = $item['label'];
			$sub = $item['sub'];
			
			switch ($type) :
				case "page" :
					$page = get_page($wp_id);
					$sort_title = $page->post_title;
					break;
				case "cat" :
					$cat = $wpdb->get_row("SELECT * FROM $wpdb->terms WHERE term_ID='$wp_id'", OBJECT);
					$sort_title = $cat->name; 
					break;
				case "url" :
					$sort_title = $label;
					break;
				default:
			endswitch;
			?>
			<li id="mp_id_<?php echo $id; ?>" class="mp_list_item">
				<span class="order"><?php echo $list_order; ?></span>&nbsp;
				<?php echo $sort_title; ?>&nbsp;
                <span class="type"><?php echo $type; ?></span>
			</li>
			<?php 
		
		endforeach;
	endif;
	
	exit();

}

function menusplus_add() {

	global $wpdb;
	$table_name = $wpdb->prefix . "menusplus";
	$wpdb->show_errors();
	
	$type  = $_POST['type'];
	$wp_id = $_POST['wp_id'];
	$class = $_POST['class'];
	$url   = $_POST['url'];
	$label = $_POST['label'];
	
	// Find the highest order thus far
	
	$items = $wpdb->get_results("SELECT list_order FROM $table_name", ARRAY_N);
	
	if (count($items) > 0) :
		$order_set = array();
		foreach ($items as $item) :
		  $order_set[] = $item[0];
		endforeach;
		$highest_order = max($order_set);
		// echo $highest_order;
	else :
		$highest_order = 0;
    endif;
	
	$highest_order++;
	
	$data_array = array(
					'type'       => $type,
					'wp_id'      => $wp_id,
					'list_order' => $highest_order,
					'class'      => $class,
					'url'        => $url,
					'label'      => $label
					);
	
	$wpdb->insert($table_name, $data_array );
	
	exit();

}

function menusplus_sort() {

	global $wpdb;
	$table_name = $wpdb->prefix . "menusplus";
	$wpdb->show_errors();
	
	$ids = $_POST['list_order'];
	$ids = explode('mp_id[]=', $ids);
	var_dump($ids);
	
	$list_order = -1;
	
	foreach ($ids as $id) :
		
		$list_order++;
		
		$pattern = "/&/";
		$id = preg_replace($pattern, '' , $id);
		
		$data_array = array(
			"list_order" => $list_order
			);
		$where = array('id' => $id);
		$wpdb->update($table_name, $data_array, $where );
		
	endforeach;
	
	exit();

}

function menusplus_remove() {

	global $wpdb;
	$table_name = $wpdb->prefix . "menusplus";
	$wpdb->show_errors();
	
	$id = $_POST['id'];
	$id = trim($id, 'mp_id_');
	
	$wpdb->query("DELETE from $table_name WHERE id = $id");
	
	exit();

}

// Install

function menusplus_install() {

	
	global $wpdb;
	$table_name = $wpdb->prefix . "menusplus";
	$wpdb->show_errors();
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) :

		$sql = "CREATE TABLE " . $table_name . " (
			id int NOT NULL AUTO_INCREMENT,
			wp_id int NULL,
			list_order int DEFAULT '0' NOT NULL, 
			type text NOT NULL,
			class text NULL,
			url text NULL,
			label text NULL,
			sub int NULL,
			PRIMARY  KEY id (id)
			);";

		dbDelta($sql);
		
	else :

	endif;
	
	update_option('mp_display_children' , "0");
	
}

function menusplus_add_admin() {

	add_theme_page('Menus Plus+', 'Menus Plus+', 'administrator', 'menusplus', 'menusplus_admin');
	
}

function menusplus_js_libs() {
	
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-sortable');
	wp_enqueue_script('jquery-ui-draggable');
	wp_enqueue_script('jquery-ui-droppable');

}

?>