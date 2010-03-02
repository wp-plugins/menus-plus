<?php
/*
Plugin Name: Menus Plus+
Plugin URI: http://www.keighl.com/plugins/menus-plus/
Description: Create <strong>multiple</strong> customized menus with pages, categories, and urls. Use a widget or a template tag <code>&lt;?php menusplus(); ?&gt;</code></code>. <a href="themes.php?page=menusplus">Configuration Page</a>
Version: 1.8.2
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
		
		load_plugin_textdomain('menus_plus', false, 'menus-plus/languages');
		
		register_activation_hook(__FILE__, array(&$this, 'install'));
		
		add_action('admin_menu', array(&$this, 'add_admin'));
		
		add_action("admin_print_scripts", array(&$this, 'js_libs'));
		add_action("admin_print_styles", array(&$this, 'style_libs'));
		
		add_action('wp_ajax_menusplus_list', array(&$this, 'list_menu'));
		add_action('wp_ajax_menusplus_add_dialog', array(&$this, 'add_dialog'));
		add_action('wp_ajax_menusplus_edit_dialog', array(&$this, 'edit_dialog'));
		add_action('wp_ajax_menusplus_add', array(&$this, 'add'));
		add_action('wp_ajax_menusplus_validate', array(&$this, 'validate'));
		add_action('wp_ajax_menusplus_edit', array(&$this, 'edit'));
		add_action('wp_ajax_menusplus_sort', array(&$this, 'sort'));
		add_action('wp_ajax_menusplus_remove', array(&$this, 'remove'));
		add_action('wp_ajax_menusplus_remove_hybrid_dialog', array(&$this, 'remove_hybrid_dialog'));
		add_action('wp_ajax_menusplus_edit_hybrid_dialog', array(&$this, 'edit_hybrid_dialog'));
		add_action('wp_ajax_menusplus_edit_hybrid', array(&$this, 'edit_hybrid'));
		add_action('wp_ajax_menusplus_remove_hybrid', array(&$this, 'remove_hybrid'));
		
		
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

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		// Items Table
		
		$exists = $wpdb->query("SELECT * FROM $items_table");

		if (!$exists) :

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
				target text NULL,
				PRIMARY  KEY id (id)
				);";

			dbDelta($sql);

		else :
		
			$fields = array(
				array(
					"column" => "wp_id",
					"meta"   => "int NULL"
				),
				array(
					"column" => "list_order",
					"meta"   => "int DEFAULT '0' NOT NULL"
				),
				array(
					"column" => "type",
					"meta"   => "text NOT NULL"
				),
				array(
					"column" => "class",
					"meta"   => "text NULL"
				),
				array(
					"column" => "url",
					"meta"   => "text NULL"
				),
				array(
					"column" => "label",
					"meta"   => "text NULL"
				),
				array(
					"column" => "children",
					"meta"   => "text NULL"
				),
				array(
					"column" => "children_order",
					"meta"   => "text NULL"
				),
				array(
					"column" => "children_order_dir",
					"meta"   => "text NULL"
				),
				array(
					"column" => "menu_id",
					"meta"   => "int DEFAULT '1' NOT NULL"
				),
				array(
					"column" => "target",
					"meta"   => "text NULL"
				)
			);

			foreach ($fields as $field) :

				$check = $wpdb->query("SELECT " . $field['column'] . " FROM " . $items_table);
				if (!$check) {
					$sql = $wpdb->query("ALTER TABLE $items_table ADD " . $field['column'] . " " . $field['meta']);
					if ($sql) 
						echo "added" . $field['column'] . "<br/>";
				}

			endforeach;

		endif;
		
		// Menus Table
				
		$exists = $wpdb->query("SELECT * FROM $menus_table");

		if (!$exists) :
			
			$sql = "CREATE TABLE " . $menus_table . " (
				id int NOT NULL AUTO_INCREMENT,
				parent_id int NULL,
				menu_title text NULL,
				menus_description text NULL,
				PRIMARY  KEY id (id)
				);";

			dbDelta($sql);
						
			$default_title = 'Default';
			
			$data_array = array(
				'menu_title'     => $default_title,
			);
			
			$wpdb->insert($menus_table, $data_array );
			
		else :
		
			$fields = array(
				array(
					"column" => "parent_id",
					"meta"   => "int NULL"
				),
				array(
					"column" => "menu_title",
					"meta"   => "text NULL"
				),
				array(
					"column" => "menus_description",
					"meta"   => "text NULL"
				),
			);

			foreach ($fields as $field) :

				$check = $wpdb->query("SELECT " . $field['column'] . " FROM " . $menus_table);
				if (!$check) {
					$sql = $wpdb->query("ALTER TABLE $menus_table ADD " . $field['column'] . " " . $field['meta']);
				}

			endforeach;

		endif;
		
		$mp_version = "1.8.2";
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
		
		$menu_id_from_get = $_GET['menu_id'];
		$menu_id = $this->get_menu_id($menu_id_from_get); 
	
		$parent = $this->menu_has_parent($menu_id);
		
		$this->js($menu_id, $parent);
		$this->style();
	
		?> 

		<div class="wrap mp_margin_bottom">
	    	<h2>Menus Plus+ <span class="mp_heading">v<?php echo get_option('mp_version'); ?> <a href="http://www.keighl.com/plugins/menus-plus/">by Keighl</a></span></h2> 
		</div>
		<div class="wrap mp_margin_bottom">
			<div class="mp_buttons_left">
				<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=home&width=350&height=250" title="<?php _e("Add Home Page"); ?>"><?php _e("Home"); ?></a>
				<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=cat&width=350&height=250" title="<?php _e("Add a Category"); ?>"><?php _e("Category"); ?></a>
				<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=page&width=350&height=250" title="<?php _e("Add a Page"); ?>"><?php _e("Page"); ?></a>
				<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=post&width=350&height=250" title="<?php _e("Add a Post"); ?>"><?php _e("Post"); ?></a>
				<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=url&width=350&height=250" title="<?php _e("Add a URL"); ?>"><?php _e("URL"); ?></a>
				<?php if (!$parent):?>
				<a class="thickbox button" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_dialog&type=hybrid&width=350&height=250" title="<?php _e("Add a Hybrid Menu"); ?>"><?php _e("Hybrid Menu"); ?></a>
				<?php endif; ?>
			</div>
			<div class="mp_buttons_right">
				
				<span class="mp_menu_title"></span> 

				<select class="mp_switch_menu">
				</select>
				
				<?php if (!$parent):?>
				
				<a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_edit_menu_dialog&menu_id=<?php echo $menu_id; ?>&width=350&height=100" title="<?php _e("Edit Menu"); ?>">
					<img src="<?php echo plugin_dir_url( __FILE__ );?>images/edit.png" />
				</a>
				<a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_remove_menu_dialog&menu_id=<?php echo $menu_id; ?>&width=350&height=100" title="<?php _e("Delete Menu"); ?>">
					<img src="<?php echo plugin_dir_url( __FILE__ );?>images/remove.png" />
				</a>
				
				<?php endif; ?>
				
				<?php if ($parent) : ?>
					
					<a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_edit_hybrid_dialog&menu_id=<?php echo $menu_id; ?>&width=350&height=350" title="<?php _e("Edit Hybrid Menu"); ?>">
						<img src="<?php echo plugin_dir_url( __FILE__ );?>images/edit.png" />
					</a>
					
				<?php endif; ?>
				
				<a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_add_new_menu_dialog&width=350&height=100" title="<?php _e("New Menu"); ?>">
					<img src="<?php echo plugin_dir_url( __FILE__ );?>images/add.png" />
				</a>
				
			</div>
			<div class="clear_list_floats"></div>
		</div>
		<div class="wrap postbox" id="menusplus_list">
			<ul <?php if ($parent) { echo 'class="parent_menu_box"'; } ?> ></ul>
		</div>
		
		<?php if (!$parent) : ?>
			<div class="wrap">
				<p><?php _e('Template Tag') ?>:
	        	<input class="mp_template_tag" value="&lt;?php menusplus(<?php echo $menu_id; ?>); ?&gt;" />
				<a href="http://www.keighl.com/plugins/menus-plus/"><?php _e('Docs') ?></a></p>
			</div>
		<?php endif; ?>
					
	 	<?php 

	}
	
	function add_dialog() {
		
		$type = $_GET['type'];
		
		if (!$type) { exit(); }
				
		?>
		<div class="mp_add">
			<table cellspacing="16" cellpadding="0">
				<?php if ($type == "home") : ?>
					<tr>
						<td><div align="right"><?php _e("URL"); ?></div></td>
						<td><span class="mp_home_url"><? bloginfo('siteurl'); ?></span></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Label"); ?></div></td>
						<td><input class="add_label widefat" value="<?php _e('Home'); ?>" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Class"); ?></div></td>
						<td><input class="add_class widefat" value="" /></td>
					</tr>
				<?php elseif ($type == "cat") : ?>
					<tr>
						<td>
							<div align="right">
								<?php echo _e("Category"); ?>
							</div>
						</td>
						<td>
							<?php wp_dropdown_categories("hide_empty=0&name=add_wpid"); ?>
						</td>
					</tr>
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
							<select class="add_children_order">
								<option value="name"><?php _e("Name"); ?></option>
								<option value="ID"><?php _e("ID"); ?></option>
								<option value="count"><?php _e("Count"); ?></option>
								<option value="slug"><?php _e("Slug"); ?></option>
								<option value="term_group"><?php _e("Term Group"); ?></option>
								<?php
									if ( function_exists('mycategoryorder') ) :
										echo '<option value="order">' . _('My Category Order') . '</option>';
									endif;
								?>
							</select>
							<select class="add_children_order_dir">
								<option value="ASC">ASC</option>
								<option value="DESC">DESC</option>
							</select>
	                    </td>
				    </tr>
				<?php elseif ($type == "page") : ?>
					<tr>
						<td>
							<div align="right">
								<?php echo _e("Page"); ?>
							</div>
						</td>
						<td>
							<?php wp_dropdown_pages("name=add_wpid"); ?>
						</td>
					</tr>
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
							<select class="add_children_order">
								<option value="post_title"><?php _e("Title"); ?></option>
								<option value="post_date"><?php _e("Date"); ?></option>
								<option value="post_modified"><?php _e("Date Modified"); ?></option>
								<option value="ID"><?php _e("ID"); ?></option>
								<option value="post_author"><?php _e("Author"); ?></option>
								<option value="post_name"><?php _e("Slug"); ?></option>
								<?php
									if ( function_exists('mypageorder') ) :
										echo '<option value="menu_order">' . _('My Page Order') . '</option>';
									endif;
								?>
							</select>
							<select class="add_children_order_dir">
								<option value="ASC">ASC</option>
								<option value="DESC">DESC</option>
							</select>
	                    </td>
				    </tr>
				<?php elseif ($type == "post") : ?>
					<tr>
						<td>
							<div align="right">
								<?php echo _e("Post"); ?>
							</div>
						</td>
						<td>
							<select name="add_wpid">
								<?php $this->mp_dropdown_posts(); ?>
							</select>
						</td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Class"); ?></div></td>
						<td><input class="add_class widefat" value="" /></td>
					</tr>
				<?php elseif ($type == "url") : ?>
					<tr>
						<td><div align="right"><?php _e("URL"); ?></div></td>
						<td><input class="add_url widefat" value="http://" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Label"); ?></div></td>
						<td><input class="add_label widefat" value="" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Target"); ?></div></td>
						<td>
							<select class="add_target">
								<option value="_parent" selected="selected"><?php _e('Same window'); ?></option>
								<option value="_blank"><?php _e('New window'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Class"); ?></div></td>
						<td><input class="add_class widefat" value="" /></td>
					</tr>
				<?php elseif ($type == "hybrid") : ?>
					<tr>
						<td><div align="right"><?php _e("Label"); ?></div></td>
						<td><input class="add_label widefat" value="" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("URL"); ?></div></td>
						<td><input class="add_url widefat" value="" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Class"); ?></div></td>
						<td><input class="add_class widefat" value="" /></td>
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
				$target = $meta['target'];
			endforeach;
		endif;
		
		?>
		<div class="mp_edit">
			<input  type="hidden" value="<?php _e($type); ?>" class="edit_type" />
			<table cellspacing="16" cellpadding="0">
				<?php if ($type == "home") : ?>
					<tr>
						<td><div align="right"><?php _e("URL"); ?></div></td>
						<td><span class="mp_home_url"><? bloginfo('siteurl'); ?></span></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Label"); ?></div></td>
						<td><input class="edit_label widefat" value="<?php echo $label; ?>" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Class"); ?></div></td>
						<td><input class="edit_class widefat" value="<?php echo $class; ?>" /></td>
					</tr>
				<?php elseif ($type == "cat") : ?>
					<tr>
						<td>
							<div align="right">
								<?php _e("Category"); ?>
							</div>
						</td>
						<td>
							<?php wp_dropdown_categories("hide_empty=0&name=edit_wpid&selected=$wp_id"); ?>
						</td>
					</tr>
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
							<select class="edit_children_order">
								<option value="name" <?php if ($children_order == "name") : ?> selected="selected" <?php endif; ?> ><?php _e("Name"); ?></option>
								<option value="ID" <?php if ($children_order == "ID") : ?> selected="selected" <?php endif; ?> ><?php _e("ID"); ?></option>
								<option value="count" <?php if ($children_order == "count") : ?> selected="selected" <?php endif; ?> ><?php _e("Count"); ?></option>
								<option value="slug" <?php if ($children_order == "slug") : ?> selected="selected" <?php endif; ?> ><?php _e("Slug"); ?></option>
								<option value="term_group" <?php if ($children_order == "term_group") : ?> selected="selected" <?php endif; ?> ><?php _e("Term Group"); ?></option>
								<?php if ( function_exists('mycategoryorder') ) : ?>
									<option value="order" <?php if ($children_order == "order") : ?> selected="selected" <?php endif; ?> ><?php _e("My Category Order"); ?></option>	
								<?php endif; ?>
							</select>
							<select class="edit_children_order_dir">
								<option value="ASC" <?php if ($children_order_dir == "ASC") : ?> selected="selected" <?php endif; ?> >ASC</option>
								<option value="DESC" <?php if ($children_order_dir == "DESC") : ?> selected="selected" <?php endif; ?> >DESC</option>
							</select>
	                    </td>
				    </tr>
				<?php elseif ($type == "page") : ?>
					<tr>
						<td>
							<div align="right">
								<?php _e("Page"); ?>
							</div>
						</td>
						<td>
							<?php wp_dropdown_pages("name=edit_wpid&selected=$wp_id"); ?>
						</td>
					</tr>
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
							<select class="edit_children_order">
								<option value="post_title" <?php if ($children_order == "post_title") : ?> selected="selected" <?php endif; ?> ><?php _e("Title"); ?></option>
								<option value="post_date" <?php if ($children_order == "post_date") : ?> selected="selected" <?php endif; ?> ><?php _e("Date"); ?></option>
								<option value="post_modified" <?php if ($children_order == "post_modified") : ?> selected="selected" <?php endif; ?> ><?php _e("Date Modified"); ?></option>
								<option value="ID" <?php if ($children_order == "ID") : ?> selected="selected" <?php endif; ?> ><?php _e("ID"); ?></option>
								<option value="post_author" <?php if ($children_order == "post_author") : ?> selected="selected" <?php endif; ?> ><?php _e("Author"); ?></option>
								<option value="post_name" <?php if ($children_order == "post_name") : ?> selected="selected" <?php endif; ?> ><?php _e("Slug"); ?></option>
								<?php if ( function_exists('mypageorder') ) : ?>
									<option value="menu_order" <?php if ($children_order == "menu_order") : ?> selected="selected" <?php endif; ?> ><?php _e("My Page Order"); ?></option>	
								<?php endif; ?>
							</select>
							<select class="edit_children_order_dir">
								<option value="ASC" <?php if ($children_order_dir == "ASC") : ?> selected="selected" <?php endif; ?> >ASC</option>
								<option value="DESC" <?php if ($children_order_dir == "DESC") : ?> selected="selected" <?php endif; ?> >DESC</option>
							</select>
	                    </td>
				    </tr>
				<?php elseif ($type == "post") : ?>
					<tr>
						<td>
							<div align="right">
								<?php echo _e("Post"); ?>
							</div>
						</td>
						<td>
							<select name="edit_wpid">
								<?php $this->mp_dropdown_posts($wp_id); ?>
							</select>
						</td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Class"); ?></div></td>
						<td><input class="edit_class widefat" value="<?php echo $class; ?>" /></td>
					</tr>
				<?php elseif ($type == "url") : ?>
					<tr>
						<td><div align="right"><?php _e("URL"); ?></div></td>
						<td><input class="edit_url widefat" value="<?php echo $url; ?>" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Label"); ?></div></td>
						<td><input class="edit_label widefat" value="<?php echo $label; ?>" /></td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Target"); ?></div></td>
						<td>
							<select class="edit_target">
								<option value="_parent" <?php if ($target == "_parent") : ?> selected="selected" <?php endif; ?> ><?php _e('Same window'); ?></option>
								<option value="_blank" <?php if ($target == "_blank") : ?> selected="selected" <?php endif; ?> ><?php _e('New window'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td><div align="right"><?php _e("Class"); ?></div></td>
						<td><input class="edit_class widefat" value="<?php echo $class; ?>" /></td>
					</tr>
							
				<?php else : ?>
					
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
					<td><input class="new_menu_title widefat" value="" /></td>
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
					<td><input class="edit_menu_title widefat" value="<?php echo $title; ?>" /></td>
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
			<p><?php _e("Are you sure you want to delete the menu <strong>$title</strong>, and all menus beneath it?"); ?></p>
			<p>
				<a class="button" id="remove_menu_submit" rel="<?php echo $type; ?>"><?php _e("Delete"); ?></a>
				<a class="button" id="mp_cancel"><?php _e("Cancel"); ?></a>
			</p>
		</div>
		
		<?php
		
		exit();
		
	}
	
	function edit_hybrid_dialog() {
		
		$id = $_GET['menu_id'];
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$items_table = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();

		$menu = $wpdb->get_row("SELECT * FROM $menus_table WHERE id = $id", OBJECT );
		$item = $wpdb->get_row("SELECT * FROM $items_table WHERE wp_id = $id", OBJECT );
		
		$title = $menu->menu_title;
		$class = $item->class;
		$url = $item->url;
		
		?>
		<div class="mp_edit_hybrid">
		<table cellspacing="16" cellpadding="0">
			<tr>
				<td><div align="right"><?php _e("Label"); ?></div></td>
				<td><input class="edit_hybrid_label widefat" value="<?php echo $title; ?>" /></td>
			</tr>
			<tr>
				<td><div align="right"><?php _e("Class"); ?></div></td>
				<td><input class="edit_hybrid_class widefat" value="<?php echo $class; ?>" /></td>
			</tr>
			<tr>
				<td><div align="right"><?php _e("URL"); ?></div></td>
				<td><input class="edit_hybrid_url widefat" value="<?php echo $url; ?>" /></td>
			</tr>
			<tr>
				<td><div align="right"></div></td>
				<td>
					<a class="button" id="edit_hybrid_submit" rel="<?php echo $id; ?>"><?php _e("Update"); ?></a>
					<a class="button" id="mp_cancel"><?php _e("Cancel"); ?></a>
				</td>
			</tr>
		</table>
		</div>
		
		<?php
		
		exit();
		
	}
	
	function remove_hybrid_dialog() {
		
		$menu_id = $_GET['menu_id'];
		$id = $_GET['id'];
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$items_table = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();

		$menus = $wpdb->get_row("SELECT * FROM $menus_table WHERE id = $menu_id", ARRAY_A );
		
		$title = $menus['menu_title'];
		
		?>
		
		<div class="remove_menu">
			<p><?php _e("Are you sure you want to delete <strong>$title</strong>? All other menus and items beneath it will be deleted as well."); ?></p>
			<p>
				<a class="button" id="remove_hybrid_submit" rel="<?php echo $id; ?>"><?php _e("Delete"); ?></a>
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
				margin-right:20px;
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
				
				#menusplus_list ul.parent_menu_box li {
					background-color:#e35f00;
				}
				
				
				
					.list_item_left {
						float:left;
					}
					
						.list_item_left .list_item_title {
							font-size:1.5em;
							font-weight:bold;
							color:#ffffff;
						}
					
					
					.list_item_right {
						float:right;
					}
					
						.list_item_type {
							color:#fff;
						}
					
						.list_item_right a:link, .list_item_right a:visited {
							color:#ffffff;
							margin-left:6px;
							text-decoration:none;
						}
						
						.list_item_right a.mp_remove {
							color:#ffa3a3;
							cursor:pointer;
							margin-left:6px;
						}
					
					.clear_list_floats {
						clear: both;
					}
				
				.mp_validate {
					background-color:#c0402a;
					color:#fff;
				}
				
				.mp_home_url {
					color:#21759b;
				}
				
				.mp_in_order_to {
					color:#21759b;
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
						var target = $('select.add_target').val();
						
						// Validate
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_validate", 
								type:type,
								wp_id:wp_id,
								children:children,
								children_order:children_order,
								children_order_dir:children_order_dir,
								opt_class:opt_class,
								label:label,
								url:url,
								menu_id:<?php echo $menu_id; ?>,
								target:target
							},
							function(str) {
								$('input').removeClass('mp_validate');
								if (str == "1") {
									// URL issue
									alert('<?php _e('You must enter a valid URL.'); ?>');
									$('input.add_url').addClass('mp_validate');
								} else if (str == "2") {
									// Label issue
									alert('<?php _e('You must enter a label.'); ?>');
									$('input.add_label').addClass('mp_validate');
							 	} else {
									// Insert
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
											menu_id : <?php echo $menu_id; ?>,
											target : target
										},
										function(str) {
											$('input').removeClass('mp_validate');
											if (str == "") {
												tb_remove();
												menusplus_list(<?php echo $menu_id; ?>);
											} else {
												window.location.replace('themes.php?page=menusplus&menu_id=' + str);
											}
										}
									);
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
						var target = $('select.edit_target').val();
						
						// Validate
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_validate", 
								id:id,
								wp_id:wp_id,
								children:children,
								children_order:children_order,
								children_order_dir:children_order_dir,
								opt_class:opt_class,
								label:label,
								url:url,
								type:type,
								target : target
							},
							function(str) {
								$('input').removeClass('mp_validate');
								if (str == "1") {
									// URL issue
									alert('<?php _e('You must enter a valid URL.'); ?>');
									$('input.edit_url').addClass('mp_validate');
								} else if (str == "2") {
									// Label issue
									alert('<?php _e('You must enter a label.'); ?>');
									$('input.edit_label').addClass('mp_validate');
							 	} else {
									// Insert
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
											type:type,
											target : target
										},
										function(str) {
											$('input').removeClass('mp_validate');
											if (str == "") {
												tb_remove();
												menusplus_list(<?php echo $menu_id; ?>);
											}
										}
									);
								}
							}
						);
					}
				);
				
				// Edit Hybrid 
				
				$('.mp_edit_hybrid a#edit_hybrid_submit').live("click",
					function () {
						var menu_id = "<?php echo $menu_id; ?>";
						var label = $('input.edit_hybrid_label').val();
						var opt_class = $('input.edit_hybrid_class').val();
						var url = $('input.edit_hybrid_url').val();
						var type = "hybrid";
						// Validate
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_validate", 
								label:label,
								type:type,
								url:url,
								opt_class:opt_class
							},
							function(str) {
								$('input').removeClass('mp_validate');
								if (str == "1") {
									// URL issue
									alert('<?php _e('You must enter a valid URL.'); ?>');
									$('input.edit_hybrid_url').addClass('mp_validate');
								
								} else if (str == "2") {
									// Label issue
									alert('<?php _e('You must enter a label.'); ?>');
									$('input.edit_hybrid_label').addClass('mp_validate');
							 	} else {
									// Edit
									$.post(
										"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
										{
											action:"menusplus_edit_hybrid", 
											menu_id:menu_id,
											opt_class:opt_class,
											label:label,
											url:url
										},
										function(str) {
											$('input').removeClass('mp_validate');
											if (str == "") {
												tb_remove();
												menu_title(<?php echo $menu_id; ?>);
												menus_dropdown(<?php echo $menu_id; ?>);
												menusplus_list(<?php echo $menu_id; ?>);
											}
										}
									);
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
				
				$("a#remove_hybrid_submit").live("click",
					function () {
						var id = $(this).attr("rel");
						$.post(
							"<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php", 
							{
								action:"menusplus_remove_hybrid", 
								id : id
							},
							function (str) {
								tb_remove();
								menusplus_list(<?php echo $menu_id; ?>);
								menus_dropdown(<?php echo $menu_id; ?>);
								menu_title(<?php echo $menu_id; ?>);
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
								$('input').removeClass('mp_validate');
								if (str == "empty") {
									// Title issue
									alert('<?php _e('You must enter a title.'); ?>');
									$('input.new_menu_title').addClass('mp_validate');
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
								$('input').removeClass('mp_validate');
								if (str == "1") {
									// Title issue
									alert('You must enter a title.')
									$('input.edit_menu_title').addClass('mp_validate');
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
	
	function menu_has_children($menu_id) {
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		// Does this menu have children?
		
		$children = $wpdb->get_results("SELECT * FROM $menus_table WHERE parent_id = $menu_id", ARRAY_A);
		$children_a = array();

		if (!$children) :
			RETURN FALSE;
		else :
			foreach ($children as $child) :
				$children_a[] = $child['id'];
			endforeach;
			RETURN $children_a;
		endif;
		
	}
	
	function menu_has_parent($menu_id) {
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		// Does this menu have a parent?
		
		$menu = $wpdb->get_row("SELECT parent_id FROM $menus_table WHERE id = $menu_id", OBJECT);
		
		if (!$menu->parent_id) {
			RETURN FALSE;
		} else {
			RETURN $menu->parent_id;
		}
		
	}
		
	function menu_parent($menu_id){
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
				
		$menu = $wpdb->get_row("SELECT parent_id FROM $menus_table WHERE id = $menu_id", OBJECT);
		
		if (!$menu->parent_id) {
			RETURN FALSE;
		} else {
			$parent_id = $menu->parent_id;
			$parent = $wpdb->get_row("SELECT menu_title FROM $menus_table WHERE id = $parent_id", OBJECT);
			RETURN $parent->menu_title;
		}
		
	}
	
	function menu_walker($menu_id){
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$parent = $this->menu_has_parent($menu_id); 
		
		if ($parent) :
			$level = 0;
			while ($parent) :
				$parent = $this->menu_has_parent($parent);
				$level++;
			endwhile;
			RETURN $level * 15 ."px";
		else :
			RETURN 0 . "px";
		endif;
		
	}
	
	function menu_title() {
		
		$menu_id = $_POST['menu_id'];
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$menu = $wpdb->get_row("SELECT * FROM $menus_table WHERE id = $menu_id", ARRAY_A );
		
		$parent = $this->menu_has_parent($menu_id);
		
		if ($parent) :
			echo $this->menu_parent($menu_id) . " : ";
		else :
			//echo $menu['menu_title'];
		endif;
		exit();
		
	}
	
	function menus_dropdown() {
		
		$menu_id = $_POST['menu_id'];
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$items = $wpdb->get_results("SELECT * FROM $menus_table WHERE parent_id is NULL ORDER BY menu_title ASC", ARRAY_A );
		
		if ($items) :
		
			foreach ($items as $item) :
				
				$id = $item['id'];
				$title = $item['menu_title'];
		
				$is_selected = ($id == $menu_id) ? 'selected="selected"' : '';		
				$level = $this->menu_walker($id);
				echo "<option style=\"margin-left:$level;\" $is_selected value=\"$id\">$title</option>";
				
				$children = $this->menu_has_children($id);
								
				if ($children) :
					foreach ($children as $child) :
						
						$meta = $wpdb->get_row("SELECT * FROM $menus_table WHERE id = $child", OBJECT);
						$level = $this->menu_walker($child);
						$is_selected = ($child == $menu_id) ? 'selected="selected"' : '';	
						echo "<option style=\"margin-left:$level;\" $is_selected value=\"$child\">$meta->menu_title</option>";
											
					endforeach;
				endif;
			
			endforeach;
		
		endif;
		
		exit();
		
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
				$menu_id = $item['menu_id'];
				
				switch ($type) :
					case "home" :
						$sort_title = $label;
						$display_type = _("Home");
						break;
					case "page" :
						$page = get_page($wp_id);
						$sort_title = $page->post_title;
						$display_type = _("Page");
						break;
					case "post" :
						$page = get_page($wp_id);
						$sort_title = $page->post_title;
						$display_type = _("Post");
						break;
					case "cat" :
						$cat = $wpdb->get_row("SELECT * FROM $wpdb->terms WHERE term_ID='$wp_id'", OBJECT);
						$sort_title = $cat->name; 
						$display_type = _("Category");
						break;
					case "url" :
						$sort_title = $label;
						$display_type = _("URL");
						break;
					case "hybrid" :
						$menu = $wpdb->get_row("SELECT * FROM $menus_table WHERE id = $wp_id", OBJECT );
						$sort_title = $menu->menu_title;
						$display_type = _("Hybrid");
						break;
					default:
				endswitch;
				?>
				<li id="mp_id_<?php echo $id; ?>" class="mp_list_item">
					<div class="list_item_left">
						<div class="list_item_title">
							<?php echo $sort_title; ?>
						</div>
					</div>
					<div class="list_item_right">
						<div>
							
							<span class="list_item_type"><?php echo $display_type; ?></span> 						
							
							<?php if ($type == "hybrid") : ?>
								
								<a  href="themes.php?page=menusplus&menu_id=<?php echo $wp_id; ?>" title="<?php _e("Edit"); ?> <?php echo $sort_title; ?>">
									<img src="<?php echo plugin_dir_url( __FILE__ );?>images/edit.png" align="absmiddle" />
								</a>
								
								<a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_remove_hybrid_dialog&id=<?php echo $id; ?>&menu_id=<?php echo $wp_id; ?>&width=350&height=200" title="<?php _e("Remove"); ?>">
									<img src="<?php echo plugin_dir_url( __FILE__ );?>images/remove.png" align="absmiddle" />
								</a>
								
							<?php else : ?>
								
								<a class="thickbox" href="<?php echo get_option('siteurl'); ?>/wp-admin/admin-ajax.php?action=menusplus_edit_dialog&id=<?php echo $id; ?>&width=350&height=250&current_id=<?php echo $menu_id; ?>" title="<?php _e("Edit"); ?> <?php echo $sort_title; ?>">
									<img src="<?php echo plugin_dir_url( __FILE__ );?>images/edit.png" align="absmiddle" />
								</a>
								
								<a class="mp_remove" id="mp_remove_<?php echo $id; ?>" title="<?php _e("Remove"); ?>">
									<img src="<?php echo plugin_dir_url( __FILE__ );?>images/remove.png" align="absmiddle" />
								</a>
							
							<?php endif; ?>
															
							
							
						</div>
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
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$type  = $_POST['type'];
		$wp_id = $_POST['wp_id'];
			$wp_id = $this->is_undefined($wp_id);
		$class = $_POST['opt_class'];
			$class = $this->is_undefined($class);
		$label = $_POST['label'];
			$label = $this->is_undefined($label);
		$url   = $_POST['url'];
			$url = $this->is_undefined($url);
		$children = $_POST['children'];
			$children = $this->is_undefined($children);
		$children_order = $_POST['children_order'];
			$children_order = $this->is_undefined($children_order);
		$children_order_dir = $_POST['children_order_dir'];
			$children_order_dir = $this->is_undefined($children_order_dir);
		$menu_id = $_POST['menu_id'];
		$target = $_POST['target'];
			$target = $this->is_undefined($target);
		
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
				'menu_id'				=> $menu_id,
				'target'                => $target
				);
							
		if ($type == "hybrid") :
					
			$title = stripslashes($label);
			
			$menu_data_array = array(
				'menu_title' => $title,
				'parent_id'	 => $menu_id
			);

			$wpdb->insert($menus_table, $menu_data_array );
			$last_result = $wpdb->insert_id;
			
			// WPID will represent the hybrid menus id. 
			
			$data_array = array(
					'type'					=> $type,
					'wp_id'     			=> $last_result,
					'list_order' 			=> $highest_order,
					'class'      			=> $class,
					'url'       			=> $url,
					'label'      			=> $label,
					'children'   			=> $children,
					'children_order' 		=> $children_order,
					'children_order_dir' 	=> $children_order_dir,
					'menu_id'				=> $menu_id,
					'target'                => $target
					);
					
			echo $last_result; // Redirect for new hybrid list
			
		endif;

		$wpdb->insert($items_table, $data_array );
		exit();

	}

	function edit() {
		
		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();
		
		$id  = $_POST['id'];
		$type  = $_POST['type'];
		$wp_id = $_POST['wp_id'];
			$wp_id = $this->is_undefined($wp_id);
		$class = $_POST['opt_class'];
			$class = $this->is_undefined($class);
		$label = $_POST['label'];
			$label = $this->is_undefined($label);
		$url   = $_POST['url'];
			$url = $this->is_undefined($url);
		$children = $_POST['children'];
			$children = $this->is_undefined($children);
		$children_order = $_POST['children_order'];
			$children_order = $this->is_undefined($children_order);
		$children_order_dir = $_POST['children_order_dir'];
			$children_order_dir = $this->is_undefined($children_order_dir);
		$target = $_POST['target'];
			$target = $this->is_undefined($target);
		
		$data_array = array(
				'wp_id'     			=> $wp_id,
				'class'      			=> $class,
				'url'       			=> $url,
				'label'      			=> $label,
				'children'   			=> $children,
				'children_order' 		=> $children_order,
				'children_order_dir' 	=> $children_order_dir,
				'target'				=> $target
				);
				
		$class = stripslashes($class);
		$label = stripslashes($label);
		$url = stripslashes($url);
				
		$where = array('id' => $id);
		$wpdb->update($items_table, $data_array, $where );
		
		exit();
		
	}
		
	function edit_hybrid() {
		
		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$menu_id = $_POST['menu_id'];
		$label = $_POST['label'];
		$class = $_POST['opt_class'];
		$url = $_POST['url'];
		
		$class = stripslashes($class);
		$label = stripslashes($label);
		
		// Update Class
		$data_array = array(
			'class' => $class,
			'url' => $url
		);
		
		$where = array('wp_id' => $menu_id);
		$wpdb->update($items_table, $data_array, $where );
		
		// Update Title
		$data_array = array(
			'menu_title' => $label,
		);
		
		$where = array('id' => $menu_id);
		$wpdb->update($menus_table, $data_array, $where );
		
		exit();
	}	
		
	function validate() {
		
		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$menus_table = $wpdb->prefix . "menusplus_menus";
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
		$target = $_POST['target'];
		
		$class = stripslashes($class);
		$label = stripslashes($label);
		$url = stripslashes($url);
		
		// Use PHP 5.2.0's filter_var for URL regex, if earlier PHP use the defined regex
		
		if (version_compare("5.2", phpversion(), "<=")) { 
			$valid_url = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
		} else {
			$regex = "((https?|ftp)\:\/\/)?"; // SCHEME
			$regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
		    $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
		    $regex .= "(\:[0-9]{2,5})?"; // Port
		    $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
		    $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
		    $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor
			$valid_url = preg_match("/^$regex$/", $url);
		}	
	
		if ($type == "url") :
					
			
			if (!$valid_url) :
				echo "1"; // URL error
				exit();
			endif;
			
			if (empty($label)) :
				echo "2"; // Label error
				exit();
			endif;
			
		elseif ($type == "home") :
		
			if (empty($label)) :
				echo "2"; // Label error
				exit();
			endif;
			
		elseif ($type == "hybrid") :
		
			if (empty($label)) :
				echo "2"; // Label error
				exit();
			endif;
			
			if (!empty($url)) :
				if (!$valid_url) :
					echo "1"; // URL error
					exit();
				endif;
			endif;
			
		endif;
		
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
		
		$count = $wpdb->query("SELECT * from $menus_table WHERE parent_id is NULL");

		if ($count == 1) :
		
			echo 1;
			
		else :
		
			$wpdb->query("DELETE from $items_table WHERE menu_id = $id");
			$wpdb->query("DELETE from $menus_table WHERE id = $id");
			$children = $this->menu_has_children($id);
			if ($children) :
				foreach ($children as $child) :
					$wpdb->query("DELETE from $menus_table WHERE id = $child");
					$wpdb->query("DELETE from $items_table WHERE wp_id = $child");
					$wpdb->query("DELETE from $items_table WHERE menu_id = $child");
				endforeach;
			endif;
			
		endif;

		exit();
		
	}

	function remove() {

		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$wpdb->show_errors();

		$id = $_POST['id'];
		$id = trim($id, 'mp_remove_');
		
		$wpdb->query("DELETE from $items_table WHERE id = $id");

		exit();

	}
	
	function remove_hybrid() {
		
		$id = $_POST['id'];
		
		global $wpdb;
		$items_table = $wpdb->prefix . "menusplus";
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$item = $wpdb->get_row("SELECT * FROM $items_table WHERE id = $id", OBJECT);
		$menu_id = $item->wp_id; 
		
		$wpdb->query("DELETE FROM $menus_table WHERE id = $menu_id");
		
		// Now delete all the items beneath this hybrid
		
		$wpdb->query("DELETE FROM $items_table WHERE wp_id = $menu_id");
		$wpdb->query("DELETE FROM $items_table WHERE menu_id = $menu_id");
		
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
		
		if ($str == "undefined") : 
			return null;
		else : 
			return $str;
		endif;
	}
	
	function mp_dropdown_posts($selected_wpid = null) {
		
		global $wpdb;
		$menus_table = $wpdb->prefix . "menusplus_menus";
		$wpdb->show_errors();
		
		$items = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC", ARRAY_A );
		
		if ($items) :
		
			foreach ($items as $item) :
				$wpid = $item['ID'];
				$post_title = $item['post_title'];
				$is_selected = ($wpid == $selected_wpid) ? 'selected="selected"' : '';
				echo "<option value=\"$wpid\" $is_selected >$post_title</option>";
			endforeach;
		
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
		
		$items = $wpdb->get_results("SELECT * FROM $menus_table WHERE parent_id is NULL ORDER BY id ASC", ARRAY_A );
		
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
			$target = $item['target'];
			
			$siteurl = get_bloginfo('siteurl');
			
			if ($type == "home") :
			
				echo '<li class="' . $class . '">';
				echo '<a href="' . $siteurl . '">' . $label . '</a>';
				echo "</li>";
			
			endif;
			
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
			
			if ($type == "post") :
											
				global $wpdb;
				$menus_table = $wpdb->prefix . "menusplus_menus";
				$wpdb->show_errors();

				$posts = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE ID = '$wp_id'", ARRAY_A );

				if ($posts) :

					foreach ($posts as $post) :
						echo '<li class="' . $class . '">';
						echo '<a href="' . get_permalink($wp_id) . '">' . $post['post_title'] . '</a>'; 
						echo '</li>';
					endforeach;

				endif;
							
			endif;
			
			if ($type == "cat") :
			
				if ($children == "true") :
				
					$children = get_categories("child_of=$wp_id&orderby=$children_order&hide_empty=0");

					foreach ($children as $child) :
						$wp_id = $wp_id . "," . $child->cat_ID;
					endforeach;
					
					wp_list_categories("title_li=&hide_empty=0&include=$wp_id&orderby=$children_order&order=$children_order_dir");
				
				else :
			
					wp_list_categories("title_li=&hide_empty=0&include=$wp_id");
				
				endif;
			
			endif;
			
			if ($type == "url") :
			
				echo "<li class=\"$class\">";
				echo "<a href=\"$url\" target=\"$target\">$label</a>";
				echo "</li>";
		
			endif;
			
			if ($type == "hybrid") :
			
				$menu = $wpdb->get_row("SELECT menu_title FROM $menus_table WHERE id = $wp_id");
				echo "<li class='$class'><a href='$url'>$menu->menu_title</a>";
				echo "<ul class='children'>";
					menusplus($wp_id);
				echo "</ul></li>";
			
			endif;
			
		endforeach;
    endif;

}

?>