# Назначение

Бэкапы и восстановление БД MySQL.

Поддержка ОС:
- Linux
- Windows

Поддержка БД:
- MySQL, Percona
- MariaDB

Запуск:
- немедленное выполнение
- вывод команд в терминал для ручного запуска



## Структура папок и файлов

Пример для:
```php
new Backup([
	rootDirectoryPath:      '/path/to/backups/',
	fullDaysPeriod:         2,
	incrementalHoursPeriod: 12,
]);
```

```
/path/to/backups/
	backups/
		2000-01-01/
		2000-01-01_12/
		2000-01-02_00/
		2000-01-02_12/
		2000-01-03/
		2000-01-03_12/
		...
	restore/
		2000-01-02_12/
			2000-01-01/
			2000-01-01_12/
			2000-01-02_00/
			2000-01-02_12/
	lock
```



## Пример работы

```php
//Инициализация
$b = new Backup([
	rootDirectoryPath: '/path/to/backups/',
]);

//Бэкап
$b->backup();

//Восстановление
$b->restore('2000-01-01');
```
