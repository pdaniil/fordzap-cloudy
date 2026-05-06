<?php
class DP_Config
{
	//Общие настройки сайта:
	public $site_name = 'Автозапчасти';//Название сайта
	public $domain_path = 'https://fordzap.com/';//Домен сайта
	public $backend_dir = 'cp';//Каталог бэкэнда
	public $list_page_limit = '20';//Количество элементов на странице списка
	public $min_password_len = '8';//Минимальная длина пароля
	public $description_tag = '';//Тег description
	public $keywords_tag = '';//Тег keywords
	public $show_ssl_checker = '1';/*Показывать уведомление проверки SSL-сертификата*/
	public $show_phone_mask = '1';/*Показывать маску ввода телефона*/
	public $country_phone_mask = 'ru';/*Страна маски ввода телефона*/
	public $is_async_search = '1'; /*Включить асинхронную проценку*/
	
	//Настройки вывода title
	public $show_page_title = '1';//Показывать заголовок страницы
	public $show_site_name = '1';//Показывать название сайта
	public $page_title_first = '1';//Заголовок страницы перед названием сайта
	
	//Настройка обновлений
	public $update_server = 'http://updates.intask.pro/';//Сервер обновлений
	public $intask_server = 'https://intask.pro/';//Сервер уведомлений
	public $product = 'docpart';//Продукт
	
	//Настройки БД
	public $host = 'localhost';/*Сервер БД*/
	public $user = 'm71automai';/*Пользователь БД*/
	public $password = 'xJZi7KTTJK2BPkM_';/*Пароль БД*/
	public $db = 'm71automai';/*Основная БД*/
	
	//Для акцивации пользователей
	public $secret_succession = '{NQynGq}SJyyG3U(}Ya-w';/*Код, добавляемый в md5 - для предотвращения подбора кодов активации*/
	
	
	// Настройки почты:
	public $from_name = 'Ford Zap';//Имя отправителя
	public $from_email = 'm71auto@yandex.ru';//Адрес отправителя
	public $smtp_mode = '1';//Режим отправки через SMTP-авторизацию. enabled or disabled (включен или выключен)
	public $smtp_encryption = 'ssl';//Шифрование
	public $smtp_host = 'smtp.yandex.ru';//Хост почтового сервера
	public $smtp_port = '465';//Порт почтового сервера
	public $smtp_username = 'm71auto@yandex.ru';//Имя пользователя почтового сервера
	public $smtp_password = 'llzktzrucvqntsoi';//Пароль почтового сервера
	
	
	//Интернет-магазин:
	public $products_count_for_page = '10';/*Количество товаров на одну страницу по умолчанию*/
	public $product_url = 'alias';/*Как вывоить url страниц товаров*/
	public $tech_key = 'R819zz6kYNN1CsG9wALJSbgLCzZYOZn3Dto8GXV8fe2ESv3VNG6YXEg8mFRZ7TWc0WoAc1';/*Ключ для вызовов скриптов протокола управления заказами*/
	public $products_table_mode = '0';/*Формат таблицы найденных товаров*/
	public $shop_currency = '643';/*Валюта магазина*/
	public $currency_show_mode = 'sign_before';/*Вариант обозначения валюты*/
	public $price_rounding = '4';/*Округление цен*/
	public $ucats_login = 'fordzap.com';/*Логин каталога Ucats*/
	public $ucats_password = 'xJZi7KTTJK2BPkM_';/*Пароль каталога Ucats*/
	public $ucats_shiny = '1';/*Показывать каталог Ucats-Шины на главной*/
	public $ucats_disks = '1';/*Показывать каталог Ucats-Диски на главной*/
	public $ucats_accessories = '1';/*Показывать каталог Ucats-Аксессуры на главной*/
	public $ucats_to = '1';/*Показывать каталог Ucats-ТО на главной*/
	public $ucats_oil = '1';/*Показывать каталог Ucats-Масла на главной*/
	public $ucats_akb = '1';/*Показывать каталог Ucats-АКБ на главной*/
	public $ucats_caps = '1';/*Показывать каталог Ucats-Колпаки на главной*/
	public $ucats_bolty = '1';/*Показывать каталог Ucats-Болты на главной*/
	public $ucats_part_info = '1';/*Включить сервис Ucats-part_info (характеристики и картинки по товару)*/
	public $suppliers_api_debug = '1';/*Включить отладку API поставщиков*/
	public $ucats_url = 'https://ucats.ru/';/*URL каталога Ucats*/
	public $api_ucats_url = 'https://api.ucats.ru/';/*URL каталога Ucats (API каталога Ucats)*/
	public $partial_payment = '';/*Вкл-выкл функции частичной оплаты заказов*/
	public $partial_payment_min_percent = '50';/*Минимальный процент при частичной оплате заказа клиентом*/
	public $client_overdraft = '';/*Вкл-выкл овердравт клиента*/
	public $client_overdraft_value = '';/*Допустимый овердравт клиента*/
	public $order_without_auth = '';/*Вкл-выкл функции покупки без регистрации*/
	public $ucats_filter_brends = '';/*Фильтр брендов Ucats*/
	public $order_pay_on_place = '1';/*Вкл-выкл кнопки Оплата при получении*/
	public $orders_statuses_notifications_settings = '1';/*Настройка отправки уведомлений по конкретным статусам*/
	public $ucats_crosses = '1';/*Вкл-выкл запроса кроссов от ucats*/
	public $max_time_answer_storage = '20';/*Максимальное время ожидания ответа склада, сек.*/
	public $simple_register_available = '1'; /*Вкл-выкл простую регистрацию/авторизацию по телефону*/
	public $section_send_request = '1'; /*Отображение секции VIN запроса на главной*/
	
