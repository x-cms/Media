<?php

namespace Xcms\Media\Support;

use Carbon\Carbon;
use Config;

class MediaLibraryItem
{
    const TYPE_FILE = 'file';
    const TYPE_FOLDER = 'folder';

    const FILE_TYPE_IMAGE = 'image';
    const FILE_TYPE_VIDEO = 'video';
    const FILE_TYPE_AUDIO = 'audio';
    const FILE_TYPE_DOCUMENT = 'document';

    /**
     * @var string Specifies the item path relative to the Library root.
     */
    public $path;

    /**
     * @var integer Specifies the item size.
     * For files the item size is measured in bytes. For folders it
     * contains the number of files in the folder.
     */
    public $size;

    /**
     * @var integer Contains the last modification time (Unix timestamp).
     */
    public $lastModified;

    /**
     * @var string Specifies the item type.
     */
    public $type;

    /**
     * @var string Specifies the public URL of the item.
     */
    public $publicUrl;

    /**
     * @var array Contains a default list of image files and directories to ignore.
     * Override with config: media.storage.media.imageExtensions
     */
    protected static $imageExtensions;

    /**
     * @var array Contains a default list of video files and directories to ignore.
     * Override with config: media.storage.media.videoExtensions
     */
    protected static $videoExtensions;

    /**
     * @var array Contains a default list of audio files and directories to ignore.
     * Override with config: media.storage.media.audioExtensions
     */
    protected static $audioExtensions;

    /**
     * @param string $path
     * @param int $size
     * @param int $lastModified
     * @param string $type
     * @param string $publicUrl
     */
    public function __construct($path, $size, $lastModified, $type, $publicUrl)
    {
        $this->path = $path;
        $this->size = $size;
        $this->lastModified = $lastModified;
        $this->type = $type;
        $this->publicUrl = $publicUrl;
    }

    /**
     * @return bool
     */
    public function isFile()
    {
        return $this->type == self::TYPE_FILE;
    }

    /**
     * Returns the file type by its name.
     * The known file types are: image, video, audio, document
     * @return string Returns the file type or NULL if the item is a folder.
     */
    public function getFileType()
    {
        if (!$this->isFile()) {
            return null;
        }

        if (!self::$imageExtensions) {
            self::$imageExtensions = Config::get('media.storage.media.imageExtensions');
            self::$videoExtensions = Config::get('media.storage.media.videoExtensions');
            self::$audioExtensions = Config::get('media.storage.media.audioExtensions');
        }

        $extension = pathinfo($this->path, PATHINFO_EXTENSION);
        if (!strlen($extension)) {
            return self::FILE_TYPE_DOCUMENT;
        }

        if (in_array($extension, self::$imageExtensions)) {
            return self::FILE_TYPE_IMAGE;
        }

        if (in_array($extension, self::$videoExtensions)) {
            return self::FILE_TYPE_VIDEO;
        }

        if (in_array($extension, self::$audioExtensions)) {
            return self::FILE_TYPE_AUDIO;
        }

        return self::FILE_TYPE_DOCUMENT;
    }

    /**
     * Returns the item size as string.
     * For file-type items the size is the number of bytes. For folder-type items
     * the size is the number of items contained by the item.
     * @return string Returns the size as string.
     */
    public function sizeToString()
    {
        return $this->type == self::TYPE_FILE
            ? $this->__sizeToString($this->size)
            : $this->size . ' ' . '个数';
    }

    /**
     * Returns the item last modification date as string.
     * @return string Returns the item's last modification date as string.
     */
    public function lastModifiedAsString()
    {
        if (!($date = $this->lastModified)) {
            return null;
        }

        return Carbon::createFromTimestamp($date)->toFormattedDateString();
    }

    protected function __sizeToString($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return $bytes = number_format($bytes / 1024, 2) . ' KB';
        }

        if ($bytes > 1) {
            return $bytes = $bytes . ' bytes';
        }

        if ($bytes == 1) {
            return $bytes . ' byte';
        }

        return '0 bytes';
    }
}
