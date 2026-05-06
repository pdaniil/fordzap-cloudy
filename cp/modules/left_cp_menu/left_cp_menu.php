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
