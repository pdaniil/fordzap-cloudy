<?php
defined('_ASTEXE_') or die('No access');
/*
Скрипт модуля для левого меню панели управления.

Меню состоит из следующих частей:
- кнопка на главную страницу панели управления
- категории товаров каталога
- задачи панели управления
*/

//ДЛЯ ВЫВОДА КАТЕГОРИЙ КАТАЛОГА ТОВАРОВ
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/dp_category_record.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/get_catalogue_tree.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/modules/left_cp_menu/catalogue_menu_helper.php");

//ДЛЯ ВЫВОДА ЗАДАЧ ПАНЕЛИ УПРАВЛЕНИЯ
//Определение функции проверки доступа к странице
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/control/control_helper.php");

?>
<!-- Добавляем поле поиска -->
<div style="padding: 10px;">
    <input type="text" id="menu-search" class="form-control" placeholder="Поиск в меню...">
</div>

<ul class="nav" id="side-menu">
    <?php
    //1. Кнопка главной страницы панели управления.
    ?>
    <li>
        <a href="/<?php echo $DP_Config->backend_dir; ?>"> <span class="nav-label">Панель управления</span></a>
    </li>
	
	<li>
		  <a href="javascript:void(0);" onclick="show_modal_search_main_page();">Проценка</a>
	</li>

	<li>
		<a class="link_shedule_wrapper" id="link_shedule_wrapper" style="display: flex;" href="https://fordzap.com/content/carservice/admin_console/index.php" target="_blank"><span class="counter_user_self_records" id="counter_user_self_records"></span> Календарь СТО</a>
	</li>
	<style>
	.counter_user_self_records {
		font-size: 10px;
		width: 15px;
		height: 15px;
		border: 1px solid;
		display: flex;
		justify-content: center;
		border-radius: 50%;
		color: orange;
	}
	.link_shedule_wrapper {
		display: flex;
		grid-gap: 5px;
	}
	</style>
    <?php
    //ВЫВОД КАТЕГОРИЙ ТОВАРОВ - только для для страниц, связанных с каталогом: Редактирование каталога и Кладовщики
    if( isset($module_modes_map[(string)$DP_Content->url]) )
    {
        ?>
        <li>
            <a href="javascript:void(0);"><span class="nav-label">Каталог товаров</span><span class="fa arrow"></span> </a>
            <?php
            $catalogue_tree_dump_PHP = json_decode($catalogue_tree_dump_JSON, true);

            printCatalogueBranch($catalogue_tree_dump_PHP);
            ?>
        </li>
        <?php
    }



    /*ВЫВОД ЗАДАЧ**/
    //Для работы с пользователями - для определения доступа к страницам
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

    //Массив для блоков и страниц по блокам
    $tabs = array();

    //Получаем перечнь групп задач панели управления:
    $control_groups_query = $db_link->prepare("SELECT * FROM `control_groups` ORDER BY `order` ASC;");
    $control_groups_query->execute();
    while( $group = $control_groups_query->fetch() )
    {
        $tabs[(string)$group["id"]] = array("caption"=>$group["caption"], "items"=>array());
    }


    //Получаем перечень всех задач:
    $control_panel_content_query = $db_link->prepare("SELECT * FROM `control_items` ORDER BY `order` ASC");
    $control_panel_content_query->execute();
    while( $item = $control_panel_content_query->fetch() )
    {
        $item["url"] = str_replace( array("<backend>"), $DP_Config->backend_dir, $item["url"]);

        //Добавляем, только, если у пользователя есть доступ
        if( is_anable($item) )
        {
            array_push($tabs[(string)$item["items_group"]]["items"], $item);
        }
    }



    //Выводим перечень задач на страницу:
    foreach($tabs as $key => $tab)
    {
        //В данном блоке нет доступных страниц
        if(count($tab["items"]) == 0)
        {
            continue;
        }
        ?>
        <li>
            <a href="javascript:void(0);"><span class="nav-label"><?php echo $tab["caption"];?></span><span class="fa arrow"></span> </a>
            <ul class="nav nav-second-level">


                <?php
                for($i=0; $i<count($tab["items"]); $i++)
                {
                    ?>
                    <li>
                        <a href="<?php echo $tab["items"][$i]["url"]; ?>">
                            <?php
                            if( !empty($tab["items"][$i]["fontawesome_class"]) )
                            {
                                ?>
                                <i class="<?php echo $tab["items"][$i]["fontawesome_class"]; ?>"></i>
                                <?php
                            }
                            ?>
                            <?php echo $tab["items"][$i]["caption"]; ?>
                        </a>
                    </li>
                    <?php
                }//for()
                ?>
            </ul>
        </li>
        <?php
    }//foreach()
    ?>
