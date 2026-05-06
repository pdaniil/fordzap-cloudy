<?php
namespace SocialsChats;
class Main
{
    private $socials;
    private $skipSocials;
    const LIMIT_CHATS = 20;
    const LIMIT_MESSAGES = 20;
    public function __construct($options = array())
    {
        $this->socials = array();
        foreach($options as $option => $data){
            switch($option){
                case 'socials_config':
                    foreach($data as $socialClass => $socialData){
                        $socialClass = "SocialsChats\\" . $socialClass . "\\Social";
                        if(class_exists($socialClass) && is_array($socialData)){
                            $socialData['api_url'] = isset($options['api_url']) ? $options['api_url'] : '';
                            $this->socials[] = new $socialClass($socialData);
                        }
                    }
                break;
            }
        }
        $this->skipSocials = array();
    }
    private function multiQuery($methodName, $methodHandle, array $methodParams = array())
    {
        $handlers = [];
        $mh = curl_multi_init();
        foreach($this->socials as $id => $social){
            if(in_array($id, $this->skipSocials)){
                continue;
            }
            if(!method_exists($social, $methodName)){
                continue;
            }
            $handle = call_user_func_array(array($social, $methodName), $methodParams);
            if(is_object($handle) && is_a($handle, 'SocialsChats\Handle') && $handle->ch && $handle->handle){
                $handlers[$id] = $handle;
                curl_multi_add_handle($mh, $handle->ch);
            }
        }
        $active = null;
        for(;;){
            curl_multi_exec($mh, $active);
            if($active < 1){
                break;
            }else{
                curl_multi_select($mh, 1);
            }
        }
        $responses = array();
        foreach($handlers as $id => $handle){
            $content = curl_multi_getcontent($handle->ch);
            if(mb_strlen($content)){
                $json = json_decode($content, true);
                if(is_array($json)){
                    $responses[$id] = call_user_func($handle->handle, $json);
                }
            }
            curl_multi_remove_handle($mh, $handle->ch);
        }        
        curl_multi_close($mh);
        if(is_callable($methodHandle)){
            return call_user_func($methodHandle, $responses);
        }
        return $responses;
    }
    public function getSocials()
    {
        $list = array();
        foreach($this->socials as $id => $social){
            $list[] = array(
                'id' => $id,
                'icon' => $social->icon
            );
        }
        return $list;
    }
    public function getSocial($index)
    {
        return isset($this->socials[$index]) ? $this->socials[$index] : false;
    }
    public function getSocialIndexByPeerId($peerId)
    {
        foreach($this->socials as $index => $social){
            if($social->peerId == $peerId){
                return $index;
            }
        }
        return false;
    }
    public function getSocialByPeerId($peerId)
    {
        $socialIndex = $this->getSocialIndexByPeerId($peerId);
        return $socialIndex !== false ? $this->socials[$socialIndex] : false;
    }
    public function getChats($offset = 0, $skipSocials = array(), $search = '')
    {
        $this->skipSocials = $skipSocials;
        return $this->multiQuery(mb_strlen($search) ? 'searchChats' : 'getChats', function($responses){
            $chats = [];
            foreach($responses as $socialId => $response){
                foreach($response as $chat){
                    if($chat->lastMessage){
                        $chat->lastMessage->socialId = $socialId;
                    }
                    $chat->socialId = $socialId;
                    $chats[] = $chat;
                }
            }
            usort($chats, function($a, $b){
                return $a->date > $b->date ? -1 : ($a->date < $b->date ? 1 : 0); 
            });
            return $chats;
        }, mb_strlen($search) ? array(
            $search,
            $offset,
            self::LIMIT_CHATS
        ) : array(
            $offset,
            self::LIMIT_CHATS
        ));
    }
    public function getMessages($socialId, $chatId, $offset = 0)
    {
        $list = array();
        if(isset($this->socials[$socialId])){
            $social = $this->socials[$socialId];
            $messages = $social->getMessages($chatId, $offset, self::LIMIT_MESSAGES);
            if(is_array($messages)){
                foreach($messages as $message){
                    if(is_object($message) && is_a($message, 'SocialsChats\Message')){
                        if(!mb_strlen($message->text) && !count($message->attachments)){
                            continue;
                        }
                        $message->type = $social->peerId == $message->fromId;
                        $list[] = $message;
                    }
                }
            }
        }
        return $list;
    }
    public function sendMessage($socialId, $chatId, $text, array $attachments = array())
    {
        if(isset($this->socials[$socialId])){
            $social = $this->socials[$socialId];
            $attachmentsList = array();
            $allowExtensions = $social->getAllowExtensions();
            foreach($attachments as $attachment){
                $fileInfo = pathinfo($attachment);
                if(isset($fileInfo['extension'])){
                    $ext = mb_strtolower($fileInfo['extension']);
                    if(isset($allowExtensions[$ext]) && file_exists($attachment)){
                        $attachmentsList[] = new Attachment($allowExtensions[$ext], $fileInfo['filename'], filesize($attachment), $ext, filemtime($attachment), $attachment, '');
                    }
                }
            }
            return $social->sendMessage($chatId, $text, $attachmentsList);
        }
        return false;
    }
    public function getCountUnseenMessages()
    {
        return $this->multiQuery('getCountUnseenMessages', function($responses){
            $count = 0;
            foreach($responses as $response){
                $count += (int)$response;
            }
            return $count;
        });
    }
    public function sendNotify($userId, $subject, $message)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . "/lib/mpdf/vendor/autoload.php";
        if(mb_strlen($message)){
            $vkGroupSocial = false;
            foreach($this->socials as $social){
                if(is_object($social) && is_a($social, "SocialsChats\VKGroup\Social")){
                    $vkGroupSocial = $social;
                }
            }
            if($vkGroupSocial !== false){
                $mpdf = new \Mpdf\Mpdf();
                $mpdf->AddPageByArray([
                    'margin-left' => 0,
                    'margin-right' => 0,
                    'margin-top' => 0,
                    'margin-bottom' => 0,
                ]);
                $mpdf->WriteHTML($message);
                $fileName = sys_get_temp_dir() . '/' . md5($userId. $subject . $message) . '.pdf';
                if(file_exists($fileName)){
                    @unlink($fileName);  
                }
                $mpdf->Output($fileName, \Mpdf\Output\Destination::FILE);
                $attachment = new Attachment($vkGroupSocial->getAllowExtensions()['pdf'], 'Подробнее', filesize($fileName), 'pdf', filemtime($fileName), $fileName, '');
                return $vkGroupSocial->sendMessage($userId, $subject, array($attachment));
            }
        }
        return false;
    }
}