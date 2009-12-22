<?php
/*
Plugin Name: Menus Plus+
Plugin URI: http://www.keighl.com/plugins/menus-plus/
Description: Create a customized list of pages and categories in any order you want! To return the list use the template tag <code>&lt;?php menusplus(); ?&gt;</code></code> in your template. <a href="themes.php?page=menusplus">Configuration Page</a>
Version: 1.1
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
		
		wp_enqueue_style('thickbox');
		
		register_activation_hook(__FILE__, array(&$this, 'install'));
		
		add_action('admin_menu', array(&$this, 'add_admin'));
		
		add_action("admin_print_scripts", array(&$this, 'js_libs'));
		
		add_action('wp_ajax_menusplus_list', array(&$this, 'list_menu'));
		add_action('wp_ajax_menusplus_add_dialog', array(&$this, 'add_dialog'));
		add_action('wp_ajax_menusplus_edit_dialog', array(&$this, 'edit_dialog'));
		add_action('wp_ajax_menusplus_add', array(&$this, 'add'));
		add_action('wp_ajax_menusplus_edit', array(&$this, 'edit'));
		add_action('wp_ajax_menusplus_sort', array(&$this, 'sort'));
		add_action('wp_ajax_menusplus_remove', array(&$this, 'remove'));
		
	}
	
	// Install
	
	function install() {
		
		global $wpdb;
		$table_name = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$exists = $wpdb->query("SELECT * FROM '$table_name'");

		if(!$exists) :

			$sql = "CREATE TABLE " . $table_name . " (
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
				PRIMARY  KEY id (id)
				);";

			dbDelta($sql);

		else :
		
			$sql = "ALTER TABLE " . $table_name . " (
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
				);";

			dbDelta($sql);

		endif;
		
		$mp_version = "1.1";
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
	
	
	// Views
	
	function admin() {

		$this->js();
		$this->style();
	
		?> 

		<div class="wrap mp_margin_bottom">
	    	<h2>Menus Plus +</h2> 
			<strong>v. 1.1</strong> <a href="http://www.keighl.com/">by Keighl</a>
		</div>
		<div class="wrap mp_margin_bottom">
			<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=cat&width=350&height=250" title="<?php _e("Add a Category"); ?>"><?php _e("Add Category"); ?></a>
			<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=page&width=350&height=250" title="<?php _e("Add a Page"); ?>"><?php _e("Add Page"); ?></a>
			<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=url&width=350&height=250" title="<?php _e("Add a URL"); ?>"><?php _e("Add URL"); ?></a>
		</div>
		<div class="wrap postbox" id="menusplus_list">
			<ul></ul>
		</div>
		<div class="wrap">
			<p>In your template files, use:
        	<code>&lt;?php menusplus(); ?&gt;</code></p>
			<p><a href="http://www.keighl.com/plugins/menus-plus/">Docs</a></p>
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
		$table_name = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();

		$metas = $wpdb->get_results("SELECT * FROM $table_name WHERE id = $id", ARRAY_A );
		
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
	
	function style() { ?>

		<style>
		
			.mp_margin_bottom {
				margin-bottom:15px;
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

	function js() {
		?>

		<script type="text/javascript">
			
			jQuery(document).ready(function($) {

				// Preloads
				
				menusplus_list();
								
				// Add
				
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
								url:url
							},
							function(str) {
								if (str == "1") {
									// URL issue
									alert('You must enter a valid URL (http://www.example.com).')
									$('input.add_url').css({'background-color' : '#c0402a' , 'color' : '#ffffff'});
								}
								if (str == "2") {
									// Label issue
									alert('You must enter a label.')
									$('input.add_label').css({'background-color' : '#c0402a' , 'color' : '#ffffff'});
								} 
								if (!str) {
									tb_remove();
									menusplus_list();
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
									alert('You must enter a valid URL (http://www.example.com).')
									$('input.edit_url').css({'background-color' : '#c0402a' , 'color' : '#ffffff'});
								}
								if (str == "2") {
									// Label issue
									alert('You must enter a label.')
									$('input.edit_label').css({'background-color' : '#c0402a' , 'color' : '#ffffff'});
								} 
								if (!str) {
									tb_remove();
									menusplus_list();
								}
							}
						);
					}
				);
				
				// Display or hide child sorting options
				
				// $("input[name='add_children'], input[name='edit_children']").live("change", 
				// 					function () {
				// 						var children = $(this).val();
				// 						if (children == "false") {
				// 							$('tr#children_order_box').fadeOut();
				// 						}
				// 						if (children == "true") {
				// 							$('tr#children_order_box').fadeIn();
				// 						}
				// 					}
				// 				);
				
				// Remove
				
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
								menusplus_list();
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
								menusplus_list();
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
				
				// Funtions
				
				function menusplus_list() {
					$.post(
						"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
						{action:"menusplus_list"},
						function(str) {
							//alert(str);
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
	
	function list_menu() {

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
		$table_name = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();
		
		$type  = $_POST['type'];
		$wp_id = $_POST['wp_id'];
		$class = $_POST['opt_class'];
		$label = $_POST['label'];
		$url   = $_POST['url'];
		$children = $_POST['children'];
		$children_order = $_POST['children_order'];
		$children_order_dir = $_POST['children_order_dir'];
		
		$class = stripslashes($class);
		$label = stripslashes($label);
		$url = stripslashes($url);
		
		$highest_order = $this->highest_order() + 1;

		$data_array = array(
				'type'					=> $type,
				'wp_id'     			=> $wp_id,
				'list_order' 			=> $highest_order,
				'class'      			=> $class,
				'url'       			=> $url,
				'label'      			=> $label,
				'children'   			=> $children,
				'children_order' 		=> $children_order,
				'children_order_dir' 	=> $children_order_dir
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

		$wpdb->insert($table_name, $data_array );

		exit();

	}

	function edit() {
		
		global $wpdb;
		$table_name = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();
		
		$id  = $_POST['id'];
		$wp_id = $_POST['wp_id'];
		$class = $_POST['opt_class'];
		$label = $_POST['label'];
		$url   = $_POST['url'];
		$children = $_POST['children'];
		$children_order = $_POST['children_order'];
		$children_order_dir = $_POST['children_order_dir'];
		$type = $_POST['type'];
		
		$data_array = array(
				'wp_id'     			=> $wp_id,
				'list_order' 			=> $highest_order,
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
		$wpdb->update($table_name, $data_array, $where );
		
		exit();
		
	}
	
	function sort() {

		global $wpdb;
		$table_name = $wpdb->prefix . "menusplus";
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
			$wpdb->update($table_name, $data_array, $where );

		endforeach;

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
	
	function highest_order() {
		
		// Find the highest order thus far
		// dapt for future release to accomdate sortable children
		
		global $wpdb;
		$table_name = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();
		
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

		return $highest_order;
		
		exit();
		
	}
	
}

// Template tags

function menusplus() {

	global $wpdb;
	$table_name = $wpdb->prefix . "menusplus";
	$wpdb->show_errors();
	
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