</ul>
 
<!-- Добавляем JavaScript для фильтрации меню -->
<script>
// === Глобальные переменные ===
var searchTimeoutId = null;
var isProcessing = false;
var currentFilter = '';
var productsCache = [];
var categoryCache = {};
var isCategoryLoading = false;

// === ФИЛЬТРАЦИЯ по нескольким словам (AND) во всех полях ===
// === ФИЛЬТРАЦИЯ по нескольким словам (AND) во всех полях ===
function filterProductsByFields(products, searchText) {
    if (!searchText || searchText.trim() === '') {
        return products;
    }
    // Разбиваем строку на отдельные слова (убираем лишние пробелы)
    var words = searchText.trim().toLowerCase().split(/\s+/);
    
    return products.filter(function(p) {
        // Для каждого слова проверяем, содержится ли оно в любом из полей
        return words.every(function(word) {
            return (p.article && p.article.toLowerCase().includes(word)) ||
                   (p.manufacturer && p.manufacturer.toLowerCase().includes(word)) ||
                   (p.name && p.name.toLowerCase().includes(word)) ||
                   (p.status && p.status.toLowerCase().includes(word));
        });
    });
}


// === Функция открытия модалки ===
function show_modal_search_main_page() {
    console.log('show_modal_search_main_page()');
    $('#modalSearchMainPage').modal('show');
    resetSearchState();
    $('#mpResultsContainer').empty();
    $('#mpSearchCounter').hide();
    $('#mpSearchForm')[0].reset();
    currentFilter = '';
    productsCache = [];
    categoryCache = {};

    var url = '/shop/part_search?cp=1&own=1';
    $('#mpIframeSearch').attr('src', url);

    // Активная вкладка — "Исходная страница" (iframe)
    $('a[href="#mpTabIframe"]').tab('show');
    $('a[href="#mpTabResults"]').tab('show');
    $('a[href="#mpTabIframe"]').tab('show');
}

// === Сброс состояния ===
function resetSearchState() {
    if (searchTimeoutId) {
        clearTimeout(searchTimeoutId);
        searchTimeoutId = null;
    }
    isProcessing = false;
    $('#mpResultsContainer').empty();
    $('#mpSearchCounter').hide();
}

// === ФИЛЬТРАЦИЯ продуктов ===
function filterProducts(products) {
    var filtered = products.filter(function(p) {
        return p.productId &&
               p.article && p.article.trim() !== '' &&
               p.manufacturer && p.manufacturer.trim() !== '';
    });

    var seen = {};
    var unique = [];
    filtered.forEach(function(p) {
        if (!seen[p.productId]) {
            seen[p.productId] = true;
            unique.push(p);
        }
    });

    return unique;
}

