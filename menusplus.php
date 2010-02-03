<?php
/*
Plugin Name: Menus Plus+
Plugin URI: http://www.keighl.com/plugins/menus-plus/
Description: Create <strong>multiple</strong> customized menus with pages, categories, and urls. Use a widget or a template tag <code>&lt;?php menusplus(); ?&gt;</code></code>. <a href="themes.php?page=menusplus">Configuration Page</a>
Version: 1.5
Author: Kyle Truscott
Author URI: http://www.keighl.com
*/


/*  Copyright 2009 Kyle Truscott  (email : info@keighl.com)

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

$menusplus = new MenusPlus();

class MenusPlus {

	function MenusPlus() {
		
		register_activation_hook(__FILE__, array(&$this, 'install'));
		
		add_action('admin_menu', array(&$this, 'add_admin'));
		
		add_action("admin_print_scripts", array(&$this, 'js_libs'));
		add_action("admin_print_styles", array(&$this, 'style_libs'));
		
		add_action('wp_ajax_menusplus_list', array(&$this, 'list_menu'));
		add_action('wp_ajax_menusplus_add_dialog', array(&$this, 'add_dialog'));
		add_action('wp_ajax_menusplus_edit_dialog', array(&$this, 'edit_dialog'));
		add_action('wp_ajax_menusplus_add', array(&$this, 'add'));
		add_action('wp_ajax_menusplus_edit', array(&$this, 'edit'));
		add_action('wp_ajax_menusplus_sort', array(&$this, 'sort'));
		add_action('wp_ajax_menusplus_remove', array(&$this, 'remove'));
		
		add_action('wp_ajax_menusplus_menu_title', array(&$this, 'menu_title'));
		add_action('wp_ajax_menusplus_menus_dropdown', array(&$this, 'menus_dropdown'));
		
		add_action('wp_ajax_menusplus_add_new_menu_dialog', array(&$this, 'new_menu_dialog'));
		add_action('wp_ajax_menusplus_new_menu', array(&$this, 'new_menu'));
		
		add_action('wp_ajax_menusplus_edit_menu_dialog', array(&$this, 'edit_menu_dialog'));
		add_action('wp_ajax_menusplus_edit_menu', array(&$this, 'edit_menu'));
		
		add_action('wp_ajax_menusplus_remove_menu_dialog', array(&$this, 'remove_menu_dialog'));
		add_action('wp_ajax_menusplus_remove_menu', array(&$this, 'remove_menu'));
		
		add_action("widgets_init", array(&$this, 'init_widget'));
				
	}
	
	// Install
	
	function install() {
		
		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// Does the menu items table exist?
		
		$exists = $wpdb->query("SELECT * FROM '$items_table'");

		if(!$exists) :

			$sql = "CREATE TABLE " . $items_table . " (
				id int NOT NULL AUTO_INCREMENT,
				wp_id int NULL,
				list_order int DEFAULT '0' NOT NULL, 
				type text NOT NULL,
				class text NULL,
				url text NULL,
				label text NULL,
				children text NULL,
				children_order text NULL,
				children_order_dir text NULL,
				menu_id int DEFAULT '1' NOT NULL,
				PRIMARY  KEY id (id)
				);";

			dbDelta($sql);

		else :
		
			$sql = "ALTER TABLE " . $items_table . " (
				ADD UNIQUE id int NOT NULL AUTO_INCREMENT,
				ADD UNIQUE wp_id int NULL,
				ADD UNIQUE list_order int DEFAULT '0' NOT NULL, 
				ADD UNIQUE type text NOT NULL,
				ADD UNIQUE class text NULL,
				ADD UNIQUE url text NULL,
				ADD UNIQUE label text NULL,
				ADD UNIQUE children text NULL,
				ADD UNIQUE children_order text NULL,
				ADD UNIQUE children_order_dir text NULL,
				ADD UNIQUE menu_id int DEFAULT '1' NOT NULL,				
				);";

			dbDelta($sql);

		endif;
		
		// Does the menus table exist?
				
		$exists = $wpdb->query("SELECT * FROM '$menus_table'");

		if(!$exists) :

			// Create the db.
			
			$sql = "CREATE TABLE " . $menus_table . " (
				id int NOT NULL AUTO_INCREMENT,
				menu_title text NULL,
				menus_description text NULL,
				PRIMARY  KEY id (id)
				);";

			dbDelta($sql);
			
			// Setup a default menu
			
			$default_title = 'Default';
			
			$data_array = array(
				'menu_title'     => $default_title,
			);
			
			$wpdb->insert($menus_table, $data_array );

		endif;
		
		$mp_version = "1.5";
		update_option('mp_version', $mp_version);

	}
	
	function add_admin() {

		add_theme_page('Menus Plus+', 'Menus Plus+', 'administrator', 'menusplus', array(&$this, 'admin'));

	}
	
	function js_libs() {

		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('thickbox');
			
	}
	
	function style_libs() {
		wp_enqueue_style('thickbox');
	}
	
	function init_widget() {

		register_widget('MenusPlusWidget');

	}
	
	// Views
	
	function admin() {

		// make a funciton to retrieve
		$menu_id_from_get = $_GET['menu_id'];
		
		$menu_id = $this->get_menu_id($menu_id_from_get); 
				
		$this->js($menu_id);
		$this->style();
			
		?> 

		<div class="wrap mp_margin_bottom">
	    	<h2>Menus Plus+ <span class="mp_heading">v<?php echo get_option('mp_version'); ?> <a href="http://www.keighl.com/plugins/menus-plus/">by Keighl</a></span></h2> 
		</div>
		<div class="wrap mp_margin_bottom">
			<div class="mp_buttons_left">
				<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=cat&width=350&height=250" title="<?php _e("Add a Category"); ?>"><?php _e("Add Category"); ?></a>
				<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=page&width=350&height=250" title="<?php _e("Add a Page"); ?>"><?php _e("Add Page"); ?></a>
				<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=url&width=350&height=250" title="<?php _e("Add a URL"); ?>"><?php _e("Add URL"); ?></a>
			</div>
			<div class="mp_buttons_right">
				<span class="mp_menu_title"></span> |
				<a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_edit_menu_dialog&menu_id=<?php echo $menu_id; ?>&width=350&height=100" title="<?php _e("New Menu"); ?>">
					<img src="<?php echo plugin_dir_url( __FILE__ );?>images/edit.png" />
				</a>
				<a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_remove_menu_dialog&menu_id=<?php echo $menu_id; ?>&width=350&height=100" title="<?php _e("Delete Menu"); ?>">
					<img src="<?php echo plugin_dir_url( __FILE__ );?>images/remove.png" />
				</a>
				|
				<select class="mp_switch_menu">
				</select>
				|
				<a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_new_menu_dialog&width=350&height=100" title="<?php _e("New Menu"); ?>">
					<img src="<?php echo plugin_dir_url( __FILE__ );?>images/add.png" />
				</a>
				
				
			</div>
			<div class="clear_list_floats"></div>
		</div>
		<div class="wrap postbox" id="menusplus_list">
			<ul></ul>
		</div>
		<div class="wrap">
			<p><?php _e('Template Tag') ?>:
        	<input class="mp_template_tag" value="&lt;?php menusplus(<?php echo $menu_id; ?>); ?&gt;" />
			<a href="http://www.keighl.com/plugins/menus-plus/"><?php _e('Docs') ?></a></p>
		</div>
					
	 	<?php 

	}
	
	function add_dialog() {
		
		$type = $_GET['type'];
		
		if (!$type) { exit(); }
				
		?>
		<div class="mp_add">
			<table cellspacing="16" cellpadding="0">
				<?php if ($type != "url") : ?>
					<tr>
						<td>
							<div align="right">
								<?php if ($type == "cat") { echo _e("Category"); } ?>
								<?php if ($type == "page") { echo _e("Page"); } ?>
							</div>
						</td>
						<td>
							<?php 
								if ($type == "cat") :
									wp_dropdown_categories("hide_empty=0&name=add_wpid");
								elseif ($type == "page") :
									wp_dropdown_pages("name=add_wpid");
								else :
						
								endif;
							?>
						</td>
					</tr>
				<?php else : ?>
					<tr>
						<td><div align="right"><?php _e("URL"); ?></div></td>
						<td><input class="add_url" value="" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Label"); ?></div></td>
						<td><input class="add_label" value="" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Class"); ?></div></td>
						<td><input class="add_class" value="" /></td>
					</tr>
				<?php endif; ?>
				<?php if ($type != "url") : ?>
					<tr>
						<td><div align="right"><?php _e("Children"); ?></div></td>
						<td>
	                        <label>
	                        	<input type="radio" name="add_children" value="true" /> <?php _e("Yes"); ?>
	                        </label>
							<label>
	                        	<input type="radio" name="add_children" value="false" checked="checked" /> <?php _e("No"); ?>
	                        </label>
	                    </td>
				    </tr>
					<tr id="children_order_box">
						<td><div align="right"><?php _e("Child Order"); ?></div></td>
						<td>
							<?php if ($type == 'cat') : ?>
								<select class="add_children_order">
									<option value="name"><?php _e("Name"); ?></option>
									<option value="ID"><?php _e("ID"); ?></option>
									<option value="count"><?php _e("Count"); ?></option>
									<option value="slug"><?php _e("Slug"); ?></option>
									<option value="term_group"><?php _e("Term Group"); ?></option>
								</select>
							<?php elseif ($type == 'page') : ?>
								<select class="add_children_order">
									<option value="post_title"><?php _e("Title"); ?></option>
									<option value="post_date"><?php _e("Date"); ?></option>
									<option value="post_modified"><?php _e("Date Modified"); ?></option>
									<option value="ID"><?php _e("ID"); ?></option>
									<option value="post_author"><?php _e("Author"); ?></option>
									<option value="post_name"><?php _e("Slug"); ?></option>
								</select>
							<?php endif; ?>
							<select class="add_children_order_dir">
								<option value="ASC">ASC</option>
								<option value="DESC">DESC</option>
							</select>
	                    </td>
				    </tr>
				<?php endif; ?>
				<tr>
					<td><div align="right"></div></td>
					<td>
						<a class="button" id="add_submit" rel="<?php echo $type; ?>"><?php _e("Add"); ?></a>
						<a class="button" id="mp_cancel"><?php _e("Cancel"); ?></a>
					</td>
				</tr>
			</table>
		</div>
		
		<?php
		
		exit();
		
	}
	
	function edit_dialog() {
		
		$id = $_GET['id'];
		
		if (!$id) { exit(); }
		
		// Assemble our knowledge of this list item
		
		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();

		$metas = $wpdb->get_results("SELECT * FROM $items_table WHERE id = $id", ARRAY_A );
		
		if (count($metas) > 0) :
			foreach ($metas as $meta) :
				$type  = $meta['type'];
				$wp_id = $meta['wp_id'];
				$class = $meta['class'];
				$label = $meta['label'];
				$url   = $meta['url'];
				$children = $meta['children'];
				$children_order = $meta['children_order'];
				$children_order_dir = $meta['children_order_dir'];
				$list_order = $meta['list_order'];
				$menu_id = $meta['menu_id'];
			endforeach;
		endif;
		
		?>
		<div class="mp_edit">
			<input  type="hidden" value="<?php _e($type); ?>" class="edit_type" />
			<table cellspacing="16" cellpadding="0">
				<?php if ($type != "url") : ?>
					<tr>
						<td>
							<div align="right">
								<?php if ($type == "cat") { _e("Category"); } ?>
								<?php if ($type == "page") { _e("Page"); } ?>
							</div>
						</td>
						<td>
							<?php 
								if ($type == "cat") :
									wp_dropdown_categories("hide_empty=0&name=edit_wpid&selected=$wp_id");
								elseif ($type == "page") :
									wp_dropdown_pages("name=edit_wpid&selected=$wp_id");
								else :
						
								endif;
							?>
						</td>
					</tr>
				<?php else : ?>
					<tr>
						<td><div align="right"><?php _e("URL"); ?></div></td>
						<td><input class="edit_url" value="<?php echo $url; ?>" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Label"); ?></div></td>
						<td><input class="edit_label" value="<?php echo $label; ?>" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Class"); ?></div></td>
						<td><input class="edit_class" value="<?php echo $class; ?>" /></td>
					</tr>
				<?php endif ;?>
				<?php if ($type != "url") : ?>
					<tr>
						<td><div align="right"><?php _e("Children"); ?></div></td>
						<td>
	                        <label>
	                        	<input type="radio" name="edit_children" value="true" <?php if ($children == "true") : ?> checked="checked" <?php endif; ?> /> <?php _e("Yes"); ?>
	                        </label>
							<label>
	                        	<input type="radio" name="edit_children" value="false" <?php if ($children == "false") : ?> checked="checked" <?php endif; ?> /> <?php _e("No"); ?>
	                        </label>
	                    </td>
				    </tr>
					<tr id="children_order_box">
						<td><div align="right"><?php _e("Child Order"); ?></div></td>
						<td>
							<?php if ($type == 'cat') : ?>
								<select class="edit_children_order">
									<option value="name" <?php if ($children_order == "name") : ?> selected="selected" <?php endif; ?> ><?php _e("Name"); ?></option>
									<option value="ID" <?php if ($children_order == "ID") : ?> selected="selected" <?php endif; ?> ><?php _e("ID"); ?></option>
									<option value="count" <?php if ($children_order == "count") : ?> selected="selected" <?php endif; ?> ><?php _e("Count"); ?></option>
									<option value="slug" <?php if ($children_order == "slug") : ?> selected="selected" <?php endif; ?> ><?php _e("Slug"); ?></option>
									<option value="term_group" <?php if ($children_order == "term_group") : ?> selected="selected" <?php endif; ?> ><?php _e("Term Group"); ?></option>
								</select>
							<?php elseif ($type == 'page') : ?>
								<select class="edit_children_order">
									<option value="post_title" <?php if ($children_order == "post_title") : ?> selected="selected" <?php endif; ?> ><?php _e("Title"); ?></option>
									<option value="post_date" <?php if ($children_order == "post_date") : ?> selected="selected" <?php endif; ?> ><?php _e("Date"); ?></option>
									<option value="post_modified" <?php if ($children_order == "post_modified") : ?> selected="selected" <?php endif; ?> ><?php _e("Date Modified"); ?></option>
									<option value="ID" <?php if ($children_order == "ID") : ?> selected="selected" <?php endif; ?> ><?php _e("ID"); ?></option>
									<option value="post_author" <?php if ($children_order == "post_author") : ?> selected="selected" <?php endif; ?> ><?php _e("Author"); ?></option>
									<option value="post_name" <?php if ($children_order == "post_name") : ?> selected="selected" <?php endif; ?> ><?php _e("Slug"); ?></option>
								</select>
							<?php endif; ?>
							<select class="edit_children_order_dir">
								<option value="ASC" <?php if ($children_order_dir == "ASC") : ?> selected="selected" <?php endif; ?> >ASC</option>
								<option value="DESC" <?php if ($children_order_dir == "DESC") : ?> selected="selected" <?php endif; ?> >DESC</option>
							</select>
	                    </td>
				    </tr>
				<?php endif; ?>
				<tr>
					<td><div align="right"></div></td>
					<td>
						<a class="button" id="edit_submit" rel="<?php echo $id; ?>"><?php _e("Update"); ?></a>
						<a class="button" id="mp_cancel"><?php _e("Cancel"); ?></a>
					</td>
				</tr>
			</table>
		</div>
		
		<?php
		
		exit();
		
	}
	
	function new_menu_dialog() {
		
		?>
		
		<div class="new_menu">
			<table cellspacing="16" cellpadding="0">
				<tr>
					<td><div align="right"><?php _e("Title"); ?></div></td>
					<td><input class="new_menu_title" value="" /></td>
				</tr>
				<tr>
					<td><div align="right"></div></td>
					<td>
						<a class="button" id="new_menu_submit" rel="<?php echo $type; ?>"><?php _e("Add"); ?></a>
						<a class="button" id="mp_cancel"><?php _e("Cancel"); ?></a>
					</td>
				</tr>
			</table>
		</div>
		
		<?php
		
		exit();
		
	}
	
	function edit_menu_dialog() {
		
		$menu_id = $_GET['menu_id'];
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();

		$menus = $wpdb->get_row("SELECT * FROM $menus_table WHERE id = $menu_id", ARRAY_A );
		
		$title = $menus['menu_title'];
		
		?>
		
		<div class="new_menu">
			<table cellspacing="16" cellpadding="0">
				<tr>
					<td><div align="right"><?php _e("Title"); ?></div></td>
					<td><input class="edit_menu_title" value="<?php echo $title; ?>" /></td>
				</tr>
				<tr>
					<td><div align="right"></div></td>
					<td>
						<a class="button" id="edit_menu_submit" rel="<?php echo $type; ?>"><?php _e("Update"); ?></a>
						<a class="button" id="mp_cancel"><?php _e("Cancel"); ?></a>
					</td>
				</tr>
			</table>
		</div>
		
		<?php
		
		exit();
		
	}
	
	function remove_menu_dialog() {
		
		$menu_id = $_GET['menu_id'];
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();

		$menus = $wpdb->get_row("SELECT * FROM $menus_table WHERE id = $menu_id", ARRAY_A );
		
		$title = $menus['menu_title'];
		
		?>
		
		<div class="remove_menu">
			<p><?php _e("Are you sure you want to delete the menu, <strong>$title</strong>?"); ?></p>
			<p>
				<a class="button" id="remove_menu_submit" rel="<?php echo $type; ?>"><?php _e("Delete"); ?></a>
				<a class="button" id="mp_cancel"><?php _e("Cancel"); ?></a>
			</p>
		</div>
		
		<?php
		
		exit();
		
	}
	
	function style() { ?>

		<style>
		
			.mp_margin_bottom h2 {
				font-weight:bold;
			}
		
			.mp_heading {
				font-size:.6em;
				font-weight:normal;
				font-family: 'Lucida Grande', Helvetica, Arial, sans-serif;
				
			}
			.mp_menu_title {
				font-weight: bold;
			}
		
			.mp_margin_bottom {
				margin-bottom:15px;
			}
			
			.mp_buttons_left {
				float:left;
			}
			
			.mp_buttons_right {
				float:right;
				margin-right:10px;
			}
			
				.mp_buttons_right img {
					vertical-align: middle;
				}
			
			#menusplus_list {
				padding:15px;
				width:95%;
			}
			
				#menusplus_list ul {
					list-style-type:none;
					margin:0px;
					padding:0px;
				}
				
				#menusplus_list ul li {
					background-color:#21759b;
				 	padding:10px;
				 	cursor: move;
					margin-top:0px;
					margin-left:0px;
					margin-right:0px;
					margin-bottom:6px;
				}
				
				
				
					.list_item_title {
						float:left;
						font-size:1.5em;
						font-weight:bold;
						color:#ffffff;
					}
					
					.list_item_meta {
						float:right;
					}
					
						.list_item_meta a:link, .list_item_meta a:visited {
							color:#ffffff;
							margin-left:6px;
							text-decoration:none;
						}
						
						.list_item_meta a.mp_remove {
							color:#0f3546;
							cursor:pointer;
							margin-left:6px;
						}
					
					.clear_list_floats {
						clear: both;
					}
				
				
			

		</style>

	<?php

	}

	function js($menu_id) {
		?>

		<script type="text/javascript">
			
			jQuery(document).ready(function($) {

				// Preloads
				menu_title(<?php echo $menu_id; ?>);
				menus_dropdown(<?php echo $menu_id; ?>);
				menusplus_list(<?php echo $menu_id; ?>);
								
				// Add lists
				
				$('.mp_add a#add_submit').live("click",
					function () {
						
						var type = $(this).attr('rel');
						var wp_id = $("select[name='add_wpid']").val();
						var opt_class = $('input.add_class').val();
						var url = $('input.add_url').val();
						var label = $('input.add_label').val();
						var children = $("input[name='add_children']:checked").val();
						var children_order = $('select.add_children_order').val();
						var children_order_dir = $('select.add_children_order_dir').val();
						
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_add", 
								type:type,
								wp_id:wp_id,
								children:children,
								children_order:children_order,
								children_order_dir:children_order_dir,
								opt_class:opt_class,
								label:label,
								url:url,
								menu_id : <?php echo $menu_id; ?>
							},
							function(str) {
								if (str == "1") {
									// URL issue
									alert('<?php _e('You must enter a valid URL.'); ?>');
									$('input.add_url').css({'background-color' : '#c0402a' , 'color' : '#ffffff'});
								}
								if (str == "2") {
									// Label issue
									alert('<?php _e('You must enter a label.'); ?>');
									$('input.add_label').css({'background-color' : '#c0402a' , 'color' : '#ffffff'});
								} 
								if (str == "") {
									tb_remove();
									menusplus_list(<?php echo $menu_id; ?>);
								}
							}
						);
					}
				);
				
				// Edit
				
				$('.mp_edit a#edit_submit').live("click",
					function () {
						// your can't change type yet ... but you can select a new item in the type class. 
						var id = $(this).attr('rel');
						var type = $("input.edit_type").val();
						var wp_id = $("select[name='edit_wpid']").val();
						var opt_class = $('input.edit_class').val();
						var url = $('input.edit_url').val();
						var label = $('input.edit_label').val();
						var children = $("input[name='edit_children']:checked").val();
						var children_order = $('select.edit_children_order').val();
						var children_order_dir = $('select.edit_children_order_dir').val();
						
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_edit", 
								id:id,
								wp_id:wp_id,
								children:children,
								children_order:children_order,
								children_order_dir:children_order_dir,
								opt_class:opt_class,
								label:label,
								url:url,
								type:type
							},
							function(str) {
								if (str == "1") {
									// URL issue
									alert('<?php _e('You must enter a valid URL.'); ?>');
									$('input.edit_url').css({'background-color' : '#c0402a' , 'color' : '#ffffff'});
								}
								if (str == "2") {
									// Label issue
									alert('<?php _e('You must enter a label.'); ?>');
									$('input.edit_label').css({'background-color' : '#c0402a' , 'color' : '#ffffff'});
								} 
								if (str == "") {
									tb_remove();
									menusplus_list(<?php echo $menu_id; ?>);
								}
							}
						);
					}
				);
				
				$("a.mp_remove").live("click", 
					function () {
						var id = $(this).attr('id');
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_remove",
								id:id
							},
							function(str) {
								menusplus_list(<?php echo $menu_id; ?>);
							}
						);
					}
				);
				
				// Sort
				
				$("#menusplus_list ul").sortable({
					update : function (event, ui) {
						var list_order = $(this).sortable("serialize");
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_sort", 
								list_order:list_order
							},
							function(str) {
								menusplus_list(<?php echo $menu_id; ?>);
							}
						);
					} ,
					opacity: 0.6 
				});
				
				$("a#mp_cancel").live("click",
					function () {
						tb_remove();
					} 	
				);	
				
				// Add new menus
				
				$('.new_menu a#new_menu_submit').live("click",
					function () {
						var title = $('input.new_menu_title').val();
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_new_menu", 
								title : title
							},
							function(str) {
								if (str == "empty") {
									// Title issue
									alert('<?php _e('You must enter a title.'); ?>');
									$('input.new_menu_title').css({'background-color' : '#c0402a' , 'color' : '#ffffff'});
								} else {
									window.location.replace('themes.php?page=menusplus&menu_id=' + str);
								}
							}
						);
					}
				);	
				
				$('a#edit_menu_submit').live("click",
					function () {
						var title = $('input.edit_menu_title').val();
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_edit_menu", 
								title : title,
								menu_id : <?php echo $menu_id; ?>
							},
							function(str) {
								if (str == "1") {
									// Title issue
									alert('You must enter a title.')
									$('input.edit_menu_title').css({'background-color' : '#c0402a' , 'color' : '#ffffff'});
								} else {
									tb_remove();
									// Stays on the current menu for now. 
									menu_title(<?php echo $menu_id; ?>);
									menus_dropdown(<?php echo $menu_id; ?>);
									menusplus_list(<?php echo $menu_id; ?>);
								}
							}
						);
					}
				);
				
				// Remove Menus
				
				$('a#remove_menu_submit').live("click",
					function () {
						var title = $('input.edit_menu_title').val();
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_remove_menu", 
								menu_id : <?php echo $menu_id; ?>
							},
							function(str) {
								if (str == 1) {
									alert('<?php _e('You cannot delete your only menu.'); ?>');
								} else {
									window.location.replace('themes.php?page=menusplus');
								}		
							}
						);
					}
				);
				
				// Switch Menus
				
				$('.mp_switch_menu').live("change" ,
					function () {
						var menu_id = $('.mp_switch_menu').val();
						window.location.replace('themes.php?page=menusplus&menu_id='+menu_id);
						
					}
				);
								
				
				// Funtions
				
				function menu_title(menu_id) {
					$.post(
						"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
						{
							action:"menusplus_menu_title",
							menu_id: menu_id
						},
						function(str) {
							$('.mp_menu_title').html(str);
						}
					);
				}
				
				function menus_dropdown(menu_id) {
					$.post(
						"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
						{
							action:"menusplus_menus_dropdown",
							menu_id: menu_id
						},
						function(str) {
							$('select.mp_switch_menu').html(str);
						}
					);
				}
								
				function menusplus_list(menu_id) {
					$.post(
						"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
						{
							action:"menusplus_list",
							menu_id : menu_id
						},
						function(str) {
							$('#menusplus_list ul').fadeOut('fast').html(str).fadeIn('slow');
							removeThickBoxEvents();
							tb_init('a.thickbox, area.thickbox, input.thickbox');
						}
					);
				}
				
				function removeThickBoxEvents() {
			        $('.thickbox').each(function(i) {
			            $(this).unbind('click');
			        });
			    }
				
			});
			
		</script>

	    <?php
	}
	
	// Methods
	
	function get_menu_id($menu_id_from_get = null) {
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		// Returns the best possible menu_id
		
		if (!$menu_id_from_get) :
			$item = $wpdb->get_row("SELECT * FROM $menus_table ORDER BY id", ARRAY_A );
			return $item['id'];
		else :
			return $menu_id_from_get;
		endif;
		
	}
		
	function menu_title() {
		
		$menu_id = $_POST['menu_id'];
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$title = $wpdb->get_row("SELECT * FROM $menus_table WHERE id = $menu_id", ARRAY_A );
		
		echo $title['menu_title'];
		
		exit();
		
	}
	
	function menus_dropdown() {
		
		$menu_id = $_POST['menu_id'];
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$items = $wpdb->get_results("SELECT * FROM $menus_table ORDER BY id ASC", ARRAY_A );
		
		if ($items) :
		
			foreach ($items as $item) :
				$id = $item['id'];
				$title = $item['menu_title'];
				$is_selected = ($item['id'] == $menu_id) ? 'selected="selected"' : '';
				echo "<option value=\"$id\" $is_selected >$title</option>";
			
			endforeach;
		
		endif;
		
	}
	
	function list_menu() {

		global $wpdb;
		
		$menu_id = $_POST['menu_id'];
		
		$items_table = $wpdb->prefix . "menusplus";
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();

		$items = $wpdb->get_results("SELECT * FROM $items_table WHERE menu_id = $menu_id ORDER BY list_order ASC", ARRAY_A );

		if (count($items) > 0) :
			foreach ($items as $item) :

				$id = $item['id'];
				$wp_id = $item['wp_id'];
				$type = $item['type'];
				$list_order = $item['list_order'];
				$url = $item['url'];
				$label = $item['label'];
				
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
					<div class="list_item_title">
						<?php echo $sort_title; ?>
					</div>
					<div class="list_item_meta">
						<a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_edit_dialog&id=<?php echo $id; ?>&width=350&height=250" title="Edit <?php echo $sort_title; ?>">
							<?php _e("Edit"); ?>
						</a>
						<a class="mp_remove" id="mp_remove_<?php echo $id; ?>">
							<?php _e("Remove"); ?>
						</a>
					</div>
					<div class="clear_list_floats"></div>
				</li>
				<?php 

			endforeach;
		endif;

		exit();

	}

	function add() {

		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();
		
		$type  = $_POST['type'];
		$wp_id = $_POST['wp_id'];
			$wp_id = $this->is_undefined($wp_id);
		$class = $_POST['opt_class'];
		$label = $_POST['label'];
		$url   = $_POST['url'];
		$children = $_POST['children'];
		$children_order = $_POST['children_order'];
		$children_order_dir = $_POST['children_order_dir'];
		$menu_id = $_POST['menu_id'];
		
		$class = stripslashes($class);
		$label = stripslashes($label);
		$url = stripslashes($url);
		
		$highest_order = $this->highest_order($menu_id) + 1;

		$data_array = array(
				'type'					=> $type,
				'wp_id'     			=> $wp_id,
				'list_order' 			=> $highest_order,
				'class'      			=> $class,
				'url'       			=> $url,
				'label'      			=> $label,
				'children'   			=> $children,
				'children_order' 		=> $children_order,
				'children_order_dir' 	=> $children_order_dir,
				'menu_id'				=> $menu_id
				);
				
		// Validate for URL submissions
		
		if ($type == "url") :
		
			$valid_url = preg_match("/^(http(s?):\/\/|ftp:\/\/{1})((\w+\.){1,})\w{2,}$/i", $url);
			
			if (!$valid_url) :
				
				echo "1";
				
				exit();
				
			endif;
			
			if (empty($label)) :
			
				echo "2";
				
				exit();
				
			endif;
		
		endif;

		$wpdb->insert($items_table, $data_array );

		exit();

	}

	function edit() {
		
		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();
		
		$id  = $_POST['id'];
		$wp_id = $_POST['wp_id'];
			$wp_id = $this->is_undefined($wp_id);
		$class = $_POST['opt_class'];
		$label = $_POST['label'];
		$url   = $_POST['url'];
		$children = $_POST['children'];
		$children_order = $_POST['children_order'];
		$children_order_dir = $_POST['children_order_dir'];
		$type = $_POST['type'];
		
		$data_array = array(
				'wp_id'     			=> $wp_id,
				'class'      			=> $class,
				'url'       			=> $url,
				'label'      			=> $label,
				'children'   			=> $children,
				'children_order' 		=> $children_order,
				'children_order_dir' 	=> $children_order_dir
				);
				
		$class = stripslashes($class);
		$label = stripslashes($label);
		$url = stripslashes($url);
		
		// Validate for URL submissions
		
		if ($type == "url") :
		
			$valid_url = preg_match("/^(http(s?):\/\/|ftp:\/\/{1})((\w+\.){1,})\w{2,}$/i", $url);
			
			if (!$valid_url) :
				
				echo "1";
				
				exit();
				
			endif;
			
			if (empty($label)) :
			
				echo "2";
				
				exit();
				
			endif;
		
		endif;
		
		$where = array('id' => $id);
		$wpdb->update($items_table, $data_array, $where );
		
		exit();
		
	}
	
	function sort() {

		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();

		$ids = $_POST['list_order'];
		$ids = explode('mp_id[]=', $ids);

		$list_order = -1;

		foreach ($ids as $id) :

			$list_order++;

			$pattern = "/&/";
			$id = preg_replace($pattern, '' , $id);
						
			$data_array = array(
				"list_order" => $list_order
				);
			$where = array('id' => $id);
			$wpdb->update($items_table, $data_array, $where );

		endforeach;

		exit();

	}

	function new_menu() {
		
		$title = $_POST['title'];
		
		if (empty($title)) : echo "empty"; exit(); endif;
		
		$title = stripslashes($title);
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$data_array = array(
			'menu_title' => $title,
		);
		
		$wpdb->insert($menus_table, $data_array );
		
		echo $last_result = $wpdb->insert_id;
		
		exit();
		
	}
	
	function edit_menu() {
		
		$title = $_POST['title'];
		$id = $_POST['menu_id'];
		
		if (empty($title)) : echo 1; exit(); endif;
		
		$title = stripslashes($title);
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$data_array = array(
			'menu_title' => $title,
		);
		
		$where = array('id' => $id);
		$wpdb->update($menus_table, $data_array, $where );

		exit();
		
	}
	
	function remove_menu() {
		
		$id = $_POST['menu_id'];
		
		// Delete the menu
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$items_table = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();
		
		// How many menus are there?
		
		$count = $wpdb->query("SELECT * from $menus_table");

		if ($count == 1) :
		
			echo 1;
			
		else :
		
			$wpdb->query("DELETE from $items_table WHERE menu_id = $id");
			$wpdb->query("DELETE from $menus_table WHERE id = $id");
			
		endif;

		exit();
		
	}

	function remove() {

		global $wpdb;
		$table_name = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();

		$id = $_POST['id'];
		$id = trim($id, 'mp_remove_');

		$wpdb->query("DELETE from $table_name WHERE id = $id");

		exit();

	}
	
	function highest_order($menu_id) {
		
		// Find the highest order thus far
		// dapt for future release to accomdate sortable children
		
		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();
		
		$items = $wpdb->get_results("SELECT list_order FROM $items_table WHERE menu_id = $menu_id", ARRAY_N);

		if (count($items) > 0) :
			$order_set = array();
			foreach ($items as $item) :
			  $order_set[] = $item[0];
			endforeach;
			$highest_order = max($order_set);
		else :
			$highest_order = 0;
	    endif;

		return $highest_order;
		
		exit();
		
	}
	
	function is_undefined($str) {
		
		if ($str == "undefined") : return 0;
		else : return $str;
		endif;
	}
	
}

class MenusPlusWidget extends WP_Widget {
	
	function MenusPlusWidget() {
		
		$widget_ops = array( 'classname' => 'menus_plus', 'description' => 'Add one of your Menus Plus+ lists in widget form.' );

		$control_ops = array( 'id_base' => 'menus_plus' );

		$this->WP_Widget( 'menus_plus', __('Menus Plus+', 'menus_plus'), $widget_ops, $control_ops );
		
	}
	
	function form($instance) {
		
		$instance = wp_parse_args( (array) $instance, $defaults );	
		
		?>
		
		<table width="100%" cellspacing="6">
			<!-- Title -->
			<tr>
				<td>
					<label for="<?php echo $this->get_field_id( 'title' ); ?>"><strong><?php _e('Title:'); ?></strong></label>
				</td>
				<td>
					<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
				</td>
			</tr>
			<!-- Menu -->
			<tr>
				<td>
					<label for="<?php echo $this->get_field_id( 'menu' ); ?>"><strong><?php _e('Menu:'); ?></strong></label>
				</td>
				<td>
					<select id="<?php echo $this->get_field_id( 'menu' ); ?>" name="<?php echo $this->get_field_name( 'menu' ); ?>">
						<?php $this->menus_dropdown($instance['menu']); ?>
					</select>
				</td>
			</tr>
		</table>
		
		<?php		
	}
	
	function update($new_instance, $old_instance) {
		
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['menu'] = strip_tags( $new_instance['menu'] );
		
		return $instance;
		
	}
	
	function menus_dropdown($menu = NULL) {
				
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$items = $wpdb->get_results("SELECT * FROM $menus_table ORDER BY id ASC", ARRAY_A );
		
		if ($items) :
		
			foreach ($items as $item) :
				$id = $item['id'];
				$title = $item['menu_title'];
				
				$is_selected = ($menu == $id) ? 'selected="selected"' : '';
				
				echo "<option value=\"$id\" $is_selected >$title</option>";
			
			endforeach;
		
		endif;
		
	}
	
	function widget($args, $instance) {
		
		extract( $args );
		
	 	$title = apply_filters('widget_title', $instance['title'] );
		$menu = $instance['menu'];
		
		echo $before_widget;
		
			if ($title) : 
				echo $before_title . $title . $after_title;
			endif;
		
			if (function_exists('menusplus')) :
				menusplus($menu);
			endif;
		
		echo $after_widget;
		
	}
	
}

// Template tags

function menusplus($passed_menu_id = null) {

	global $wpdb;
	$items_table = $wpdb->prefix . "menusplus";
	$menus_table = $wpdb->prefix . "menusplus_menus";
	$wpdb->show_errors();
	
	// Returns the best possible menu_id
	
	if (!$passed_menu_id) :
		$item = $wpdb->get_row("SELECT * FROM $menus_table ORDER BY id ASC", ARRAY_A );
		$menu_id = $item['id'];
	else :
		$menu_id = $passed_menu_id;
	endif;
	
	$items = $wpdb->get_results("SELECT * FROM $items_table WHERE menu_id = $menu_id ORDER BY list_order ASC", ARRAY_A);
	
	if (count($items) > 0) :
		foreach ($items as $item) :

			$id = $item['id'];
			$wp_id = $item['wp_id'];
			$list_order = $item['list_order'];
			$type = $item['type'];
			$class = $item['class'];
			$url = $item['url'];
			$label = $item['label'];
			$children = $item['children'];
			$children_order = $item['children_order'];
			$children_order_dir = $item['children_order_dir'];
			
			if ($type == "page") :
			
				if ($children == "true") :
				
					$children = get_pages("child_of=$wp_id");

					foreach ($children as $child) :
						$wp_id = $wp_id . "," . $child->ID;
					endforeach;
					
					wp_list_pages("title_li=&include=$wp_id&sort_column=$children_order&sort_order=$children_order_dir");
				
				else :
								
					wp_list_pages("title_li=&include=$wp_id");
				
				endif;
			
			endif;
			
			if ($type == "cat") :
			
				if ($children == "true") :
				
					$children = get_categories("child_of=$wp_id&hide_empty=0");

					foreach ($children as $child) :
						$wp_id = $wp_id . "," . $child->cat_ID;
					endforeach;
					
					wp_list_categories("title_li=&hide_empty=0&include=$wp_id&order_by=$children_order&order=$children_order_dir");
				
				else :
			
					wp_list_categories("title_li=&hide_empty=0&include=$wp_id");
				
				endif;
			
			endif;
			
			if ($type == "url") :
			
				echo "<li class=\"$class\">";
				echo "<a href=\"$url\">$label</a>";
				echo "</li>";
		
			endif;

		endforeach;
    endif;

}

?>