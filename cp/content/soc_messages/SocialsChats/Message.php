<?php
namespace SocialsChats;
class Message
{
    public $socialId;
    public $id;
    public $chatId;
    public $fromId;
    public $date;
    public $text;
    public $attachments;
    public $type;
    public function __construct($id, $chatId, $fromId, $date, $text, $attachments = array())
    {
        $this->type = 0;
        $this->socialId = 0;
        $this->id = $id;
        $this->chatId = $chatId;
        $this->fromId = $fromId;
        $this->date = is_numeric($date) ? $date : strtotime($date);
        $this->text = str_replace(array("\r\n", "\n"), "<br>", $text);
        $list = array();
        foreach($attachments as $attachment){
            if(is_object($attachment) && is_a($attachment, 'SocialsChats\Attachment')){
                $list[] = $attachment;
            }
        }
        $this->attachments = $list;
    }
}