<?
namespace S5\Siteman\Templates\PhpFpm;

use S5\Siteman\Templates\ITemplate;



class Main implements ITemplate {
	public function __construct (
		protected string $siteDirName,
		protected string $userGroup   = '',
		protected string $user        = 'www-data',
		protected string $group       = 'www-data',
		protected string $listenOwner = 'www-data',
		protected string $listenGroup = 'www-data',
		protected string $listenMode  = '0666',
	) {
		if ($userGroup) {
			[$user, $group]    = explode(':', $userGroup);
			$this->user        = $user;
			$this->group       = $group;
			$this->listenOwner = $user;
			$this->listenGroup = $group;
		}
	}



public function getText (): string {
$text = <<<EOF
[general]

user  = $this->user
group = $this->group

listen = /var/www/$this->siteDirName/server/php-fpm.sock

listen.owner = $this->listenOwner
listen.group = $this->listenGroup
listen.mode  = $this->listenMode

pm                       = dynamic
pm.max_children          = 5
pm.start_servers         = 2
pm.min_spare_servers     = 1
pm.max_spare_servers     = 3
pm.max_requests          = 500

access.log = /var/www/$this->siteDirName/server/logs/php-fpm/access.log

catch_workers_output    = yes
decorate_workers_output = yes

php_flag[display_errors]   = on
php_admin_flag[log_errors] = on
php_admin_value[error_log] = /var/www/$this->siteDirName/server/logs/php-fpm/php.log

EOF;

return $text;
}
}
