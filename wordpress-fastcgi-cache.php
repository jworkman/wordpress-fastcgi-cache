<?php
/*
Plugin Name: Wordpress FastCGI Cache
Description: Manages the FastCGI cache for the server.
Plugin URI:  https://github.com/jworkman/wordpress-fastcgi-cache
Author:      Justin Workman
Author URI:  https://github.com/jworkman
Version: 	 1.0.1

Wordpress FastCGI Cache is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
{Plugin Name} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Wordpress FastCGI. If not, see https://www.gnu.org/licenses/gpl-3.0.en.html.
*/



function fastcgi_page_html()
{
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    require_once __DIR__ . '/src/jworkman/templates/fastcgi.php';
}


function fastcgi_page()
{
    // add_menu_page(
    //     'FastCGI Cache Management',
    //     'FastCGI',
    //     'edit_files',
    //     'fastcgi',
    //     'fastcgi_page_html',
    //     'dashicons-dashboard',
    //     20
    // );
	add_management_page('FastCGI Cache Clear', 'FastCGI Cache Clear', 'activate_plugins', 'fastcgi', function(){
		exec('rm -rf /var/cache/nginxfastcgi/*');
		require_once __DIR__ . '/src/jworkman/templates/fastcgi.php';
		return '';
	});
}
add_action('admin_menu', 'fastcgi_page');
