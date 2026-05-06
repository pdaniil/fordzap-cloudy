<?php
namespace SocialsChats\Avito;
use \SocialsChats\Chat;
use \SocialsChats\Peer;
use \SocialsChats\Message;
use \SocialsChats\Attachment;
use \SocialsChats\Handle;
class Social extends \SocialsChats\Social
{
    public $icon = 'avito-t.png';
    public $clientId;
    public $clientSecret;
    public $peerId;
    private $url = 'https://api.avito.ru/';
    protected $extensions;
    public $noAvatar;
    public $apiUrl;
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
        $this->clientId = $this->option('clientId');
        $this->clientSecret = $this->option('clientSecret');
        $this->peerId = $this->option('userId');
        $this->noAvatar = $this->option('noAvatar');
        $this->apiUrl = $this->option('api_url');
    }
    public function getToken()
    {
        $tokenFile = sys_get_temp_dir() . '/avito.token';
        $token = '';
        if(!file_exists($tokenFile) || (time() - filemtime($tokenFile) > 24 * 3600)){
            $ch = curl_init($this->url . 'token/');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            )));
            $authResult = @json_decode(curl_exec($ch), true);
            curl_close($ch);
            if(is_array($authResult) && isset($authResult['access_token'])){
                file_put_contents($tokenFile, $authResult['access_token']);
                $token = $authResult['access_token'];
            }
        }else{
            $token = file_get_contents($tokenFile);
        }
        return $token;
    }
    public function api($method, array $params = array(), $returnRequest = false, $requestMethod = true)
    {
        $requestMethod = (bool)$requestMethod;
        $paramsString = http_build_query($params);
        $ch = curl_init($this->url . $method . (!$requestMethod && count($params) ? '?' . $paramsString : ''));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, $requestMethod);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . $this->getToken()
        ));
        if(count($params) && $requestMethod){
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
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
                case 'image':
                    $type = Attachment::PHOTO;
                    $sizes = isset($attachment['content']) && isset($attachment['content']['image']) && isset($attachment['content']['image']['sizes']) ? $attachment['content']['image']['sizes'] : array();
                    $lastSizeLength = 0;
                    foreach($sizes as $size => $sizeUrl){
                        $sizeLength = mb_strlen($size);
                        if($sizeLength > $lastSizeLength){
                            $url = $sizeUrl;
                            $lastSizeLength = $sizeLength;
                        }
                    }
                    $title = '';
                    $ext = 'webp';
                    $date = isset($attachment['created']) ? $attachment['created'] : (isset($attachment['delivered']) ? $attachment['delivered'] : 0);
                break;
                case 'item':
                    $type = Attachment::OTHER;
                    $ext = 'market';
                    $item = $attachment['content']['item'];
                    $titles = array();
                    if(isset($item['title'])){
                        $titles[] = $item['title'];
                    }
                    if(isset($item['price_string'])){
                        $titles[] = 'Цена: ' . $item['price_string'];
                    }
                    $title = implode(" - ", $titles);
                    $preview = isset($item['image_url']) && $item['image_url'] ? $item['image_url'] : '';
                break;
                case 'voice':
                    $type = Attachment::AUDIO;
                    $ext = 'mp4';
                    $voice = $attachment['content']['voice'];
                    $title = 'Голосовое сообщение';
                    if(isset($voice['voice_id'])){
                        $url = $this->apiUrl . '?api=socialCall&social_id=0&method=getVoice&voice=' . $voice['voice_id'];
                    }
                break;
                case 'link':
                    $type = Attachment::LINK;
                    $link = $attachment['content']['link'];
                    $url = $link['url'];
                    $title = mb_strlen($link['text']) ? $link['text'] : 'Ссылка';
                    if(isset($link['preview'])){
                        $preview = '';
                        $lastSizeLength = 0;
                        foreach($link['preview']['images'] as $size => $previewImage){
                            $sizeLength = mb_strlen($size);
                            if($sizeLength > $lastSizeLength){
                                $priview = $previewImage;
                                $lastSizeLength = $sizeLength;
                            }
                        }
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
    public function apiGetVoice()
    {
        $voice = isset($_GET['voice']) ? $_GET['voice'] : false;
        if($voice){
            $result = $this->api('messenger/v1/accounts/'.$this->peerId.'/getVoiceFiles', array(
                'voice_ids' => $voice
            ), false, false);
            if(is_array($result) && isset($result['voices_urls']) && isset($result['voices_urls'][$voice])){
                $content = file_get_contents($result['voices_urls'][$voice]);
                header("Content-Type: video/mp4");
                header('Accept-Ranges: bytes');
                header("Content-Disposition: inline;");
                header("Content-Transfer-Encoding: binary\n");
                header('Connection: close');
                echo $content;
                die;
            }
        }
        return array();
    }
    private function message($chatId, array $message, &$profiles){
        $fromId = $message['author_id'];
        if(!$fromId){
            $fromId = $this->peerId;
        }
        $attachments = array();
        $text = '';
        switch($message['type']){
            case 'text':
                $text = $message['content']['text'];
            break;
            case 'system':
                $text = $message['content']['text'];
            break;
            case 'image':
            case 'item':
            case 'voice':
            case 'link':
                $attachments = array_merge($attachments, $this->attachments(array($message), $profiles));
            break;
            default:
                $text = print_r($message, true);
        }
        //$attachments = $this->attachments($message['attachments'], $profiles);
        return new Message($message['id'], $chatId, $fromId, isset($message['created']) ? $message['created'] : (isset($message['delivered']) ? $message['delivered'] : 0), $text, $attachments);
    }
    private function getProfiles(&$profiles, $data)
    {
        if(isset($data['users'])){
            foreach($data['users'] as $profile){
                $profileName = array();
                if(isset($profile['name']) && mb_strlen($profile['name'])){
                    $profileName[] = $profile['name'];
                }
                if(!count($profileName)){
                    $profileName[] = 'Пользователь';
                }
                $profilePhoto = '';
                $profileId = $profile['id'];
                $profileUrl = '';
                if(isset($profile['public_user_profile'])){
                    $profilePublic = $profile['public_user_profile'];
                    if(isset($profilePublic['avatar']) && isset($profilePublic['avatar']['default']) && mb_strlen($profilePublic['avatar']['default'])){
                        $profilePhoto = $profilePublic['avatar']['default'];
                    }
                    if(isset($profilePublic['url'])){
                        $profileUrl = $profilePublic['url'];
                    }
                }
                if(!isset($profiles[$profileId])){
                    $profiles[$profileId] = new Peer($profileId, implode(" ", $profileName), $profilePhoto, $profileUrl);
                }
            }
        }
    }
    private function queryChats($method, $params)
    {
        return new Handle($this->api($method, $params, true, false), function($response){
            $chats = array();
            if(is_array($response) && isset($response['chats'])){
                foreach($response['chats'] as $item){
                    $profiles = array();
                    if(isset($item['users'])){
                        $this->getProfiles($profiles, $item);
                    }
                    $peerId = $item['id'];
                    $peerTitle = '';
                    $peerPhoto = $this->noAvatar;
                    $peerUrl = '';
                    $socialIcon = '';
                    $city = 'Тула';
                    $chatItem = false;
                    if(isset($item['context'])){
                        $context = $item['context'];
                        $contextValue = $context['value'];
                        $peerTitle = $contextValue['title'];
                        if(isset($contextValue['images']) && isset($contextValue['images']['main'])){
                            foreach($contextValue['images']['main'] as $size => $url){
                                $peerPhoto = $url;
                                break;
                            }
                        }
                        $peerUrl = $contextValue['url'];
                        if(isset($contextValue['location']) && isset($contextValue['location']['title'])){
                            $city = $contextValue['location']['title'];
                            switch($city){
                                case 'Новомосковск': $socialIcon = 'avito-n.png'; break;
                            }
                        }
                        $socialTitle = 'Чат объявления из г. '. $city;
                        $chatItem = array(
                            'type' => 'item',
                            'content' => '<span onlick="return false;" style="color: black!important; cursor: default; position: absolute; right: 5px; top: 5px; font-size: 18px; font-weight: bold;">Город: '.$city.'</span>',
                            'title' => $peerTitle,
                            'url' => $peerUrl,
                            'photo' => $peerPhoto
                        );
                    }
                    foreach($profiles as $profile){
                        if($profile->id != $this->peerId){
                            if($profile->photo){
                                $peerTitle = $profile->name;
                                $peerPhoto = $profile->photo;
                            }
                        }
                    }
                    $peer = new Peer($peerId, mb_strlen($peerTitle) ? $peerTitle : 'Чат авито', $peerPhoto, $peerUrl);
                    $lastMessage = false;
                    $unseen = 0;
                    if(isset($item['last_message'])){
                        $lastMessage = $this->message($peer->id, $item['last_message'], $profiles);
                        if($lastMessage && $item['last_message']['direction'] == 'in' && !isset($item['last_message']['read'])){
                            $unseen = 1;
                        }
                    }
                    $chat = new Chat($peer->id, $lastMessage ? $lastMessage->date : 0, $unseen, $peer, $lastMessage);
                    $chat->item = $chatItem;
                    if($socialIcon){
                        $chat->socialIcon = $socialIcon;
                    }
                    $chat->socialTitle = $socialTitle;
                    $chats[] = $chat;
                }
            }
            return $chats;
        });
    }
    public function getChats($offset = 0, $limit = 20)
    {
        return $this->queryChats('messenger/v2/accounts/'.$this->peerId.'/chats', array(
            'chat_types' => "u2i",
            'offset' => $offset,
            'limit' => $limit
        ));
    }
    public function searchChats($search, $offset = 0, $limit = 20)
    {
        return array();
    }
    public function getMessages($chatId, $offset = 0, $limit = 20)
    {
        $result = $this->api('messenger/v3/accounts/'.$this->peerId.'/chats/'.$chatId.'/messages/', [
            'offset' => $offset,
            'limit' => $limit
        ], false, false);
        $list = array();
        if(is_array($result) && isset($result['messages'])){
            foreach($result['messages'] as $item){
                $profiles = array();
                $list[] = $this->message($chatId, $item, $profiles);
            }
        }
        if($offset === 0){
            $this->api('messenger/v1/accounts/'.$this->peerId.'/chats/'.$chatId.'/read', array(), false, true);
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
        $photoId = '';
        if(count($attachments)){
            foreach($attachments as $attachment){
                switch($attachment->type){
                    case Attachment::PHOTO:
                        $ch = curl_init($this->url . 'messenger/v1/accounts/'.$this->peerId.'/uploadImages');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            "Authorization: Bearer " . $this->getToken()
                        ));
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                            'uploadfile[]' => function_exists('curl_file_create') ? curl_file_create($attachment->url) : "@" . $attachment->url
                        ));
                        $photoResult = curl_exec($ch);
                        $uploadData = json_decode($photoResult, true);
                        if(is_array($uploadData) && count($uploadData)){
                            $uploadData = array_keys($uploadData);
                            $photoId = $uploadData[0];
                        }
                    break;
                }
            }
        }
        if($photoId && mb_strlen($text)){
            return array(
                'error' => 'В авито нельзя отправить текст одновременно с фотографией'
            );
        }
        if($photoId){
            $result = $this->api('messenger/v1/accounts/'.$this->peerId.'/chats/'.$chatId.'/messages/image', array(
                'image_id' => $photoId
            ));
        }else{
            $result = $this->api('messenger/v1/accounts/'.$this->peerId.'/chats/'.$chatId.'/messages', array(
                'message' => array(
                    'text' => $text
                ),
                'type' => 'text'
            ));
        }
        if(is_array($result) && isset($result['id'])){
            $profiles = array();
            return $this->message($chatId, $result, $profiles);
        }
        return false;
    }
    public function getCountUnseenMessages()
    {
        return new Handle($this->api('messenger/v2/accounts/'.$this->peerId.'/chats', array(
            'unread_only' => 'true',
            'limit' => 100
        ), true, false), function($response){
            return is_array($response) && isset($response['chats']) ? count($response['chats']) : 0;
        });
    }
}