	//Настройки почты для загрузки прайс-листов
	public $prices_email_server = '';/*Почтовый сервер*/
	public $prices_email_encryption = 'off';/*Шифрование*/
	public $prices_email_port = '';/*Порт*/
	public $prices_email_username = '';/*Пользователь*/
	public $prices_email_password = '';/*Пароль*/
	
	
	//Технические настройки
	public $tmp_dir_prices_upload = '/tmp/prices_upload_files';/*Путь к временному каталогу для загрузки прайс-листов*/
	
	public $chpu_search_config = array(
		"chpu_search_on"=>true,//Поиск через ЧПУ включен
		
		"slash_code"=>"&ms;",//Код для слеша в наименовании производителя
		
		//ДАЛЕЕ КОНФИГУРАЦИЯ УРОВНЕНИЙ ЧПУ:
		
		
		"level_1"=>array("url"=>"parts", "h1"=>"Запчасти", "title_tag"=>"Раздел поиска автозапчастей по артикулу", "description_tag"=>"Раздел поиска автозапчастей по артикулу с ценами и наличием", "keywords_tag"=>"поиск автозапчастей, поиск автозапчастей по артикулу", "content_type"=>"text", "content"=>"<div class=\"col-md-12\">Раздел поиска автозапчастей по артикулу - корневая страница. Здесь ничего нет. Введите артикул в текстовую строку и нажмите Enter.</div>"),//Конфигурация для первого раздела. URL строгий (задается здесь). Здесь только один вариант - корневой раздел ЧПУ-проценки. Аргументы для подстановки в содержимое и метаданные не доступны. Но, можно менять содержимое и метаданные. Если содержимое - php-скрипт, то, путь указывать от корня сайта, начиная со знака / (точно накже и для других уровней) 
		
		
		
		"level_2"=>array(
			"mode_1"=>array("url"=>"brands", "h1"=>"Бренды", "title_tag"=>"Раздел получения брендов по артикулу", "description_tag"=>"Раздел получения брендов при поиске по артикулу", "keywords_tag"=>"производители автозапчастей, названия производителей автозапчастей", "content_type"=>"text", "content"=>"<div class=\"col-md-12\">Раздел получения брендов по артикулу. Здесь ничего нет. Введите артикул в текстовую строку и нажмите Enter.</div>"),//Первый режим второго уровня. URL строгий (задается здесь). Логический промежуточный раздел для подбора производителей по артикулу. Аргументы для подстановки в содержимое и метаданные не доступны. Но, можно менять содержимое и метаданные.
			
			
			"mode_2"=>array("url"=>"all", "h1"=>"Без учета бренда", "title_tag"=>"Поиск автозапчастей по артикулу без учета бренда", "description_tag"=>"Поиск автозапчастей по артикулу без учета бренда - наличие и цены", "keywords_tag"=>"любые запчасти, поиск запчастей без учета производителя, наличие цены автозапчастей по артикулу", "content_type"=>"text", "content"=>"<div class=\"col-md-12\">Это логический раздел ЧПУ-проценки, который используется в случае, если производитель по артикулу не был найден. Здесь ничего нет. Введите артикул в текстовую строку и нажмите Enter.</div>"),//Второй режим второго уровня. URL строгий (задается здесь). Случай, когда при поиске по артикулу не был найден ни один производитель. В этом случае пользователь был переброшен на поиск остатков по тем поставщикам, у которых нет уточнения по бренду. В этом случае на втором разделе выбирается этот режим. Аргументы для подстановки в содержимое и метаданные не доступны. Но, можно менять содержимое и метаданные.
			
			
			"mode_3"=>array("h1"=>"Бренд <manufacturer>", "title_tag"=>"Автозапчасти с брендом <manufacturer>", "description_tag"=>"Автозапчасти с брендом <manufacturer> с ценами и наличием в интернет-магазине", "keywords_tag"=>"бренд <manufacturer>, автозапчасти с брендом <manufacturer>, описание бренда <manufacturer>", "content_type"=>"text", "content"=>"<div class=\"col-md-12\">Корневой раздел бренда <manufacturer>. Информация не заполнена. Введите артикул в текстовую строку и нажмите Enter.</div>")//Третий режим второго уровня. URL динамический (в адресную строку может вводиться произвольное значение). Случай, когда на втором уровне указан конкретный производитель. Можно менять содержимое и метаданные. Кроме этого, можно использовать код <manufacturer> в содержимом и метаданных, который заменится на название производителя
			
			),//Конфигурация для второго раздела
		
		
		//Третий уровень. На нем ВСЕГДА АРТИКУЛ
		"level_3"=>array(
			"mode_1"=>array("h1"=>"Товары с артикулом <article>", "title_tag"=>"Подбор товаров с артикулом <article>", "description_tag"=>"Подбор автотоваров с артикулом <article>", "keywords_tag"=>"бренды с артикулом <article>, <article>, автозапчасти <article>"),//Первый режим третьего уровня. Случай, когда на втором уровне тоже mode_1. Т.е. пользователь перешел на поиск по артикулу из поисковой строки - идет подбор брендов по артикулу. Содержимое менять НЕЛЬЗЯ, т.к. страница выводится скриптом проценки. Метаданные менять МОЖНО. Можно использовать код <article> в метаданных, который заменится на строку артикула
		
		
			"mode_2"=>array("h1"=>"Цены и наличие ВСЕХ товаров с артикулом <article>", "title_tag"=>"Цены и наличие ВСЕХ товаров с артикулом <article> без учета производителя", "description_tag"=>"Цены и наличие ВСЕХ товаров с артикулом <article> без учета производителя, поскольку он не был найден", "keywords_tag"=>"цены на товары с артикулом <article>, цены <article>, автозапчасти <article>"),//Второй режим третьего уровня. Случай, когда на втором уровне тоже mode_2. Т.е. когда при поиске по артикулу не был найден ни один производитель - идет переброс сюда на поиск остатков по тем поставщикам, у которых нет уточнения по бренду. Т.е. здесь уже идет показ цен. Содержимое менять НЕЛЬЗЯ, т.к. страница выводится скриптом проценки. Метаданные менять МОЖНО. Кроме этого, можно использовать код <article> в метаданных, который заменится на строку артикула
			
			
			
			"mode_3"=>array("h1"=>"Цены и наличие <manufacturer> <article>", "title_tag"=>"Цены и наличие на товар <manufacturer> <article>", "description_tag"=>"Цены и наличие на товар <manufacturer> <article> в интернет-магазине", "keywords_tag"=>"<manufacturer> <article>, товар <manufacturer> <article>, <manufacturer> <article> купить в интернет-магазине")//Третий режим третьего уровня. Когда на втором уровне тоже mode_3. Т.е. когда был наден и явно указан конкретный производитель. Т.е. эта страница, на которой покупатель видит цены и наличие товара с конкретным производителем и артикулом. URL и второго и третьего уровня - динамический. Содержимое менять НЕЛЬЗЯ, т.к. страница выводится скриптом проценки. Метаданные менять МОЖНО. Кроме этого, можно использовать коды <article> и <manufacturer> в метаданных, которые заменятся на строку артикула и название производителя
		)
		
		);/*Конфигурация для ЧПУ-проценки*/
	
	
	//Настройки UCatalog
	public $UCatalog_active = '';/*Показывать каталог*/
	public $type_show_cars = '';/*Показывать вкладку Легковые*/
	public $type_show_trucks = '';/*Показывать вкладку Грузовые*/
	public $type_show_moto = '';/*Показывать вкладку Мотоциклы*/
	
