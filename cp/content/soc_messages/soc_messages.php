<!-- Подключение стилей Fancybox -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css">
<!-- Подключение скриптов Fancybox -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
<div class="panel-body">
    <div class="soc-messages-container">
        <div class="soc-messages-chats-wrapper">
            <div class="soc-messages-chats-search">
                <input id="soc-messages-chats-search" placeholder="Напишите, чтобы найти чат...">
            </div>
            <div class="soc-messages-chats">
                <div class="soc-chat-loader">
                    <div class="glyphicon glyphicon-refresh soc-spinning"></div>
                </div>
            </div>
        </div>
        <div class="soc-messages-messages">
            <div class="soc-messages-chat-info d-none"></div>
            <div class="soc-messages-list">
                <div class="need-select-dialog">
                    Выберите диалог для начала общения
                </div>
            </div>
            <div class="soc-messages-send d-none">
                <div class="soc-messages-send-input">    
                    <textarea id="soc-messages-message-text" placeholder="Введите сообщение" type="text"></textarea>
                    <div id="message-add-attachments-list"></div>
                    <i id="message-add-attachment-btn" class="fas fa-file-upload"></i>
                    <input id="message-add-attachment-file" type="file" class="d-none">
                </div>
                <div class="soc-messages-send-control">
                    <button id="message-send-btn" class="btn btn-success" disabled>
                        <span class="glyphicon glyphicon-refresh soc-spinning d-none"></span>
                        <span class="text">Отправить</span>
                    </button>
                    <span style="color: gray;">или <b>Enter</b></span>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="soc-chat-item-content-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <!-- заголовок -->
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Объявление</h4>
            </div>
            <!-- содержимое -->
            <div class="modal-body" id="soc-chat-item-content"></div>
            <!-- подвал -->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
<script>
    var api_hash = '<?php echo hash('sha256', $key); ?>';
</script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/" . $DP_Config->backend_dir . '/content/soc_messages/api.php';
?>
<script>
    SocialsApiUrl = '<?=$DP_Config->domain_path . $DP_Config->backend_dir . "/content/soc_messages/api.php"?>';
    SocialsLimits = <?=json_encode($socialsChatsApi->apiLimitsGet())?>;
    Socials = <?=json_encode($socialsChatsApi->apiSocialsGet())?>;
</script>