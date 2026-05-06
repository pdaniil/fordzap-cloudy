<?php
namespace SocialsChats\VKGroup;
use \SocialsChats\Chat;
use \SocialsChats\Peer;
use \SocialsChats\Message;
use \SocialsChats\Attachment;
use \SocialsChats\Handle;
class Social extends \SocialsChats\Social
{
    public $icon = 'vk-group.png';
    public $peerId;
    private $token;
    private $url = 'https://api.vk.com/method/';
    private $version = '5.199';
    protected $extensions;
    public function __construct($options)
    {
        parent::__construct($options);
        $this->extensions = array(
            'png' => Attachment::PHOTO,
            'jpg' => Attachment::PHOTO,
            'jpeg' => Attachment::PHOTO,
            'csv' => Attachment::DOCUMENT,
            'txt' => Attachment::DOCUMENT,
            'xls' => Attachment::DOCUMENT,
            'xlsx' => Attachment::DOCUMENT,
            'doc' => Attachment::DOCUMENT,
            'docx' => Attachment::DOCUMENT,
            'mp3' => Attachment::DOCUMENT,
            'mp4' => Attachment::VIDEO,
            'avi' => Attachment::VIDEO,
            'mpeg' => Attachment::VIDEO,
            'mov' => Attachment::VIDEO,
            'pdf' => Attachment::DOCUMENT
        );
        $this->peerId = $this->option('userId');
        $this->token = $this->option('token');
    }
    public function api($method, array $params = array(), $returnRequest = false)
    {
        $params['access_token'] = $this->token;
        $params['v'] = $this->version;
        $ch = curl_init($this->url . $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        if(count($params)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        if($returnRequest){
            return $ch;
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return @json_decode($result, true);
    }
    private function attachments(array $attachments, &$profiles)
    {
        $list = [];
        foreach($attachments as $attachment){
            $type = Attachment::OTHER;
            $title = '';
            $size = 0;
            $ext = '';
            $date = 0;
            $url = '';
            $preview = '';
            switch($attachment['type']){
                case 'link':
                    $type = Attachment::LINK;
                    $link = $attachment['link'];
                    $url = $link['url'];
                    $title = mb_strlen($link['title']) ? $link['title'] : (mb_strlen($link['caption'] ? $link['caption'] : 'Ссылка'));
                    if(isset($link['photo'])){
                        $photo = $link['photo'];
                        if(isset($photo['orig_photo'])){
                            $preview = $photo['orig_photo']['url'];
                        }elseif(count($photo['sizes'])){
                            $preview = $photo['sizes'][0]['url'];
                        }
                    }
                break;
                case 'audio':
                    $type = Attachment::AUDIO;
                    $audio = $attachment['audio'];
                    $title = $audio['artist'] . ' - ' . $audio['title'];
                    $url = $audio['url'];
                    $size = $audio['duration'];
                    $date = $audio['date'];
                break;
                case 'video':
                    $type = Attachment::VIDEO;
                    $video = $attachment['video'];
                    if(isset($video['player'])){
                        $url = $video['player'];
                        $title = $video['title'];
                        $size = $video['duration'];
                        $date = $video['date'];
                        $preview = isset($video['image']) && count($video['image']) ? $video['image'][count($video['image'])-1]['url'] : '';
                    }else{
                        $title = 'Видео недоступно';
                    }
                break;
                case 'doc':
                    $type = Attachment::DOCUMENT;
                    $doc = $attachment['doc'];
                    $url = $doc['url'];
                    $title = $doc['title'];
                    $size = $doc['size'];
                    $ext = $doc['ext'];
                    $date = $doc['date'];
                break;
                case 'photo':
                    $type = Attachment::PHOTO;
                    $photo = $attachment['photo'];
                    if(isset($photo['orig_photo'])){
                        $url = $photo['orig_photo']['url'];
                    }elseif(count($photo['sizes'])){
                        $url = $photo['sizes'][0]['url'];
                    }
                    if(!mb_strlen($url)){
                        continue 2;
                    }
                    $title = $photo['text'];
                    $ext = 'jpg';
                    $date = $photo['date'];
                break;
                case 'wall':
                    $type = Attachment::OTHER;
                    $wall = $attachment['wall'];
                    $title = 'Прикреплена запись на стене';
                    $date = $wall['date'];
                    $fromId = $wall['owner_id'];
                    $url = 'https://vk.com/'. ($fromId < 0 ? 'club' . abs($fromId) : 'id' . $fromId) . '?w=wall'. $fromId . '_' . $attachment['wall']['id'];
                break;
                case 'audio_message':
                    $type = Attachment::AUDIO;
                    $audioMessage = $attachment['audio_message'];
                    $ext = 'mp3';
                    $url = [];
                    $url[] = $audioMessage['link_mp3'];
                    $url[] = $audioMessage['link_ogg'];
                    $size = $audioMessage['duration'];
                break;
                case 'market':
                    $type = Attachment::OTHER;
                    $market = $attachment['market'];
                    $ext = 'market';
                    $date = isset($market['date']) ? $market['date'] : 0;
                    $title = $market['title'] . (isset($market['description']) && mb_strlen($market['description']) ? "<br><br>Описание:<br>" . str_replace(array("\r\n","\n"), "<br>", $market['description']) : '');
                    $url = $market['market_url'];
                    $preview = isset($market['thumb_photo']) && mb_strlen($market['thumb_photo']) ? $market['thumb_photo'] : '';
                break;
                case 'sticker':
                    $type = Attachment::OTHER;
                    $sticker = $attachment['sticker'];
                    $title = 'Стикер недоступен';
                    $ext = 'sticker';
                    if(isset($sticker['images']) && count($sticker['images'])){
                        $url = $sticker['images'][0]['url'];
                    }elseif(isset($sticker['images_with_background']) && count($sticker['images_with_background'])){
                        $url = $sticker['images_with_background'][0]['url'];
                    }elseif(isset($sticker['animation_url'])){
                        $url = $sticker['animation_url'];
                        $ext = 'sticker_animation';
                    }
                    if(mb_strlen($url)){
                        $title = '';
                    }else{
                        $url = $ext;
                    }
                break;
                case 'story':
                    $type = Attachment::OTHER;
                    $story = $attachment['story'];
                    $title = 'История недоступна';
                    $url = 'https://vk.com/feed?section=photos&w=story' . $story['owner_id'] . '_' . $story['id'];
                    if(isset($story['is_expired']) && $story['is_expired']){
                        $url = '';
                    }else{
                        if(isset($story['can_see']) && $story['can_see']){
                            switch($story['type']){
                                case 'photo':
                                    if(isset($story['photo']) && count($story['photo']['sizes'])){
                                        $preview = array_pop($story['photo']['sizes'])['url'];
                                    }
                                break;
                                case 'video':
                                    if(isset($story['video']) && isset($story['video']['image']) && count($story['video']['image'])){
                                        $preview = array_pop($story['video']['image'])['url'];
                                    }
                                break;
                            }
                            $peer = isset($profiles[$story['owner_id']]) ? $profiles[$story['owner_id']] : false;
                            $title = 'История' . ($peer ? ' от '. $peer->name : '');
                        }
                    }
                    $date = isset($story['date']) ? $story['date'] : 0;
                    $ext = 'story';
                break;
                case 'geo':
                    $type = attachment::LINK;
                    $title = 'Показать точку на карте';
                    $geo = $attachment['geo'];
                    $coordinates = isset($geo['coordinates']) ? $geo['coordinates']['latitude'].'%2C' . $geo['coordinates']['longitude'] : '';
                    if($coordinates){
                        $url = 'https://yandex.ru/maps/?mode=search&sll='.$coordinates. '&text='. $coordinates . '&z=15';
                    }
                break;
                default:
                    continue 2;
            }
            if((is_array($url) && count($url)) || mb_strlen($url)){
                $list[] = new Attachment($type, $title, $size, $ext, $date, $url, $preview);
            }
        }
        return $list;
    }
    private function message($chatId, array $message, &$profiles){
        $attachments = $this->attachments($message['attachments'], $profiles);
        if(isset($message['geo'])){
            foreach($this->attachments(array(array(
                'type' => 'geo',
                'geo' => $message['geo']
            )), $profiles) as $attachment){
                $attachments[] = $attachment;
            }
        }
        $forwardMessages = array();
        if(isset($message['fwd_messages']) && count($message['fwd_messages'])){
            foreach($message['fwd_messages'] as $forwardMessage){
                $forwardMessagesAttachments = $this->attachments($forwardMessage['attachments'], $profiles);
                if(isset($message['geo'])){
                    foreach($this->attachments(array(array(
                        'type' => 'geo',
                        'geo' => $message['geo']
                    )), $profiles) as $forwardMessagesAttachment){
                        $forwardMessagesAttachments[] = $forwardMessagesAttachment;
                    }
                }
                if(isset($forwardMessage['id']) || isset($forwardMessage['conversation_message_id'])){
                    $userProfile = isset($profiles[$forwardMessage['from_id']]) ? $profiles[$forwardMessage['from_id']] : false;
                    $userName = 'Пользователь';
                    if($userProfile){
                        $userName = $userProfile->name;
                    }
                    $forwardMessages[] = new Message(isset($forwardMessage['id']) ? $forwardMessage['id'] : $forwardMessage['conversation_message_id'], $chatId, $forwardMessage['from_id'], $forwardMessage['date'], 'Переслано от ' . $userName . "<br>" . $forwardMessage['text'], $forwardMessagesAttachments);
                }
            }
        }
        if(count($forwardMessages)){
            $attachments[] = new Attachment(Attachment::OTHER, '', 0, 'forward', 0, 'forward', '', $forwardMessages);
        }
        return new Message($message['id'], $chatId, $message['from_id'], $message['date'], $message['text'], $attachments);
    }
    private function getProfiles(&$profiles, $data)
    {
        if(isset($data['response'])){
            if(isset($data['response']['profiles'])){
                foreach($data['response']['profiles'] as $profile){
                    $profileName = array();
                    if(mb_strlen($profile['last_name'])){
                        $profileName[] = $profile['last_name'];
                    }
                    if(mb_strlen($profile['first_name'])){
                        $profileName[] = $profile['first_name'];
                    }
                    if(!count($profileName)){
                        $profileName[] = 'Пользователь';
                    }
                    $photo = isset($profile['photo']) ? $profile['photo'] : (isset($profile['photo_50']) ? $profile['photo_50'] : (isset($profile['photo_200']) ? $profile['photo_200'] : ''));
                    $profileId = $profile['id'];
                    if(!isset($profiles[$profileId])){
                        $profiles[$profileId] = new Peer($profile['id'], implode(" ", $profileName), $photo, 'https://vk.com/id' . $profile['id']);
                    }
                }
            }
            if(isset($data['response']['groups'])){
                foreach($data['response']['groups'] as $group){
                    $photo = isset($group['photo']) ? $group['photo'] : (isset($group['photo_50']) ? $group['photo_50'] : (isset($group['photo_200']) ? $group['photo_200'] : ''));
                    $groupId = -$group['id'];
                    if(!isset($profiles[$groupId])){
                        $profiles[$groupId] = new Peer($groupId, mb_strlen($group['name']) ? $group['name'] : 'Сообщество', $photo, 'https://vk.com/club' . $group['id']);
                    }
                }
            }
        }else{
            if(isset($data['peer'])){
                $peerId = $data['peer']['id'];
                if($data['peer']['type'] == 'chat'){
                    $chatSettings = $data['chat_settings'];
                    $photo = isset($chatSettings['photo']) ? (isset($chatSettings['photo']['photo']) ? $chatSettings['photo']['photo'] : (isset($chatSettings['photo']['photo_50']) ? $chatSettings['photo']['photo_50'] : (isset($chatSettings['photo']['photo_200']) ? $chatSettings['photo']['photo_200'] : ''))) : '';
                    if(!isset($profiles[$peerId])){
                        $profiles[$peerId] = new Peer($peerId, mb_strlen($chatSettings['title']) ? $chatSettings['title'] : 'Беседа', $photo, 'https://vk.com/im?sel=c' .($peerId-2000000000));
                    }   
                }
            }
        }
    }
    private function queryChats($method, $params)
    {
        return new Handle($this->api($method, $params, true), function($response){
            $chats = [];
            if(is_array($response) && isset($response['response'])){
                $profiles = [];
                if(isset($response['response']['profiles'])){
                    foreach($response['response']['profiles'] as $profile){
                        $profileName = array();
                        if(mb_strlen($profile['last_name'])){
                            $profileName[] = $profile['last_name'];
                        }
                        if(mb_strlen($profile['first_name'])){
                            $profileName[] = $profile['first_name'];
                        }
                        if(!count($profileName)){
                            $profileName[] = 'Пользователь';
                        }
                        $photo = isset($profile['photo']) ? $profile['photo'] : (isset($profile['photo_50']) ? $profile['photo_50'] : (isset($profile['photo_200']) ? $profile['photo_200'] : ''));
                        $profiles[$profile['id']] = new Peer($profile['id'], implode(" ", $profileName), $photo, 'https://vk.com/id' . $profile['id']);
                    }
                }
                if(isset($response['response']['groups'])){
                    foreach($response['response']['groups'] as $group){
                        $photo = isset($group['photo']) ? $group['photo'] : (isset($group['photo_50']) ? $group['photo_50'] : (isset($group['photo_200']) ? $group['photo_200'] : ''));
                        $profiles[-$group['id']] = new Peer(-$group['id'], mb_strlen($group['name']) ? $group['name'] : 'Сообщество', $photo, 'https://vk.com/club' . $group['id']);
                    }
                }
                foreach($response['response']['items'] as $item){
                    $vkConversation = isset($item['conversation']) ? $item['conversation'] : $item;
                    $peerId = $vkConversation['peer']['id'];
                    if(!isset($profiles[$peerId])){
                        if($vkConversation['peer']['type'] == 'chat'){
                            $chatSettings = $vkConversation['chat_settings'];
                            $photo = isset($chatSettings['photo']) ? (isset($chatSettings['photo']['photo']) ? $chatSettings['photo']['photo'] : (isset($chatSettings['photo']['photo_50']) ? $chatSettings['photo']['photo_50'] : (isset($chatSettings['photo']['photo_200']) ? $chatSettings['photo']['photo_200'] : ''))) : '';
                            $profiles[$peerId] = new Peer($peerId, mb_strlen($chatSettings['title']) ? $chatSettings['title'] : 'Беседа', $photo, 'https://vk.com/im?sel=c' .($peerId-2000000000));
                        }
                        if(!isset($profiles[$peerId])){
                            continue;
                        }
                    }
                    $peer = $profiles[$peerId];
                    $lastMessage = false;
                    if($vkConversation['last_conversation_message_id'] > 0 && isset($item['last_message'])){
                        $vkLastMessage = $item['last_message'];
                        $lastMessage = new Message($vkLastMessage['conversation_message_id'], $peer->id, $vkLastMessage['from_id'], $vkLastMessage['date'], $vkLastMessage['text'], $this->attachments($vkLastMessage['attachments'], $profiles));
                    }
                    $chats[] = new Chat($peer->id, $lastMessage ? $lastMessage->date : 0, isset($vkConversation['unread_count']) ? $vkConversation['unread_count'] : 0, $peer, $lastMessage);
                }
            }
            return $chats;
        });
    }
    public function getChats($offset = 0, $limit = 20)
    {
        return $this->queryChats('messages.getConversations', array(
            'filter' => 'all',
            'extended' => 1,
            'fields' => 'id,first_name,last_name,has_photo,photo_50,photo_200',
            'offset' => $offset,
            'count' => $limit
        ));
    }
    public function searchChats($search, $offset = 0, $limit = 20)
    {
        return $this->queryChats('messages.searchConversations', array(
            'q' => $search,
            'filter' => 'all',
            'extended' => 1,
            'fields' => 'id,first_name,last_name,has_photo,photo_50,photo_200',
            'offset' => $offset,
            'count' => $limit
        ));
    }
    public function getMessages($chatId, $offset = 0, $limit = 20)
    {
        $result = $this->api('messages.getHistory', [
            'peer_id' => $chatId,
            'offset' => $offset,
            'count' => $limit,
            'extended' => true
        ]);
        $list = array();
        if(is_array($result) && isset($result['response']) && isset($result['response']['items'])){
            $profiles = array();
            $this->getProfiles($profiles, $result);
            foreach($result['response']['items'] as $item){
                $this->getProfiles($profiles, $item);
                $list[] = $this->message($chatId, $item, $profiles);
            }
        }
        if($offset === 0){
            $this->api('messages.markAsRead', array(
                'peer_id' => $chatId,
                'mark_conversation_as_read' => true
            ));
        }
        return $list;
    }
    public function sendMessage($chatId, $text, array $attachments = array())
    {
        $params = array(
            'peer_id' => $chatId,
            'message' => $text,
            'random_id' => rand(10000000, 100000000)
        );
        $attachmentsList = array();
        if(count($attachments)){
            foreach($attachments as $attachment){
                switch($attachment->type){
                    case Attachment::PHOTO:
                        $server = $this->api('photos.getMessagesUploadServer', array(
                            'group_id' => abs($this->peerId)
                        ));
                        if(is_array($server) && isset($server['response']) && isset($server['response']['upload_url'])){
                            $serverData = $server['response'];
                            $ch = curl_init($serverData['upload_url']);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                'Content-Type: multipart/form-data; charset=utf-8'
                            ));
                            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                                'photo' => function_exists('curl_file_create') ? curl_file_create($attachment->url) : "@" . $attachment->url
                            ));
                            $uploadData = curl_exec($ch);
                            curl_close($ch);
                            $uploadData = json_decode($uploadData, true);
                            $uploadData['group_id'] = abs($this->peerId);
                            if(is_array($uploadData)){
                                $photoData = $this->api('photos.saveMessagesPhoto', $uploadData);
                                if(is_array($photoData) && isset($photoData['response']) && count($photoData['response'])){
                                    $attachmentsList[] = 'photo' . $photoData['response'][0]['owner_id'] . '_' . $photoData['response'][0]['id'];
                                }
                            }
                        }
                    break;
                    case Attachment::VIDEO:
                        $server = $this->api('video.save');
                        if(is_array($server) && isset($server['response']) && isset($server['response']['upload_url'])){
                            $serverData = $server['response'];
                            $ch = curl_init($serverData['upload_url']);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                                'video_file' => function_exists('curl_file_create') ? curl_file_create($attachment->url) : "@" . $attachment->url
                            ));
                            $uploadData = json_decode(curl_exec($ch), true);
                            if(is_array($uploadData) && isset($uploadData['video_id']) && isset($uploadData['owner_id'])){
                                $attachmentsList[] = 'video' . $uploadData['owner_id'] . '_' . $uploadData['video_id'];
                            }
                        }
                    break;
                    case Attachment::DOCUMENT:
                        $server = $this->api('docs.getMessagesUploadServer', array(
                            'peer_id' => $chatId,
                            'type' => 'doc'
                        ));
                        if(is_array($server) && isset($server['response']) && isset($server['response']['upload_url'])){
                            $serverData = $server['response'];
                            $ch = curl_init($serverData['upload_url']);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                                'file' => function_exists('curl_file_create') ? curl_file_create($attachment->url) : "@" . $attachment->url
                            ));
                            $uploadResult = curl_exec($ch);
                            $uploadData = json_decode($uploadResult, true);
                            if(is_array($uploadData) && isset($uploadData['file'])){
                                $uploadData['title'] = $attachment->title;
                                $docData = $this->api('docs.save', $uploadData);
                                if(is_array($docData) && isset($docData['response']) && count($docData['response'])){
                                    $attachmentsList[] = 'doc' . $docData['response']['doc']['owner_id'] . '_' . $docData['response']['doc']['id'];
                                }
                            }
                        }
                    break;
                }
            }
        }
        if(count($attachmentsList)){
            $params['attachment'] = implode(",", $attachmentsList);
        }
        $result = $this->api('messages.send',  $params);
        if(is_array($result) && isset($result['response'])){
            $messageQuery = $this->api('messages.getById', array(
                'message_ids' => $result['response'],
                'extended' => true
            ));
            if(is_array($messageQuery) && isset($messageQuery['response']) && count($messageQuery['response']['items'])){
                $message = $messageQuery['response']['items'][0];
                if(is_array($message)){
                    $profiles = [];
                    $this->getProfiles($profiles, $messageQuery);
                    return $this->message($chatId, $message, $profiles);
                }
            }
        }
        return false;
    }
    public function getCountUnseenMessages()
    {
        return new Handle($this->api('messages.getConversations', array(
            'count' => 0
        ), true), function($response){
            return is_array($response) && isset($response['response']) && isset($response['response']['unread_count']) ? (int)$response['response']['unread_count'] : 0;
        });
    }
}