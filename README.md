# wordpress-fastcgi-cache
Quick tool utilities to manage a fastcgi cache enabled server


## Nginx Configuration

In order to setup Nginx to use fast cgi cache we must configure Nginx in a couple of different places. This plugin assumes that the fast cgi cache will be located in the directory `/var/cache/nginxfastcgi`

So first lets make that directory on the server:

	makedir -p /var/cache/nginxfastcgi

Also create a `global` directory in your nginx `etc` directory:
	
	mkdir /etc/nginx/global

Make sure you set the ownership to whatever user your nginx user is running under.

After that lets create a file called `/etc/nginx/global/wordpress_cache.conf` and place the below code inside of it. You can change it where you like, however be cautious of the `$fastcgi_skipcache` stuff. Also make sure to update your fast cgi socket file location to where php-fpm places its socket. 

	```# example FastCGI cache exception rules
    set $fastcgi_skipcache 0;
    if ($http_cookie ~ "users_login_cookie") {
      set $fastcgi_skipcache 1;
    }

    if ( $request_uri ~ "/wp/wp-login.php" ) {
      set $fastcgi_skipcache 1;
    }

    if ( $request_uri ~ "/wp/wp-admin" ) {
      set $fastcgi_skipcache 1;
    }

    # Global restrictions configuration file.
    # Designed to be included in any server {} block.</p>
    location = /favicon.ico {
        log_not_found off;
        access_log off;
    }

    location = /robots.txt {
        allow all;
        log_not_found off;
        access_log off;
    }

    # Deny all attempts to access hidden files such as .htaccess, .htpasswd, .DS_Store (Mac).
    # Keep logging the requests to parse later (or to pass to firewall utilities such as fail2ban)
    location ~ /\. {
        deny all;
    }

    # Deny access to any files with a .php extension in the uploads directory
    # Works in sub-directory installs and also in multisite network
    # Keep logging the requests to parse later (or to pass to firewall utilities such as fail2ban)
    location ~* /(?:uploads|files)/.*\.php$ {
        deny all;
    }


    # WordPress single blog rules.
    # Designed to be included in any server {} block.

    # This order might seem weird - this is attempted to match last if rules below fail.
    # http://wiki.nginx.org/HttpCoreModule
    location / {
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'Accept-Encoding' 'gzip';
        try_files $uri $uri/ /index.php?$args;
    }

    # Add trailing slash to */wp-admin requests.
    rewrite /wp-admin$ $scheme://$host$uri/ permanent;

    # Directives to send expires headers and turn off 404 error logging.
    location ~* ^.+\.(ogg|ogv|svg|svgz|eot|otf|woff|mp4|ttf|rss|atom|jpg|jpeg|gif|png|ico|zip|tgz|gz|rar|bz2|doc|xls|exe|ppt|tar|mid|midi|wav|bmp|rtf)$ {
           access_log off; log_not_found off; expires max;
    }

    # Pass all .php files onto a php-fpm/php-fcgi server.
    location ~ [^/]\.php(/|$) {
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        if (!-f $document_root$fastcgi_script_name) {
            return 404;
        }

	fastcgi_buffers 8 16k;
        fastcgi_buffer_size 32k;

        add_header X-Cache $upstream_cache_status;
        fastcgi_cache fastcgicache;
        fastcgi_cache_bypass $fastcgi_skipcache;
        fastcgi_no_cache $fastcgi_skipcache;        

        # This is a robust solution for path info security issue and works with "cgi.fix_pathinfo = 1" in /etc/php.ini (default)
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param APP_ENV prod;
        fastcgi_read_timeout 600;
    }```

After creating your global wordpress cache configuration you will need to define your cache system in nginx by adding a file `/etc/nginx/global/cache.conf` and adding the following lines:

	```fastcgi_cache_path /var/cache/nginxfastcgi levels=1:2 keys_zone=fastcgicache:5m inactive=5m max_size=64m;
	fastcgi_cache_key $scheme$request_method$host$request_uri;
	# note: can also use HTTP headers to form the cache key, e.g.
	fastcgi_cache_lock on;
	fastcgi_cache_use_stale error timeout invalid_header updating http_500;
	fastcgi_cache_valid 5m;
	fastcgi_ignore_headers Cache-Control Expires Set-Cookie;

	proxy_buffer_size   128k;
	proxy_buffers   4 256k;
	proxy_busy_buffers_size   256k;```

And finally after that file is created you must include it in the nginx configuration by adding the following line to `/etc/nginx/nginx.conf` below `client_max_body_size`:

	include             /etc/nginx/global/cache.conf;