// === ПАРСИНГ DOM ===
function parseIframeContent(doc) {
    var captions = doc.querySelectorAll('td.products_table_block_caption');
    var startRow = null;
    for (var cap of captions) {
        if (cap.textContent.trim() === 'В наличии на складе') {
            startRow = cap.closest('tr').nextElementSibling;
            break;
        }
    }
    if (!startRow) return [];

    var products = [];
    var currentRow = startRow;
    while (currentRow) {
        var nextCaption = currentRow.querySelector('td.products_table_block_caption');
        if (nextCaption && nextCaption.textContent.includes('Запрошенный артикул')) break;
        if (currentRow.classList.contains('next_page_box')) {
            currentRow = currentRow.nextElementSibling;
            continue;
        }

        var product = {};
        var tdMan = currentRow.querySelector('td.td_manufacturer');
        product.manufacturer = tdMan ? (tdMan.querySelector('span')?.getAttribute('title') || '') : '';
        
        var tdArt = currentRow.querySelector('td.td_article');
        if (tdArt) {
            var a = tdArt.querySelector('a');
            if (a) {
                // Берём чистый текст ссылки, убираем лишние пробелы
                product.article = a.textContent.trim();
                // Если текст содержит "Искать:", то обрезаем
                if (product.article.indexOf('Искать:') === 0) {
                    product.article = product.article.replace('Искать:', '').trim();
                }
            } else {
                product.article = tdArt.textContent.trim();
            }
        } else {
            product.article = '';
        }

        var tdName = currentRow.querySelector('td.td_name');
        product.name = tdName ? (tdName.querySelector('span')?.getAttribute('title') || '') : '';
        
        var tdTime = currentRow.querySelector('td.td_time_to_exe');
        product.status = tdTime ? (tdTime.querySelector('div')?.getAttribute('title') || '') : '';
        
        var tdInfo = currentRow.querySelector('td.td_info');
        product.address = tdInfo ? (tdInfo.querySelector('div.show_data_class')?.getAttribute('title') || '') : '';
        
        var productId = tdInfo ? parseInt(tdInfo.getAttribute('data-product-id'), 10) : null;
        if (isNaN(productId)) productId = null;
        product.productId = productId;

        products.push(product);
        currentRow = currentRow.nextElementSibling;
    }
    return products;
}

// === ЗАГРУЗКА CATEGORY_ID ===
function loadCategoriesForProducts(products, callback) {
    if (!products || products.length === 0) {
        callback(products);
        return;
    }

    var productIds = [];
    products.forEach(function(p) {
        if (p.productId && !categoryCache[p.productId]) {
            productIds.push(p.productId);
        }
    });

    if (productIds.length === 0) {
        callback(products);
        return;
    }

    isCategoryLoading = true;
    var loadingCount = productIds.length;
    var loadedCount = 0;

    productIds.forEach(function(pid) {
        $.ajax({
            url: '/api/v1/get_product_category.php?product_id=' + pid,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data && data.category_id) {
                    categoryCache[pid] = data.category_id;
                } else {
                    categoryCache[pid] = null;
                }
                loadedCount++;
                if (loadedCount === loadingCount) {
                    isCategoryLoading = false;
                    callback(products);
                }
            },
            error: function() {
                categoryCache[pid] = null;
                loadedCount++;
                if (loadedCount === loadingCount) {
                    isCategoryLoading = false;
                    callback(products);
                }
            }
        });
    });
}

// === ПОСТРОЕНИЕ КАРТОЧЕК ===
function buildResultsCards(products, filterName) {
	// Применяем фильтр по всем полям
    var filtered = filterProductsByFields(products, filterName);

    if (!filtered || filtered.length === 0) {
        return '<div class="alert alert-info">Товары не найдены.</div>';
    }

    var html = '<div class="mp-cards-grid">';
    filtered.forEach(function(p) {
        var productId = p.productId;
        var categoryId = categoryCache[productId] || null;
        var link = '#';
        if (productId && categoryId) {
            link = '/cp/shop/catalogue/products/product?category_id=' + categoryId + '&product_id=' + productId;
        } else if (productId) {
            link = '/cp/shop/catalogue/products/product?product_id=' + productId;
        }

        html += `
            <div class="mp-card">
                <div class="mp-card-brand-article">
                    <span class="mp-brand">${p.manufacturer}</span>
                    <span class="mp-article">${p.article}</span>
                </div>
                <div class="mp-card-name">${p.name}</div>
                <div class="mp-card-status">${p.status}</div>
                <div>
                    <a href="${link}" target="_blank" class="mp-card-link">Страница товара →</a>
                </div>
            </div>
        `;
    });
    html += '</div>';
	showUpdateIndicator();
    return html;
}

