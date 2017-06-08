<?php

namespace Xcms\Media\Models;

use Exception;
use File as FileHelper;
use Config;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\File\File as FileObj;
use Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Xcms\Media\Support\BrokenImage;
use Xcms\Media\Support\Resizer;

class File extends Model
{
    protected $table = 'files';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public $data = null;

    public static $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    protected $autoMimeTypes = [
        'docx' => 'application/msword',
        'xlsx' => 'application/excel',
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'pdf'  => 'application/pdf'
    ];

    /**
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile
     * @return bool|File
     */
    public function fromPost($uploadedFile)
    {
        if ($uploadedFile === null) {
            return false;
        }

        $this->name = $uploadedFile->getClientOriginalName();
        $this->size = $uploadedFile->getClientSize();
        $this->type = $uploadedFile->getMimeType();
        $this->disk_name = $this->getDiskName();

        /*
         * getRealPath() can be empty for some environments (IIS)
         */
        $realPath = empty(trim($uploadedFile->getRealPath()))
            ? $uploadedFile->getPath() . DIRECTORY_SEPARATOR . $uploadedFile->getFileName()
            : $uploadedFile->getRealPath();

        $this->putFile($realPath, $this->disk_name);

        return $this;
    }

    public function fromFile($filePath)
    {
        if ($filePath === null) {
            return false;
        }

        $file = new FileObj($filePath);
        $this->name = $file->getFilename();
        $this->size = $file->getSize();
        $this->type = $file->getMimeType();
        $this->disk_name = $this->getDiskName();

        $this->putFile($file->getRealPath(), $this->disk_name);

        return $this;
    }

    public function getPathAttribute()
    {
        return $this->getPath();
    }

    public function getExtensionAttribute()
    {
        return $this->getExtension();
    }

    public function setDataAttribute($value)
    {
        $this->data = $value;
    }

    public function output($disposition = 'inline')
    {
        header("Content-type: ".$this->getContentType());
        header('Content-Disposition: '.$disposition.'; filename="'.$this->name.'"');
        header('Cache-Control: private');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Accept-Ranges: bytes');
        header('Content-Length: '.$this->size);
        echo $this->getContents();
    }

    public function outputThumb($width, $height, $options = [])
    {
        $disposition = array_get($options, 'disposition', 'inline');
        $this->getThumb($width, $height, $options);
        $options = $this->getDefaultThumbOptions($options);
        $thumbFile = $this->getThumbFilename($width, $height, $options);
        $contents = $this->getContents($thumbFile);

        header("Content-type: ".$this->getContentType());
        header('Content-Disposition: '.$disposition.'; filename="'.basename($thumbFile).'"');
        header('Cache-Control: private');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Accept-Ranges: bytes');
        header('Content-Length: '.mb_strlen($contents, '8bit'));
        echo $contents;
    }

    public function getFilename()
    {
        return $this->name;
    }

    public function getExtension()
    {
        return FileHelper::extension($this->name);
    }

    public function getLastModified($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->disk_name;
        }

