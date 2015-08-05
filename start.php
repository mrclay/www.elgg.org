<?php

elgg_register_event_handler('init', 'system', function() {
	// Only admins can post blogs
	elgg_register_plugin_hook_handler('container_permissions_check', 'object', function($hook, $type, $return, $params) {
		if ($params['subtype'] == 'blog') {
			return $params['user']->isAdmin();
		}
	});

	// Remove the duplicate add button for dicussions
	elgg_register_plugin_hook_handler('register', 'menu:title', function($hook, $type, $menu) {
		if (elgg_get_context() == 'discussion') {
			foreach ($menu as $index => $item) {
				if ($item->getName() == 'add') {
					unset($menu[$index]);
				}
			}
		}
		return $menu;
	});
	
	// Support for some very old URLs
	elgg_register_page_handler('requirements.php', function() {
		http_response_code(301);
		forward('http://learn.elgg.org/en/latest/intro/install.html#requirements');
	});
	
	elgg_register_page_handler('external.php', function() {
		http_response_code(301);
		forward('/plugins');
	});
	
	elgg_register_page_handler('license.php', function() {
		http_response_code(301);
		forward('http://learn.elgg.org/en/latest/intro/license.html');
	});
	
	elgg_register_css('widgets/messageboard/content', elgg_get_simplecache_url('widgets/messageboard/content.css'));
	
	if (function_exists("elgg_ws_unexpose_function")) {
		elgg_ws_unexpose_function('auth.gettoken');
	}

	// filter new friendships and new bookmarks from river
	elgg_register_plugin_hook_handler('creating', 'river', function($hook, $type, $item) {
		$view = $item['view'];
		switch ($view) {
			case 'river/relationship/friend/create':
			case 'river/object/bookmarks/create':
				return false;
				break;
		}
	});

	// Delete messages from a user who is being deleted
	// TODO(ewinslow): Move to Elgg core??
	elgg_register_event_handler('delete', 'user', function($event, $type, $user) {

		// make sure we delete them all
		$entity_disable_override = access_get_show_hidden_status();
		access_show_hidden_entities(true);

		$messages = elgg_get_entities_from_metadata(array(
			'type' => 'object',
			'subtype' => 'messages',
			'metadata_name' => 'fromId',
			'metadata_value' => $user->getGUID(),
			'limit' => 0,
		));

		if ($messages) {
			foreach ($messages as $e) {
				$e->delete();
			}
		}

		access_show_hidden_entities($entity_disable_override);
	});

	// convert messageboard to private message interface
	elgg_register_widget_type('messageboard', elgg_echo("customizations:widget:pm"), elgg_echo("customizations:widget:pm:desc"), array("profile"));
	
	// Forward to referrer if posting PM from a widget
	elgg_register_plugin_hook_handler('forward', 'system', function() {
		if (get_input('pm_widget') == true) {
			return $_SERVER['HTTP_REFERER'];
		}
	});

	// do not want the pages link in hover menu
	elgg_unextend_view('profile/menu/links', 'pages/menu');

	// button for flushing apc cache
	elgg_register_plugin_hook_handler('register', 'menu:admin_control_panel', function($hook, $type, $menu, $params) {
		$options = array(
			'name' => 'flush_apc',
			'text' => elgg_echo('apc:flush'),
			'href' => 'action/admin/flush_apc',
			'is_action' => true,
			'link_class' => 'elgg-button elgg-button-action',
		);
		$menu[] = ElggMenuItem::factory($options);
		return $menu;
	});

	$actions = __DIR__ . "/actions";
	elgg_register_action('admin/flush_apc', "$actions/admin/flush_apc.php", 'admin');
});
