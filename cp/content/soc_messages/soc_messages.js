let Version =  Math.random();
let SocialId = 0;
let ChatId = 0;
let SocialsApiUrl = '';
let SocialsLimits = {};
let Socials = [];
let ChatsOffset = 0;
let MessagesOffset = 0;
let getChatsQueryBlock = false;
let getMessagesQueryBlock = false;
let newMessagesTimeout = false;
let newMessagesInterval = 60000; // Каждые 60 сек обновлять новые сообщения в активном чате
let skipChats = {};
let chatsList = {};
function loadProfilePhoto(self, status)
{
    self = $(self);
    self.next().remove();
    if(status){
        self.removeClass('d-none');
    }
}
function correctText(str)
{
    return (str + '').replace(/[\\"]/g, '\\$&').replace(/\u0000/g, '\\0').replace(/<br>/g, "\n");
}
function correctDate(value)
{
    value = parseInt(value)||0;
    value *= 1000;
    let months = ["Января", "Февраля", "Марта", "Апреля", "Мая", "Июня", "Июля", "Августа", "Сентября", "Октября", "Ноября", "Декабря"];
    let currentDayStart = new Date;
    currentDayStart.setHours(0);
    currentDayStart.setMinutes(0);
    currentDayStart.setSeconds(0);
    currentDayStart.setMilliseconds(0);
    let date = new Date(value);
    let d = date.getDate();
    let m = date.getMonth();
    let y = date.getFullYear();
    let h = date.getHours();
    let i = date.getMinutes();
    h = h < 10 ? '0' + h : h;
    i = i < 10 ? '0' + i : i;
    if(value > currentDayStart.getTime()){
        return h + ':' + i;
    }
    return d + ' ' + months[m] + (y != currentDayStart.getFullYear() ? ' ' + y : '') + ', ' + h + ':' + i;
}
function socShowChat(socialId, chatId)
{
    $(".need-select-dialog").remove();
    $(".soc-chat").removeClass('active');
    let currentChat = $(".soc-chat[item-social='"+socialId+"'][item-chat='"+chatId+"']");
    currentChat.addClass('active');
    currentChat.find('.soc-chat-unseen').text('').addClass('d-none');
    $(".soc-messages-send").removeClass('d-none');
    SocialId = socialId;
    ChatId = chatId;
    let chat = chatsList[ChatId];
    let itemHtml = '';
    if(chat.item){
        let chatItem = chat.item;
        switch(chatItem.type){
            case 'item':
                let chatItemPhoto = '';
                if(chatItem.photo){
                    chatItemPhoto = `
                        <div>
                            <a href="${chatItem.photo}" data-fancybox="gallery-no-item-${ChatId}">
                                <img src="${chatItem.photo}">
                            </a>
                        </div>
                    `;
                }
                itemHtml = `
                    <div class="item">
                        <div class="item-content">
                            ${chatItemPhoto}
                            <div>
                                <a href="${chatItem.url}" target="_blank">${chatItem.title}</a>
                            </div>
                            ${chatItem.content}
                        </div>
                    </div>
                `;
            break;
        }
    }
    if(itemHtml){
        $(".soc-messages-chat-info").html('<div class="soc-chat-item-block">'+itemHtml+'</div>').removeClass('d-none');
    }
    socGetMessages(socialId, chatId, 0);
}
function socCountUnseenMessages()
{
    api('countUnseenMessages', function(res){
        $("#soc-unread-messages-count").text(res);        
    }, function(res){
        setTimeout(socCountUnseenMessages, 5000);
    });
}
function socGetChats(offset)
{
    offset = parseInt(offset)||0;
    let list = $(".soc-messages-chats");
    if(offset === 0){
        skipChats = {};
        ChatsOffset = 0;
        getChatsQueryBlock = false;
        list.html(`
            <div class="soc-chat-loader">
                <div class="glyphicon glyphicon-refresh soc-spinning"></div>
            </div>
        `);
    }
    let params = {};
    params.offset = offset;
    let skip = [];
    for(let id in skipChats){
        skip.push(id);
    }
    let search = $("#soc-messages-chats-search").val();
    if(search.length){
        params.search = search;
    }
    if(skip.length){
        params.skip = skip.join(",");
    }
    api('chatsGet', params, function(res){
        if(!res.length){
            if(offset === 0){
                list.html(`
                    <div class="soc-chat-empty">
                        Нет чатов
                    </div>
                `);
            }else{
                list.find('.soc-chat-loader').remove();
            }
        }else{
            let html = [];
            let hasSocialsChats = {};
            for(let i = 0; i < res.length; i++){
                let item = res[i];
                chatsList[item.id] = item;
                let lastMessage = item.lastMessage ? (item.lastMessage.text.length ? item.lastMessage.text : (item.lastMessage.attachments.length ? 'Вложение':'Сообщение')):'';
                let socialIcon = Socials[item.socialId].icon;
                if(item.socialIcon){
                    socialIcon = item.socialIcon;
                }
                let socialTitle = item.socialTitle ? ' title="'+item.socialTitle+'"' : '';
                html.push(`
                    <div class="soc-chat" item-social="`+item.socialId+`" item-chat="`+item.id+`">
                        <div class="soc-chat-photo">
                            <div class="soc-chat-photo-img">
                                <img class="d-none" src="`+(item.peer && item.peer.photo.length ? item.peer.photo : '')+`" onload="loadProfilePhoto(this, true)" onerror="loadProfilePhoto(this, false)">
                                <div class="glyphicon glyphicon-refresh soc-spinning"></div>
                            </div>
                            <div class="soc-chat-unseen`+(item.unseen > 0 ? '' : ' d-none')+`">`+item.unseen+`</div>
                            <div class="soc-chat-social"`+socialTitle+`>
                                <img src="/cp/content/soc_messages/img/`+socialIcon+`?v=`+Version+`">
                            </div>
                        </div>
                        <div class="soc-chat-info">
                            <div class="soc-chat-name"><span chat-link="`+(item.peer ? item.peer.link : '')+`" title="`+(item.peer ? item.peer.name : 'Пользователь')+`">`+(item.peer ? item.peer.name : 'Пользователь')+`</span></div>
                            <div class="soc-chat-last-message" title="`+correctText(lastMessage)+`">`+lastMessage+`</div>
                        </div>
                    </div>
                `);
                if(hasSocialsChats[item.socialId] === undefined){
                    hasSocialsChats[item.socialId] = 0;
                }
                hasSocialsChats[item.socialId] += 1;
            }
            for(let id in Socials){
                if(hasSocialsChats[id] === undefined || hasSocialsChats[id] < SocialsLimits.chats){
                    skipChats[id] = true;
                }
            }
            ChatsOffset += SocialsLimits.chats;
            html = html.join("");
            if(offset===0){
                list.html(html);
            }else{
                list.find('.soc-chat-loader').remove();
                list.append(html);
            }
            list.find('.soc-chat-name span').not('.init-events').addClass('init-events').click(function(e){
                e.stopPropagation();
                let self = $(this);
                let link = self.attr('chat-link');
                if(link.length){
                    window.open(link, '_blank');
                }
            });
            list.find('.soc-chat').not('.init-events').addClass('init-events').click(function(){
                let self = $(this);
                socShowChat(self.attr('item-social'), self.attr('item-chat'));
            });
            getChatsQueryBlock = false;
        }
    }, function(res){
        getChatsQueryBlock = false;
    });
}
function renderMessage(item, forward)
{
    forward = forward === true;
    let message = item.text.length ? '<div class="soc-message-text">' + item.text + '</div>' : '';
    if(item.attachments.length){
        let attachmentsHtml = [];
        for(let i = 0; i < item.attachments.length; i++){
            let attachment = item.attachments[i];
            attachment.size_ex = fileSize(attachment.size);
            let attachmentHtml = '';
            switch(attachment.type){
                case 'link':
                    attachmentHtml += `
                        <a href="${attachment.url}" target="_blank">
                            <span class="soc-attachment-link">${attachment.title}</span>
                        </a>
                    `;
                break;
                case 'video':
                    attachmentHtml += `
                        <iframe src="${attachment.url}" width="400" height="300"></iframe>
                    `;
                break;
                case 'audio':
                    if(attachment.ext == 'mp3'){
                        let types = {
                            mp3: "audio/mpeg",
                            ogg: "audio/ogg"
                        };
                        let urls = [];
                        let sources = [];
                        if(typeof(attachment.url) == "string"){
                            urls.push(attachment.url);
                        }else{
                            urls = attachment.url;
                        }
                        for(let id in urls){
                            let url = urls[id];
                            for(let type in types){
                                if(url.indexOf('.'+type) !== -1){
                                    sources.push('<source src="'+url+'" type="'+types[type]+'"/>');
                                }
                            }
                        }
                        attachmentHtml += `
                            <audio controls>
                                `+sources.join("")+`
                            </audio>
                        `;
                    }else{
                        attachmentHtml += `
                            <video width="400" class="audio" item-src="${attachment.url}" controls="false"></video>
                        `;
                    }
                break;
                case 'photo':
                    attachmentHtml += `
                        <a href="${attachment.url}" data-fancybox="gallery-chat-message-${ChatId}-${item.id}-${i}">
                            <div class="soc-attachment-photo">
                                <div class="glyphicon glyphicon-refresh soc-spinning"></div>
                                <img class="d-none" src="${attachment.url}" onload="attachmentImageLoad(this)" onerror="attachmentImageError(this)">
                            </div>
                        </a>
                    `;
                break;
                case 'document':
                    attachmentHtml += `
                        <a href="${attachment.url}" target="_blank">
                            <div class="soc-attachment-document">
                                <div class="soc-attachment-document-ext">${attachment.ext}</div>
                                <div class="soc-attachment-document-name">
                                    <span>${attachment.title}</span>
                                    <br>
                                    <span>${attachment.size_ex}</span>
                                </div>
                            </div>
                        </a>
                    `;
                break;
                case 'other':
                    switch(attachment.ext){
                        case 'market':
                            attachmentHtml += `
                                <a href="${attachment.url}" target="_blank">
                                    <div class="soc-attachment-market">
                                        <div class="soc-attachment-market-name">`+attachment.title+`</div>
                                        `+(attachment.preview ? `
                                        <div class="soc-attachment-market-photo">
                                            <div class="glyphicon glyphicon-refresh soc-spinning"></div>
                                            <a href="${attachment.preview}" data-fancybox="gallery-chat-message-${ChatId}-${item.id}-${i}">
                                                <img class="d-none" src="`+attachment.preview+`" onload="attachmentImageLoad(this)" onerror="attachmentImageError(this)">
                                            </a>
                                        </div>` : ``)+`
                                    </div>
                                </a>
                            `;
                        break;
                        case 'forward':
                            let forwardMessages = [];
                            for(let k = 0; k < attachment.data.length; k++){
                                const forwardMessage = attachment.data[k];
                                forwardMessages.push(renderMessage(forwardMessage, true));
                            }
                            attachmentHtml += `
                                <div class="soc-message-forward-messages">
                                `+forwardMessages.join("")+`
                                </div>
                            `;
                        break;
                        case 'sticker':
                            if(attachment.url == attachment.ext){
                                attachmentHtml += `<div class="soc-message-sticker">Стикер недоступен</div>`;
                            }else{
                                attachmentHtml += `
                                    <div class="soc-message-sticker">
                                        <div class="glyphicon glyphicon-refresh soc-spinning"></div>
                                        <a href="${attachment.preview}" data-fancybox="gallery-chat-message-${ChatId}-${item.id}-${i}">
                                            <img class="d-none" src="`+attachment.preview+`" onload="attachmentImageLoad(this)" onerror="attachmentImageError(this)">
                                        </a>
                                    </div>
                                `;
                            }
                        break;
                        case 'sticker_animation':
                            if(attachment.url == attachment.ext){
                                attachmentHtml += `<div class="soc-message-sticker-animation">Стикер недоступен</div>`;
                            }else{
                                attachmentHtml += `
                                    <div class="soc-message-sticker-animation" item-src="`+attachment.url+`"></div>
                                `;
                            }
                        break;
                        case 'story':
                            attachmentHtml += `
                                <a href="${attachment.url}" target="_blank">
                                    <div class="soc-attachment-story">
                                        <div class="soc-attachment-story-name">`+attachment.title+`</div>
                                        `+(attachment.preview ? `
                                        <div class="soc-attachment-story-photo">
                                            <div class="glyphicon glyphicon-refresh soc-spinning"></div>
                                            <img class="d-none" src="`+attachment.preview+`" onload="attachmentImageLoad(this)" onerror="attachmentImageError(this)">
                                        </div>` : ``)+`
                                    </div>
                                </a>
                            `;
                        break;
                        default:
                            attachmentHtml += `
                                <a href="${attachment.url}" target="_blank">${attachment.title}</a>
                            `;
                    }
                break;
            }
            attachmentsHtml.push('<div class="soc-message-attachment soc-attachment-type-'+attachment.type+'">'+attachmentHtml+'</div>');
        }
        if(attachmentsHtml.length){
            message += '<div class="soc-message-attachments">' + attachmentsHtml.join("") + "</div>";
        }
    }
    return `
        <div class="soc-message`+(item.type ? ' self' : '')+`" `+(forward ? '' : 'item-message="'+item.id+'"')+`">
            <div class="soc-message-content">`+message+`</div>
            <div class="soc-message-date">`+correctDate(item.date)+`</div>
        </div>
    `;
}
function socGetMessages(socialId, chatId, offset)
{
    offset = parseInt(offset)||0;
    let list = $(".soc-messages-list");
    if(offset === 0){
        MessagesOffset = 0;
        getMessagesQueryBlock = false;
        list.html(`
            <div class="soc-message-loader">
                <div class="glyphicon glyphicon-refresh soc-spinning"></div>
            </div>
        `);
    }
    let params = {
        social_id: socialId,
        chat_id: chatId
    };
    params.offset = offset;
    api('messagesGet', params, function(res){
        if(!res.length){
            if(offset === 0){
                list.html(`
                    <div class="soc-message-empty">
                        Нет сообщений
                    </div>
                `);
            }else{
                list.find('.soc-message-loader').remove();
            }
        }else{
            let html = [];
            for(let i = 0; i < res.length; i++){
                let item = res[i];
                html.push(renderMessage(item));
            }
            html = html.reverse().join("");
            MessagesOffset += SocialsLimits.messages;
            if(offset===0){
                socCountUnseenMessages();
                list.css('opacity', 0).html(html);
                $(document).ready(function(){
                    setTimeout(function(){
                        list.scrollTop(list.prop('scrollHeight') - list.prop('offsetHeight'));
                        setTimeout(function(){
                            list.css('opacity', 1);
                        }, 100);
                    }, 100);
                });
            }else{
                list.find('.soc-message-loader').remove();
                list.prepend(html);
            }
            list.find('.audio').not('.audio-init').addClass('audio-init').each(function(i, e){
                if(Hls.isSupported()){
                    let video = e;
                    let hls = new Hls();
                    hls.loadSource($(e).attr('item-src'));
                    hls.attachMedia(video);
                    hls.on(Hls.Events.MANIFEST_PARSED,function(){});
                }
            });
            list.find('.soc-message-sticker-animation').not('.sticker-init').addClass('sticker-init').each(function(i, e){
                let el = $(e);
                lottie.loadAnimation({
                    container: e,
                    renderer: 'svg',
                    loop: true,
                    autoplay: true,
                    path: el.attr('item-src')
                });
            });
            getMessagesQueryBlock = false;
            socGetNewMessages();
        }
    }, function(res){
        getMessagesQueryBlock = false;
    });
}
function fileSize(v, rt)
{
    let rv = v < 1024 * 1024 ? v / 1024 : v / 1024 / 1024;
    let t = v < 1024 * 1024 ? 'Кб' : 'Мб';
    return (Math.round(rv * 100) / 100).toFixed(2) + (rt === false ? '' : ' ' + t);
}
function checkCanSend()
{
    if($("#message-add-attachments-list .message-add-attachment").length == 0 && $("#soc-messages-message-text").val().trim().length == 0){
        $("#message-send-btn").attr('disabled', '');
    }else{
        $("#message-send-btn").removeAttr('disabled');
    }
}
function socGetNewMessages(callback)
{
    if(newMessagesTimeout){
        clearTimeout(newMessagesTimeout);
    }
    let params = {
        social_id: SocialId,
        chat_id: ChatId,
        offset: 0
    };
    api('messagesGet', params, function(res){
        let newMessages = [];
        let lastMessage = '';
        for(let i = 0; i < res.length; i++){
            let item = res[i];
            if(lastMessage === ''){
                lastMessage = item.text ? (item.text.length ? item.text : (item.attachments.length ? 'Вложение':'Сообщение')):'';
            }
            if($(".soc-messages-list .soc-message[item-message='"+item.id+"']").length === 0){
                newMessages.push(renderMessage(item));
            }
            
        }
        if(newMessages.length){
            $(".soc-messages-list").append(newMessages.reverse().join(""));
            if(lastMessage != ''){
                $(".soc-messages-chats .soc-chat[item-chat='"+ChatId+"']").find('.soc-chat-last-message').attr('title', lastMessage).text(lastMessage);
            }
        }
        if(typeof(callback) == "function"){
            callback.call(null);
        }
        newMessagesTimeout = setTimeout(function(){
            socGetNewMessages(callback);
        }, newMessagesInterval);
    }, function(res){
        newMessagesTimeout = setTimeout(function(){
            socGetNewMessages(callback);
        }, 5000);
    });
}
function isMobile()
{
    const mediaQuery = window.matchMedia('(max-width: 767px)');
    return mediaQuery.matches ? true : false;
}
function attachmentImageLoad(self)
{
    let el = $(self);
    el.prev().addClass('d-none');
    el.removeClass('d-none');
}
function attachmentImageError(self)
{
    let el = $(self);
    el.prev().addClass('d-none');
    el.addClass('d-none');
}
$(document).ready(function(){
    $(".soc-messages-chats").scroll(function(){
        let self = $(this);
        let checkEnd = isMobile() ? self.prop('scrollLeft') + self.prop('offsetWidth') == self.prop('scrollWidth') : self.prop('scrollTop') + self.prop('offsetHeight') == self.prop('scrollHeight');
        if(checkEnd  && !getChatsQueryBlock){
            getChatsQueryBlock = true;
            $(".soc-messages-chats").append(`
                <div class="soc-chat-loader">
                    <div class="glyphicon glyphicon-refresh soc-spinning"></div>
                </div>
            `);
            $(document).ready(function(){
                self.scrollTop(self.prop('scrollHeight') - self.prop('offsetHeight'));
                socGetChats(ChatsOffset);                
            });
        }
    });
    $(".soc-messages-list").scroll(function(){
        let self = $(this);
        if(self.prop('scrollTop') == 0 && !getMessagesQueryBlock){
            getMessagesQueryBlock = true;
            $(".soc-messages-list").prepend(`
                <div class="soc-message-loader">
                    <div class="glyphicon glyphicon-refresh soc-spinning"></div>
                </div>
            `);
            $(document).ready(function(){
                self.scrollTop(1);
                socGetMessages(SocialId, ChatId, MessagesOffset);                
            });
        }
    });
    $("#message-add-attachment-btn").click(function(){
        $("#message-add-attachment-file").val("").click();
    });
    $("#message-send-btn").click(function(){
        let self = $(this);
        self.attr('disabled', '');
        self.find('.text').text('Отправляем');
        self.find('.soc-spinning').removeClass('d-none');
        let attachments = [];
        $("#message-add-attachments-list .message-add-attachment-name").each(function(i, e){
            attachments.push($(e).text());
        });
        api('messageSend', {
            social_id: SocialId,
            chat_id: ChatId,
            text: $("#soc-messages-message-text").val(),
            attachments: attachments.join("|")
        }, function(res){
            $("#soc-messages-message-text").val("");
            $("#message-add-attachments-list").html("");
            checkCanSend();
            self.find('.text').text('Отправить');
            self.find('.soc-spinning').addClass('d-none');
            socGetNewMessages(function(){
                let socMessagesList = $(".soc-messages-list");
                socMessagesList.prop('scrollTop', socMessagesList.prop('scrollHeight') - socMessagesList.prop('offsetHeight'));
            });
        },function(res){
            self.find('.text').text('Отправить');
            self.find('.soc-spinning').addClass('d-none');
            self.removeAttr('disabled');
            alert(res);
        });
    });
    $("#soc-messages-message-text").on('keydown', function(e){
        if(e.keyCode == 13 && !e.shiftKey){
            e.stopPropagation();
            e.preventDefault();
            $("#message-send-btn").not('[disabled]').click();
            return false;
        }
    }).on('input', function(){
        let self = $(this);
        self.css('height', '5px');
        self.css('height', self.prop('scrollHeight'));
        checkCanSend();
    });
    $("#message-add-attachment-file").change(function(){
        let file = this.files[0];
        let maxFiles = 10;
        let size = `0 из `+fileSize(file.size);
        $("#message-add-attachments-list").append(`
            <div class="message-add-attachment">
                <div class="message-add-attachment-name" title="`+file.name+`">`+file.name+`</div>
                <div class="message-add-attachment-size" title="`+size+`">`+size+`</div>
                <i class="message-add-attachment-remove fas fa-times d-none"></i>
            </div>
        `);
        $("#message-add-attachments-list .message-add-attachment-name").off('click').click(function(){
            let self = $(this);
            let src = self.attr('item-src');
            if(src !== undefined && typeof(src) == "string" && src.length){
                window.open(src, '_blank');
            }
        });
        $("#message-add-attachments-list .message-add-attachment-remove").off('click').click(function(){
            let self = $(this);
            self.parent().remove();
            $("#message-add-attachment-btn").removeClass('d-none');
            checkCanSend();
            api('uploadRemove', {
                name: self.prev().prev().text()
            });
        });
        let length = $("#message-add-attachments-list .message-add-attachment").length;
        if(length == maxFiles){
            $("#message-add-attachment-btn").addClass('d-none');
        }
        checkCanSend();
        let el = $($("#message-add-attachments-list .message-add-attachment")[length-1]);
        let form = new FormData;
        form.append('api', 'upload');
        form.append('file', file);
        $.ajax({
            xhr: function(){
              let xhr = new window.XMLHttpRequest();
              xhr.upload.addEventListener("progress", function(evt){
                if(evt.lengthComputable){
                    let val = fileSize(evt.loaded, false)+ ' из ' + fileSize(evt.total);
                    el.find('.message-add-attachment-size').text(val).attr('title', val);
                }
              }, false);
              return xhr;
            },
            type: "POST",
            url: SocialsApiUrl,
            cache: false,
			contentType: false,
			processData: false,
			data: form,
			dataType : 'json',
            success: function(res){
                if(res.success !== undefined){
                    el.find('.message-add-attachment-size').attr('title', res.success.size).text(res.success.size);
                    el.find('.message-add-attachment-name').addClass('active').attr('item-src', SocialsApiUrl + '?api=uploadGet&name=' + res.success.name);
                }else if(res.error !== undefined){
                    el.find('.message-add-attachment-size').attr('title', res.error).html('<span style="color: #dc3545;">'+res.error+'</span>');
                }else{
                    el.find('.message-add-attachment-size').attr('title', 'Ошибка загрузки').html('<span style="color: #dc3545;">Ошибка загрузки</span>');
                }
                el.find('.message-add-attachment-remove').removeClass('d-none');
            },
            error: function(){
                el.find('.message-add-attachment-size').attr('title', 'Ошибка загрузки').html('<span style="color: #dc3545;">Ошибка загрузки</span>');
                el.find('.message-add-attachment-remove').removeClass('d-none');
            }
        });
    });
    let searchTimer = false;
    $("#soc-messages-chats-search").on('input', function(){
        let self = $(this);
        if(searchTimer){
            clearTimeout(searchTimer);
        }
        searchTimer = setTimeout(function(res){
            if(!getChatsQueryBlock){
                socGetChats(0);
            }
        }, 500);
    });
    socGetChats(0);
});