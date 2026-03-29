<?
namespace S5\Siteman\Templates\Nginx;

use S5\Siteman\Templates\ITemplate;



class Basic implements ITemplate {
	public function __construct (
		protected string $siteDirName,
		protected string $serverName
	) {
	}



public function getText (): string {
$siteDirName = $this->siteDirName;
$serverName  = $this->serverName;

$text = <<<EOF
server {
	listen      80;
	server_name $serverName;

	root /var/www/$siteDirName/site/public;

	access_log /var/www/$siteDirName/server/logs/nginx/access.log;
	error_log  /var/www/$siteDirName/server/logs/nginx/error.log error;

	index index.php index.htm index.html;

	location ~* /\. {
		return 404;
	}

	location / {
		try_files \$uri \$uri/ =404;
	}

	location ~* \.php$ {
		try_files    \$uri =404;
		fastcgi_pass unix:/var/www/$siteDirName/server/php-fpm.sock;
		include      fastcgi.conf;
	}
}

EOF;

return $text;
}
}
