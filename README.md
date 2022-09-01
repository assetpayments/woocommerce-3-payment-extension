## woocommerce-6-payment-module

### Installation

* Backup your webstore and database
* Upload the module file assetpayments.zip via Plugins -> Add New -> Upload Plugin
* Activate the module in Plugins
* Configure the module settings:
  * User Id
  * Secret key
  * Template ID (default = 19)
  * Order statuses for successfuly processed payment
  * Enabled the module
  
### Notes
Tested and developed with Wordpress 5.9 WooCommerce 6.8.1 

### Troubleshooting
If you hosting service doesn't provide a FTP access, most probably you will have to install the extension before to install the payment module.

Alternatively you can just upload the upload directory content to wp-content/plugins/ directory.

In you see this message 'AssetPayments does not support your shop currency.' - check currency settings in 
WooCommerce -> Settings -> Main -> Currency settings -> Currency
AssetPayments extension supports USD, EUR, UAH, RUB and KZT by default. Change currency settings or add currency code to assetpayments.php (line 21). 

## Модуль оплаты WooCommerce 6

### Установка
* Создайте резервную копию вашего магазина и базы данных
* Загрузите файл модуля assetpayments.zip через Plugins -> Add New -> Upload Plugin
* Активируйте модуль AssetPayments в модулях оплаты 
* Задайте в настройках модуля:
  * Id магазина
  * Ключ магазина
  * Id шаблона (по-умолчанию = 19)
  * Статусы заказа в случае успешной и не успешной оплаты
  * Включите модуль

### Примечания
Разработано и протестировано с Wordpress 5.9 WooCommerce 6.8.1 

### Проблемы при установке
Если ваша хостинговая компания не предоставляет FTP доступ, то вам будет необходимо установить этот модуль прежде чем устанавливать данный модуль оплаты.

Другой вариант - это закачать на сервер содержимое папки upload в директорию wp-content/plugins/.

Если при установке модуля показывается сообщение "AssetPayments не поддерживает валюты Вашего магазина." - измените настройки валюты в WooCommerce -> Settings -> Main -> Currency settings -> Currency на EUR, RUB, ISD, UAH, KZT, или добавьте свой код валюты в файле assetpayments.php (строка 21)
