<script>
function showIconsList() {
    document.querySelector('.icons_list').classList.remove('hidden');
}

function hideIconList() {
    document.querySelector('.icons_list').classList.add('hidden');
}

function changeCurrentImage(src) {
    document.querySelector('#post_image_input').value = src;
    document.querySelector('.post_image').src = src;
}
//Проверяем данные и возвращаем объект для сохранения/текст ошибки
function validateFields(action, extra_fields = null) {
    let result = {
        success: true,
        error_message: '',
        body: {
            caption: getElement('#post_caption').value,
            image: getElement('#post_image_input').value,
            action: action
        }
    };

    if (result.body.caption.trim() == '') {
        result.success = false;
        result.error_message = 'Укажите название.';
        return result;
    }

    if (result.body.image == '/content/files/images/no_image.png') {
        result.success = false;
        result.error_message = 'Выберите изображение';
        return result;
    }


    if (extra_fields != null)
        result.body = Object.assign(result.body, extra_fields);

    return result;
}


function createPost() {
    let result = validateFields('create_post');
    if (!result.success) {
        alert(result.error_message);
        return false;
    }

    postAjax(result.body, false, 'reloadPageAfterCreate');
    //
}

function updatePost(id) {
    let result = validateFields('update_post', {id: id});
    if (!result.success) {
        alert(result.error_message);
        return false;
    }

    postAjax(result.body, true);
    //
}

function reloadPageAfterCreate(answer) {
    if (answer.id > 0)
        location = location.origin + location.pathname + '?id=' + answer.id;
}

function redirectAfterDelete() {
    location = '<?=$DP_Config->domain_path . $DP_Config->backend_dir . '/car_service/service_posts'?>';
}

function deletePostById(id) {
    if (confirm('Подтвердите удаление.'))
        postAjax({
            action: 'delete_by_id',
            id: id
        }, false, 'redirectAfterDelete');
}
</script>