	public $popular_cars = 'ALFA ROMEO, AUDI, BMW, CHEVROLET, CITROEN, DAEWOO, DODGE, FIAT, FORD, GREAT WALL, HONDA, HUMMER, HYUNDAI, INFINITI, JAGUAR, JEEP, KIA, LADA, LAND ROVER, LEXUS, MAZDA, MERCEDES-BENZ, MITSUBISHI, NISSAN, OPEL, PEUGEOT, PORSCHE, RENAULT, SAAB, SEAT, SKODA, SSANGYONG, SUBARU, SUZUKI, TOYOTA, VOLVO, VW';/*Список популярных марок для вкладки Легковые*/
	public $popular_trucks = 'ALFA ROMEO, AUDI, BMW, CHEVROLET, CITROEN, DAEWOO, DODGE, FIAT, FORD, GREAT WALL, HONDA, HUMMER, HYUNDAI, INFINITI, JAGUAR, JEEP, KIA, LADA, LAND ROVER, LEXUS, MAZDA, MERCEDES-BENZ, MITSUBISHI, NISSAN, OPEL, PEUGEOT, PORSCHE, RENAULT, SAAB, SEAT, SKODA, SSANGYONG, SUBARU, SUZUKI, TOYOTA, VOLVO, VW';/*Список популярных марок для вкладки Грузовые*/
	public $popular_moto = 'CAGIVA, BMW, KAWASAKI, JAWA, HONDA, PEUGEOT, SUZUKI, YAMAHA';/*Список популярных марок для вкладки Мотоциклы*/
	
