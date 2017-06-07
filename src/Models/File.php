<?php

namespace Xcms\Media\Models;

use File as FileHelper;
use Config;
use Illuminate\Database\Eloquent\Model;
use Storage;

class File extends Model
{
    protected $table = 'files';

    protected $primaryKey = 'id';

    protected $guarded = [];

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

    public function getExtension()
    {
        return FileHelper::extension($this->name);
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

    protected function getDiskName()
    {
        if ($this->disk_name !== null)
            return $this->disk_name;

        $ext = strtolower($this->getExtension());
        $name = str_replace('.', '', uniqid(null, true));

        return $this->disk_name = $ext !== null ? $name.'.'.$ext : $name;
    }

    protected function isLocalStorage()
    {
        return Storage::getDefaultDriver() == 'local';
    }

    protected function copyLocalToStorage($localPath, $storagePath)
    {
        return Storage::put($storagePath, FileHelper::get($localPath), ($this->isPublic()) ? 'public' : null);
    }

    protected function getPartitionDirectory()
    {
        return implode('/', array_slice(str_split($this->disk_name, 3), 0, 3)) . '/';
    }

    protected function getLocalRootPath()
    {
        return storage_path().'/app/public';
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
}
