# первоначальная установка приложения
install:
	composer install
	php install.php
	php vendor/bin/phinx migrate