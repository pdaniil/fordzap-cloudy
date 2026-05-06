<?php
namespace SocialsChats;
class Api extends API\Api
{
    private $main;
    private $allowExtensions;
    private $fileMaxSize;
    private $uploadPath;
    public function __construct($db, $init = true, $options = array())
    {
        parent::__construct($db, false, isset($options['rights']) ? (bool)$options['rights'] : false);
        $this->allowExtensions = array(
            'txt' => 'text/plain',
            'zip' => 'application/x-zip-compressed',
            'csv' => 'text/csv',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg'
        );
        $this->fileMaxSize = 10 * 1024 * 1024; # 10 Мб
        $this->uploadPath = sys_get_temp_dir() . '/soc-uploads/';
        $this->main = new Main($options);
        if($init){
            $optionsVkGroupCallback = isset($options['vk_group_callback']) ? $options['vk_group_callback'] : false;
            $callback = new VKGroup\CallbackApi($db, $this->main, $optionsVkGroupCallback && isset($optionsVkGroupCallback['confirmation']) ? $optionsVkGroupCallback['confirmation'] : '',  $optionsVkGroupCallback && isset($optionsVkGroupCallback['secret']) ? $optionsVkGroupCallback['secret'] : '');
            if($callback->handle()){
                die;
            }
            $this->handle();
        }
    }
    private function offset(&$offset)
    {
        $offset = $this->paramId('offset');
    }
    public function apiSendNotify()
    {
        $userVkConnection = $this->query("SELECT * FROM vk_users_connections WHERE user_id=?", $this->paramId('user_id'))->fetch(\PDO::FETCH_ASSOC);
        if($userVkConnection){
            if($userVkConnection['notify']){
                return $this->main->sendNotify($userVkConnection['vk_user_id'], $this->param('subject'), $this->param('message'));
            }
            return $this->error('Уведомления отключены для пользователя');
        }
        return $this->error('Пользователь не найден');
    }
    public function apiLimitsGet()
    {
        return array(
            'chats' => Main::LIMIT_CHATS,
            'messages' => Main::LIMIT_MESSAGES
        );
    }
    public function apiSocialsGet()
    {
        return $this->main->getSocials();
    }
    public function apiMessagesGet()
    {
        $socialId = $this->paramId('social_id');
        $chatId = $this->param('chat_id');
        $this->offset($offset);
        return $this->main->getMessages($socialId, $chatId, $offset);
    }
    public function apiChatsGet()
    {
        $this->offset($offset);
        return $this->main->getChats($offset, $this->paramList('skip'), $this->param('search'));
    }
    public function apiMessageSend()
    {
        $socialId = $this->paramId('social_id');
        $chatId = $this->param('chat_id');
        $text = $this->param('text');
        $attachments = $this->param('attachments');
        $attachmentsList = array();
        if($attachments){
            foreach(explode("|", $attachments) as $attachment){
                if($attachment){
                    $fileName = $this->uploadPath . $attachment;
                    if(file_exists($fileName)){
                        $attachmentsList[] = $fileName;
                    }
                }
            }
        }
        $result = $this->main->sendMessage($socialId, $chatId, $text, $attachmentsList);
        if($result === false){
            return $this->error('Не удалось отправить сообщение');
        }
        if(is_array($result) && isset($result['error'])){
            return $this->error($result['error']);
        }
        return $this->success($result);
    }
    public function apiCountUnseenMessages()
    {
        return $this->main->getCountUnseenMessages();
    }
    private function fileSize($v, $rt = true)
    {
        $rv = $v < 1024 * 1024 ? $v / 1024 : $v / 1024 / 1024;
        $t = $v < 1024 * 1024 ? 'Кб' : 'Мб';
        return round(ceil($rv * 100) / 100, 2) . ($rt === false ? '' : ' ' . $t);
    }
    public function apiUpload()
    {
        if(count($_FILES) && isset($_FILES['file'])){
            $file = $_FILES['file'];
            $fileInfo = pathinfo($file['name']);
            if(isset($fileInfo['extension']) && $file['error'] == 0){
                $ext = mb_strtolower($fileInfo['extension']);
                if(isset($this->allowExtensions[$ext]) && $this->allowExtensions[$ext] == $file['type']){
                    if($file['size'] > $this->fileMaxSize){
                        return $this->error('Превышен размер ('. $this->fileSize($this->fileMaxSize) .')');
                    }
                    $path = $this->uploadPath;
                    if(!is_dir($path)){
                        mkdir($path);
                    }
                    foreach(scandir($path) as $pathFile){
                        $pathFileName = $path . $pathFile;
                        if(time() - filemtime($pathFileName) > 24 * 3600){
                            @unlink($pathFileName);
                        }
                    }
                    $fileName = $path . $file['name'];
                    if(!move_uploaded_file($file['tmp_name'], $fileName)){
                        return $this->error('Файл не загружен');
                    }
                    return $this->success(array(
                        'message' => 'Файл успешно загружен',
                        'name' => $file['name'],
                        'size' => $this->fileSize($file['size'])
                    ));
                }else{
                    return $this->error('Некорректный формат');
                }
            }
        }
        return $this->error('Ошибка загрузки');
    }
    public function apiSocialCall()
    {
        $socialId = $this->paramId('social_id');
        $socialMethod = 'api' . $this->param('method');
        $social = $this->main->getSocial($socialId);
        if($social && mb_strlen($socialMethod) > 3 && method_exists($social, $socialMethod)){
            $result = call_user_func([$social, $socialMethod]);
            return is_array($result) && isset($result['error']) ? $this->error($result) : $this->success($result);
        }
        return $this->error('Не удалось определить метод социальной сети');
    }
    public function apiUploadRemove()
    {
        $path = $this->uploadPath;
        $name = $this->param('name');
        if(!$name){
            return $this->error('Введите название файла');
        }
        $fileName = $path . $name;
        if(file_exists($fileName)){
            @unlink($fileName);
        }
        return $this->success('Файл успешно удален');
    }
    public function apiUploadGet()
    {
        $path = $this->uploadPath;
        $name = $this->param('name');
        if(!$name){
            return $this->error('Введите название файла');
        }
        $fileName = $path . $name;
        if(!file_exists($fileName)){
            return $this->error('Файл не найден');
        }
        $fileInfo = pathinfo($fileName);
        if(!isset($fileInfo['extension'])){
            return $this->error('Некорректный формат файла');
        }
        $ext = mb_strtolower($fileInfo['extension']);
        $allowMime = $this->allowExtensions[$ext];
        $showExtensions = array(
            'png',
            'jpeg',
            'jpg',
            'txt',
            'csv'
        );
        if(!in_array($ext, $showExtensions)){
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');            
            header('Content-Disposition: attachment; filename='.$name);
            header('Content-Transfer-Encoding: binary');
        }else{
            header('Content-Type: '. $allowMime);
        }
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileName));
        readfile($fileName);
        die;
    }
}