        return $this->storageCmd('lastModified', $this->getStorageDirectory() . $this->getPartitionDirectory() . $fileName);
    }

    public function getContentType()
    {
        if ($this->type !== null) {
            return $this->type;
        }

        $ext = $this->getExtension();
        if (isset($this->autoMimeTypes[$ext])) {
            return $this->type = $this->autoMimeTypes[$ext];
        }

        return null;
    }

    public function getContents($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->disk_name;
        }

        return $this->storageCmd('get', $this->getStorageDirectory() . $this->getPartitionDirectory() . $fileName);
    }

    public function getPath()
    {
        return $this->getPublicPath() . $this->getPartitionDirectory() . $this->disk_name;
    }

    public function getLocalPath()
    {
        if ($this->isLocalStorage()) {
            return $this->getLocalRootPath() . '/' . $this->getDiskPath();
        }
        else {
            $itemSignature = md5($this->getPath()) . $this->getLastModified();

            $cachePath = $this->getLocalTempPath($itemSignature . '.' . $this->getExtension());

            if (!FileHelper::exists($cachePath)) {
                $this->copyStorageToLocal($this->getDiskPath(), $cachePath);
            }

            return $cachePath;
        }
    }

    public function getDiskPath()
    {
        return $this->getStorageDirectory() . $this->getPartitionDirectory() . $this->disk_name;
    }

    public function isPublic()
    {
        if (array_key_exists('is_public', $this->attributes)) {
            return $this->attributes['is_public'];
        }

        if (isset($this->is_public)) {
            return $this->is_public;
        }

        return true;
    }

    public function sizeToString()
    {
        return FileHelper::sizeToString($this->file_size);
    }

    public function beforeSave()
    {
        /*
         * Process the data property
         */
        if ($this->data !== null) {
            if ($this->data instanceof UploadedFile) {
                $this->fromPost($this->data);
            }
            else {
                $this->fromFile($this->data);
            }

            $this->data = null;
        }
    }

    public function afterDelete()
    {
        try {
            $this->deleteThumbs();
            $this->deleteFile();
        }
        catch (Exception $ex) {}
    }

    public function isImage()
    {
        return in_array(strtolower($this->getExtension()), static::$imageExtensions);
    }

    public function getThumb($width, $height, $options = [])
    {
        if (!$this->isImage()) {
            return $this->getPath();
        }

        $width = (int) $width;
        $height = (int) $height;

        $options = $this->getDefaultThumbOptions($options);

        $thumbFile = $this->getThumbFilename($width, $height, $options);
        $thumbPath = $this->getStorageDirectory() . $this->getPartitionDirectory() . $thumbFile;
        $thumbPublic = $this->getPublicPath() . $this->getPartitionDirectory() . $thumbFile;

        if (!$this->hasFile($thumbFile)) {

            if ($this->isLocalStorage()) {
                $this->makeThumbLocal($thumbFile, $thumbPath, $width, $height, $options);
            }
            else {
                $this->makeThumbStorage($thumbFile, $thumbPath, $width, $height, $options);
            }

        }

        return $thumbPublic;
    }

    protected function getThumbFilename($width, $height, $options)
    {
        return 'thumb_' . $this->id . '_' . $width . 'x' . $height . '_' . $options['offset'][0] . '_' . $options['offset'][1] . '_' . $options['mode'] . '.' . $options['extension'];
    }

    protected function getDefaultThumbOptions($overrideOptions = [])
    {
        $defaultOptions = [
            'mode'      => 'auto',
            'offset'    => [0, 0],
            'quality'   => 90,
            'sharpen'   => 0,
            'extension' => 'auto',
        ];

        if (!is_array($overrideOptions)) {
            $overrideOptions = ['mode' => $overrideOptions];
        }

        $options = array_merge($defaultOptions, $overrideOptions);

        $options['mode'] = strtolower($options['mode']);

        if ((strtolower($options['extension'])) == 'auto') {
            $options['extension'] = strtolower($this->getExtension());
        }

        return $options;
    }

    protected function makeThumbLocal($thumbFile, $thumbPath, $width, $height, $options)
    {
        $rootPath = $this->getLocalRootPath();
        $filePath = $rootPath.'/'.$this->getDiskPath();
        $thumbPath = $rootPath.'/'.$thumbPath;

        /*
         * Handle a broken source image
         */
        if (!$this->hasFile($this->disk_name)) {
            BrokenImage::copyTo($thumbPath);
        }
        /*
         * Generate thumbnail
         */
        else {
            try {
                Resizer::open($filePath)
                    ->resize($width, $height, $options)
                    ->save($thumbPath)
                ;
            }
            catch (Exception $ex) {
                BrokenImage::copyTo($thumbPath);
            }
        }

        FileHelper::chmod($thumbPath);
    }

    protected function makeThumbStorage($thumbFile, $thumbPath, $width, $height, $options)
    {
        $tempFile = $this->getLocalTempPath();
        $tempThumb = $this->getLocalTempPath($thumbFile);

        /*
         * Handle a broken source image
         */
        if (!$this->hasFile($this->disk_name)) {
            BrokenImage::copyTo($tempThumb);
        }
        /*
         * Generate thumbnail
         */
        else {
            $this->copyStorageToLocal($this->getDiskPath(), $tempFile);

            try {
                Resizer::open($tempFile)
                    ->resize($width, $height, $options)
                    ->save($tempThumb)
                ;
            }
            catch (Exception $ex) {
                BrokenImage::copyTo($tempThumb);
            }

            FileHelper::delete($tempFile);
        }

        /*
         * Publish to storage and clean up
         */
        $this->copyLocalToStorage($tempThumb, $thumbPath);
        FileHelper::delete($tempThumb);
    }

    public function deleteThumbs()
    {
        $pattern = 'thumb_'.$this->id.'_';

        $directory = $this->getStorageDirectory() . $this->getPartitionDirectory();
        $allFiles = $this->storageCmd('files', $directory);
        $collection = [];
        foreach ($allFiles as $file) {
            if (starts_with(basename($file), $pattern)) {
                $collection[] = $file;
            }
        }

        /*
         * Delete the collection of files
         */
        if (!empty($collection)) {
            if ($this->isLocalStorage()) {
                FileHelper::delete($collection);
            }
            else {
                Storage::delete($collection);
            }
        }
    }

    protected function getDiskName()
    {
        if ($this->disk_name !== null)
            return $this->disk_name;

        $ext = strtolower($this->getExtension());
        $name = str_replace('.', '', uniqid(null, true));

        return $this->disk_name = $ext !== null ? $name.'.'.$ext : $name;
    }

    protected function getLocalTempPath($path = null)
    {
        if (!$path) {
            return $this->getTempPath() . '/' . md5($this->getDiskPath()) . '.' . $this->getExtension();
        }

        return $this->getTempPath() . '/' . $path;
    }

    protected function putFile($sourcePath, $destinationFileName = null)
    {
        if (!$destinationFileName) {
            $destinationFileName = $this->disk_name;
        }

        $destinationPath = $this->getStorageDirectory() . $this->getPartitionDirectory();

        if (!$this->isLocalStorage()) {
            return $this->copyLocalToStorage($sourcePath, $destinationPath . $destinationFileName);
        }

        /*
         * Using local storage, tack on the root path and work locally
         * this will ensure the correct permissions are used.
         */
        $destinationPath = $this->getLocalRootPath() . '/' . $destinationPath;

        /*
         * Verify the directory exists, if not try to create it. If creation fails
         * because the directory was created by a concurrent process then proceed,
         * otherwise trigger the error.
         */
        if (
            !FileHelper::isDirectory($destinationPath) &&
            !FileHelper::makeDirectory($destinationPath, 0777, true, true) &&
            !FileHelper::isDirectory($destinationPath)
        ) {
            trigger_error(error_get_last(), E_USER_WARNING);
        }

        return FileHelper::copy($sourcePath, $destinationPath . $destinationFileName);
    }

    protected function deleteFile($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->disk_name;
        }

        $directory = $this->getStorageDirectory() . $this->getPartitionDirectory();
        $filePath = $directory . $fileName;

        if ($this->storageCmd('exists', $filePath)) {
            $this->storageCmd('delete', $filePath);
        }

        $this->deleteEmptyDirectory($directory);
    }

    protected function hasFile($fileName = null)
    {
        $filePath = $this->getStorageDirectory() . $this->getPartitionDirectory() . $fileName;
        return $this->storageCmd('exists', $filePath);
    }

    protected function deleteEmptyDirectory($dir = null)
    {
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);

        $dir = dirname($dir);
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);

        $dir = dirname($dir);
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);
    }

    protected function isDirectoryEmpty($dir)
    {
        if (!$dir) {
            return null;
        }

        return count($this->storageCmd('allFiles', $dir)) === 0;
    }

    protected function storageCmd()
    {
        $args = func_get_args();
        $command = array_shift($args);

        if ($this->isLocalStorage()) {
            $interface = 'File';
            $path = $this->getLocalRootPath();
            $args = array_map(function($value) use ($path) {
                return $path . '/' . $value;
            }, $args);
        }
        else {
            $interface = 'Storage';
        }

        return forward_static_call_array([$interface, $command], $args);
    }

    protected function copyStorageToLocal($storagePath, $localPath)
    {
        return FileHelper::put($localPath, Storage::get($storagePath));
    }

    protected function copyLocalToStorage($localPath, $storagePath)
    {
        return Storage::put($storagePath, FileHelper::get($localPath), ($this->isPublic()) ? 'public' : null);
    }

    public static function getMaxFilesize()
    {
        return round(UploadedFile::getMaxFilesize() / 1024);
    }

    public function getStorageDirectory()
    {
        if ($this->isPublic()) {
            return 'uploads/public/';
        }
        else {
            return 'uploads/protected/';
        }
    }

    public function getPublicPath()
    {
        if ($this->isPublic()) {
            return 'http://localhost/uploads/public/';
        }
        else {
            return 'http://localhost/uploads/protected/';
        }
    }

    public function getTempPath()
    {
        $path = temp_path() . '/uploads';

        if (!FileHelper::isDirectory($path)) {
            FileHelper::makeDirectory($path, 0777, true, true);
        }

        return $path;
    }

    protected function isLocalStorage()
    {
        return Storage::getDefaultDriver() == 'local';
    }

    protected function getPartitionDirectory()
    {
        return implode('/', array_slice(str_split($this->disk_name, 3), 0, 3)) . '/';
    }

    protected function getLocalRootPath()
    {
        return storage_path().'/app/public';
    }
}
