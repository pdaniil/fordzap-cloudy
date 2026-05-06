<?php
namespace SocialsChats;
class Attachment
{
    const PHOTO = 'photo';
    const AUDIO = 'audio';
    const VIDEO = 'video';
    const DOCUMENT = 'document';
    const LINK = 'link';
    const OTHER = 'other';
    public $type;
    public $title;
    public $size;
    public $ext;
    public $date;
    public $url;
    public $preview;
    public $data;
    public static function checkType($type)
    {
        if(!in_array($type, array(self::PHOTO, self::AUDIO, self::VIDEO, self::DOCUMENT, self::LINK))){
            $type = self::OTHER;
        }
        return $type;
    }
    public function __construct($type, $title, $size, $ext, $date, $url, $preview = '', $data = null)
    {
        $type = self::checkType($type);
        $this->type = $type;
        $this->title = $title;
        $this->size = $size;
        $this->ext = $ext;
        $this->date = $date;
        $this->url = $url;
        $this->preview = $preview;
        $this->data = $data;
    }
}