// === ОБНОВЛЕНИЕ РЕЗУЛЬТАТОВ ===
function updateResults(products, filterName) {
    // Фильтруем для счётчика
    var filtered = filterProductsByFields(products, filterName);

    var counter = document.getElementById('mpSearchCounter');
    var counterValue = document.getElementById('mpCounterValue');
    if (counter && counterValue) {
        if (filtered.length > 0) {
            counterValue.textContent = filtered.length;
            counter.style.display = 'block';
        } else {
            counter.style.display = 'none';
        }
    }

    var container = document.getElementById('mpResultsContainer');
    if (container) {
        // Передаём исходный массив, фильтрация выполнится внутри buildResultsCards
        container.innerHTML = buildResultsCards(products, filterName);
    }
}
// === ИНДИКАТОР ОБНОВЛЕНИЯ ===
function showUpdateIndicator() {
    var indicator = document.getElementById('mpUpdateIndicator');
    if (!indicator) return;
    indicator.style.display = 'inline-block';
    clearTimeout(window.indicatorTimeout);
    window.indicatorTimeout = setTimeout(function() {
        indicator.style.display = 'none';
    }, 2000);
}

// === ОБНОВЛЕНИЕ ПАРСИНГОМ ===
function refreshResultsFromIframe() {
    var iframe = document.getElementById('mpIframeSearch');
    if (!iframe) return;

    try {
        var doc = iframe.contentDocument || iframe.contentWindow.document;
        if (!doc) throw new Error('Нет доступа к документу iframe');

        var products = parseIframeContent(doc);
        products = filterProducts(products);
        productsCache = products;

        loadCategoriesForProducts(products, function(updatedProducts) {
            updateResults(productsCache, currentFilter);
        });
    } catch (e) {
        console.error('Ошибка при парсинге iframe:', e);
        $('#mpResultsContainer').html('<div class="alert alert-danger">Не удалось получить данные из iframe.</div>');
        $('#mpSearchCounter').hide();
    }
}

// === ИНИЦИАЛИЗАЦИЯ ===
$(document).ready(function() {

    $('#mpIframeSearch').on('load', function() {
        var src = this.src;
        if (src && src !== 'about:blank') {
            setTimeout(function() {
                refreshResultsFromIframe();
            }, 500);
        }
    });

    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'dataUpdated') {
            refreshResultsFromIframe();
        }
    });

    $('#mpSearchName').on('input', function() {
        var filter = $(this).val();
        currentFilter = filter;
        if (productsCache.length > 0) {
            updateResults(productsCache, filter);
        }
    });

    $('#modalSearchMainPage').on('hidden.bs.modal', function() {
        resetSearchState();
        productsCache = [];
        categoryCache = {};
        $('#mpSearchName').val('');
        // $('#mpIframeSearch').attr('src', 'about:blank');
    });

});
    // Функция для фильтрации меню
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('menu-search');
        const menu = document.getElementById('side-menu');
        const menuItems = menu.getElementsByTagName('li');

        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();

            for (let i = 0; i < menuItems.length; i++) {
                const li = menuItems[i];
                const a = li.getElementsByTagName('a')[0];
                if (a) {
                    const text = a.textContent.toLowerCase();
                    // Проверяем, содержит ли текст ссылки фильтр
                    if (text.indexOf(filter) > -1) {
                        li.style.display = "list-item";
                        // Если это вложенный пункт, убедимся, что его родитель тоже отображается
                        let parent = li.parentElement;
                        while (parent && parent.id !== 'side-menu') {
                            if (parent.tagName === 'UL') {
                                parent.style.display = "block";
                                const parentLi = parent.parentElement;
                                if (parentLi && parentLi.tagName === 'LI') {
                                    parentLi.style.display = "list-item";
                                }
                            }
                            parent = parent.parentElement;
                        }
                    } else {
                        li.style.display = "none";
                    }
                }
            }

            // Дополнительно: скрыть все пустые группы меню
            const subMenus = menu.querySelectorAll('.nav-second-level');
            for (let i = 0; i < subMenus.length; i++) {
                const subMenu = subMenus[i];
                const visibleItems = subMenu.querySelectorAll('li[style="display: list-item;"], li:not([style])');
                if (visibleItems.length === 0) {
                    subMenu.style.display = "none";
                    const parentLi = subMenu.parentElement;
                    if (parentLi && parentLi.tagName === 'LI') {
                        parentLi.style.display = "none";
                    }
                } else {
                    subMenu.style.display = "block";
                    const parentLi = subMenu.parentElement;
                    if (parentLi && parentLi.tagName === 'LI') {
                        parentLi.style.display = "list-item";
                    }
                }
            }
        });
    });
</script>
