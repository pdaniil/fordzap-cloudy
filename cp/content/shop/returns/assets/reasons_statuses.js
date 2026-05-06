function delete_status(id) {
    let obj = new URLSearchParams({
        tech_key: tech_key,
        csrf_guard_key: csrf_guard_key,
        action: "delete",
        id: id
    });
    obj = obj.toString();
    jQuery.ajax({
        type: "POST",
        async: true, //Запрос синхронный
        url: main_url + "/content/shop/returns/ajax/ajax_statuses.php",
        dataType: "json",//Тип возвращаемого значения
        data: obj,
        success: function (answer) {
            show_statuses();
        }
    });
}


function update_status(id) {
    let obj = new URLSearchParams({
        tech_key: tech_key,
        csrf_guard_key: csrf_guard_key,
        action: "update",
        caption: document.querySelector("#block_status_caption_" + id).value,
        color: document.querySelector("#block_status_color_" + id).value,
        id: id
    });
    obj = obj.toString();
    jQuery.ajax({
        type: "POST",
        async: true, //Запрос синхронный
        url: main_url + "/content/shop/returns/ajax/ajax_statuses.php",
        dataType: "json",//Тип возвращаемого значения
        data: obj,
        success: function (answer) {
            show_statuses();
        }
    });
}

function insert_status(block) {
    if (block.value == "")
        alert("Заполните статуса причины.");
    else {
        let obj = new URLSearchParams({
            tech_key: tech_key,
            csrf_guard_key: csrf_guard_key,
            action: "insert",
            caption: block.value,
            color: "#26ad5f"
        });
        obj = obj.toString();
        jQuery.ajax({
            type: "POST",
            async: true, //Запрос синхронный
            url: main_url + "/content/shop/returns/ajax/ajax_statuses.php",
            dataType: "json",//Тип возвращаемого значения
            data: obj,
            success: function (answer) {
                show_statuses();
            }
        });
    }
}


function add_status_block(obj = null) {
    if (obj == null) {
        let block =
            `
                <div class="block_settings">
                    <input type="text" value="" placeholder="Название" class="form-control"  onblur="insert_status(this)">
                </div>
            `;
        document.querySelector("#statuses").innerHTML += block;
    } else {
        let block = `
            <div class="block_settings">
            <input type="text" value="${obj.caption}" placeholder="Название" class="form-control" id="block_status_caption_${obj.id}" onblur="update_status(${obj.id})"> <input type="color" value="${obj.color}"  class="form-control" id="block_status_color_${obj.id}" onblur="update_status(${obj.id})"> `;
        if (obj.caption != "Оформлена" && obj.caption != "Закрыта") {
            block += `<div class="delete_icon"><i class="fas fa-trash" onclick="delete_status(${obj.id})"></i></div>`;
        }

        block += `</div>`;

        document.querySelector("#statuses").innerHTML += block;
    }
}

function show_statuses() {
    let obj = new URLSearchParams({
        tech_key: tech_key,
        csrf_guard_key: csrf_guard_key,
        action: "select",
    });
    obj = obj.toString();


    jQuery.ajax({
        type: "POST",
        async: true, //Запрос синхронный
        url: main_url + "/content/shop/returns/ajax/ajax_statuses.php",
        dataType: "json",//Тип возвращаемого значения
        data: obj,
        success: function (answer) {
            document.querySelector("#statuses").textContent = "";
            answer.data.forEach(el => {
                add_status_block(el);
            });
        }
    });
}


function delete_reason(reason_id) {
    let obj = new URLSearchParams({
        tech_key: tech_key,
        csrf_guard_key: csrf_guard_key,
        action: "delete",
        id: reason_id
    });
    obj = obj.toString();
    jQuery.ajax({
        type: "POST",
        async: true, //Запрос синхронный
        url: main_url + "/content/shop/returns/ajax/ajax_reasons.php",
        dataType: "json",//Тип возвращаемого значения
        data: obj,
        success: function (answer) {
            show_reason();
        }
    });
}

function update_reason(reason_id) {
    let obj = new URLSearchParams({
        tech_key: tech_key,
        csrf_guard_key: csrf_guard_key,
        action: "update",
        caption: document.querySelector("#block_reason_" + reason_id).value,
        id: reason_id
    });
    obj = obj.toString();
    jQuery.ajax({
        type: "POST",
        async: true, //Запрос синхронный
        url: main_url + "/content/shop/returns/ajax/ajax_reasons.php",
        dataType: "json",//Тип возвращаемого значения
        data: obj,
        success: function (answer) {
            show_reason();
        }
    });
}

function insert_reason(block) {
    if (block.value == "")
        alert("Заполните название причины.");
    else {
        let obj = new URLSearchParams({
            tech_key: tech_key,
            csrf_guard_key: csrf_guard_key,
            action: "insert",
            caption: block.value
        });
        obj = obj.toString();
        jQuery.ajax({
            type: "POST",
            async: true, //Запрос синхронный
            url: main_url + "/content/shop/returns/ajax/ajax_reasons.php",
            dataType: "json",//Тип возвращаемого значения
            data: obj,
            success: function (answer) {
                show_reason();
            }
        });
    }
}

//Добавление нового блока
function add_reason_block(obj = null) {
    //Если значение передали - значит отображаем в обачном виде
    if (obj != null) {
        let block = `
            <div class="block_settings">
                <input type="text" value="${obj.caption}" class="form-control" id="block_reason_${obj.id}" onblur="update_reason(${obj.id})"> <div class="delete_icon"><i class="fas fa-trash" onclick="delete_reason(${obj.id})"></i></div>
            </div>
            `;
        document.querySelector("#reasons").innerHTML += block;
    }
    //Иначе в формате добавления (пользователь сам нажан на кнопку добавления)
    else {
        let block = `
            <div class="block_settings">
                <input type="text" value="" class="form-control" onblur="insert_reason(this)">
            </div>
            `;
        document.querySelector("#reasons").innerHTML += block;
    }
}

//Отображение причин
function show_reason() {
    let obj = new URLSearchParams({
        tech_key: tech_key,
        csrf_guard_key: csrf_guard_key,
        action: "select",
    });
    obj = obj.toString();


    jQuery.ajax({
        type: "POST",
        async: true, //Запрос синхронный
        url: main_url + "/content/shop/returns/ajax/ajax_reasons.php",
        dataType: "json",//Тип возвращаемого значения
        data: obj,
        success: function (answer) {
            document.querySelector("#reasons").textContent = "";
            answer.data.forEach(el => {
                add_reason_block(el);
            });
        }
    });
}


function main() {
    show_statuses();
    show_reason();
}


//Запуск скрипта
main();