	public $filter_cars = '';/*Список скрытых марок для вкладки Легковые*/
	public $filter_trucks = '';/*Список скрытых марок для вкладки Грузовые*/
	public $filter_moto = '';/*Список скрытых марок для вкладки Мотоциклы*/
	
	//Настройки страницы проценки
	public $outer_sort_field = 'price';/*Поле внешней сортировки*/
	public $sort_asc_desc = 'asc';/*Направление сортировки*/
	public $inner_sort_field = 'time_to_exe';/*Поле внутренней сортировки*/
	public $flag_time_head = '1';/*Поднимать позиции с определенным сроком выше остальных*/
	public $max_time_to_exe = '2';/*Максимальный срок позиций, которые необходимо поднимать выше остальных*/
	public $view_manufacturer_filter = '1';/*Показывать фильтр производителей*/
	public $view_storage_filter = '1';/*Показывать фильтр складов*/
	public $show_filter = '1';/*Развернуть фильтр*/
	public $show_search_string = '1';/*Показывать строку поиска*/
	public $show_range_blocks = '1';/*Показывать блоки: Цена доставки, Сроки доставки, Наличие*/
	public $local_crosses = '1';/*Использовать локальную таблицу кроссов*/
	public $list_brends_crosses = '1';/*Использовать таблицу кроссов для формирования производителей*/
	public $show_cookie = '1';/*Показывать уведомление о сборе куки на сайте*/
	public $return_available = '1'; //Включить возвраты
	public $retention_percentage = ''; //Процент удержания
	public $retention_percentage_text = ''; //Уведомление при оформлении возврата
	public $catalogue_html_way = 'pagination';/*Способ отображения каталога для покупателя*/
	public $allow_edit_system_content = '';/*Разрешить редактирование системных материалов*/
	public $levam_code = '';/*Каталог Levam OEM - атрибут code*/
	public $is_telegram_notify_enable = '1';/*Включить отправку пользователей через Телеграм*/
	public $telegram_bot_token = '6838244183:AAFhVZopy7LMgiWmn7bHA7nzXAurAw7WNP0';/*Токен Телеграм Бота*/
}
?>