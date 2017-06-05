<?php

namespace Xcms\Media\Support;

use Exception;
use File;
use Illuminate\Support\Str;
use Request;
use Response;
use URL;

class MediaManager
{
    const FOLDER_ROOT = '/';

    const VIEW_MODE_GRID = 'grid';
    const VIEW_MODE_LIST = 'list';
    const VIEW_MODE_TILES = 'tiles';

    const SELECTION_MODE_NORMAL = 'normal';
    const SELECTION_MODE_FIXED_RATIO = 'fixed-ratio';
    const SELECTION_MODE_FIXED_SIZE = 'fixed-size';

    const FILTER_EVERYTHING = 'everything';

    protected $brokenImageHash = null;

    /**
     * @var boolean Determines whether the bottom toolbar is visible.
     */
    public $bottomToolbar = false;

    /**
     * @var boolean Determines whether the Crop & Insert button is visible.
     */
    public $cropAndInsertButton = false;

    protected $mediaLibrary;

    /**
     * MediaManager constructor.
     * @param $alias
     */
    public function __construct($alias)
    {
        $this->alias = $alias;
        $this->checkUploadPostback();
    }

    /**
     * Renders the widget.
     * @return string
     */
    public function render()
    {
        $this->prepareVars();

        return view('media::partials.body')->render();
    }

    //
    // Event handlers
    //

    public function onSearch()
    {
        $this->setSearchTerm(Request::get('search'));

        $this->prepareVars();

        return [
            '#MediaManager-manager-item-list' => view('media::partials.item-list')->render(),
            '#MediaManager-manager-folder-path' => view('media::partials.folder-path')->render()
        ];
    }

    public function onGoToFolder()
    {
        $path = Request::get('path');

        if (Request::get('clearCache')) {
            MediaLibrary::instance()->resetCache();
        }

        if (Request::get('resetSearch')) {
            $this->setSearchTerm(null);
        }

        $this->setCurrentFolder($path);
        $this->prepareVars();

        return [
            '#MediaManager-manager-item-list' => view('media::partials.item-list')->render(),
            '#MediaManager-manager-folder-path' => view('media::partials.folder-path')->render()
        ];
    }

    public function onGenerateThumbnails()
    {
        $batch = Request::get('batch');
        if (!is_array($batch)) {
            return false;
        }

        $result = [];
        foreach ($batch as $thumbnailInfo) {
            $result[] = $this->generateThumbnail($thumbnailInfo);
        }

        return [
            'generatedThumbnails' => $result
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function onGetSidebarThumbnail()
    {
        $path = Request::get('path');
        $lastModified = Request::get('lastModified');

        $thumbnailParams = $this->getThumbnailParams();
        $thumbnailParams['width'] = 300;
        $thumbnailParams['height'] = 255;
        $thumbnailParams['mode'] = 'auto';

        $path = MediaLibrary::validatePath($path);

        if (!is_numeric($lastModified)) {
            throw new Exception('Invalid input data');
        }

        /*
         * If the thumbnail file exists, just return the thumbnail markup,
         * otherwise generate a new thumbnail.
         */
        $thumbnailPath = $this->thumbnailExists($thumbnailParams, $path, $lastModified);

        if ($thumbnailPath) {
            return [
                'markup' => view('media::partials.thumbnail-image', [
                    'isError' => $this->thumbnailIsError($thumbnailPath),
                    'imageUrl' => $this->getThumbnailImageUrl($thumbnailPath)
                ])->render()
            ];
        }

        $thumbnailInfo = $thumbnailParams;
        $thumbnailInfo['path'] = $path;
        $thumbnailInfo['lastModified'] = $lastModified;
        $thumbnailInfo['id'] = 'sidebar-thumbnail';

        return $this->generateThumbnail($thumbnailInfo, $thumbnailParams);
    }

    public function onChangeView()
    {
        $viewMode = Request::get('view');
        $path = Request::get('path');

        $this->setViewMode($viewMode);
        $this->setCurrentFolder($path);

        $this->prepareVars();

        return [
            '#MediaManager-manager-item-list' => view('media::partials.item-list')->render(),
            '#MediaManager-manager-folder-path' => view('media::partials.folder-path')->render(),
            '#MediaManager-manager-view-mode-buttons' => view('media::partials.view-mode-buttons')->render()
        ];
    }

    public function onSetFilter()
    {
        $filter = Request::get('filter');
        $path = Request::get('path');

        $this->setFilter($filter);
        $this->setCurrentFolder($path);

        $this->prepareVars();

        return [
            '#MediaManager-manager-item-list' => view('media::partials.item-list')->render(),
            '#MediaManager-manager-folder-path' => view('media::partials.folder-path')->render(),
            '#MediaManager-manager-filters' => view('media::partials.filters')->render()
        ];

    }

    public function onSetSorting()
    {
        $sortBy = Request::get('sortBy');
        $path = Request::get('path');

        $this->setSortBy($sortBy);
        $this->setCurrentFolder($path);

        $this->prepareVars();

        return [
            '#MediaManager-manager-item-list' => view('media::partials.item-list')->render(),
            '#MediaManager-manager-folder-path' => view('media::partials.folder-path')->render()
        ];
    }

    public function onDeleteItem()
    {
        $paths = Request::get('paths');

        if (!is_array($paths)) {
            throw new Exception('Invalid input data');
        }

        $library = MediaLibrary::instance();

        $filesToDelete = [];
        foreach ($paths as $pathInfo) {
            $path = array_get($pathInfo, 'path');
            $type = array_get($pathInfo, 'type');

            if (!$path || !$type) {
                throw new Exception('Invalid input data');
            }

            if ($type === MediaLibraryItem::TYPE_FILE) {
                /*
                 * Add to bulk collection
                 */
                $filesToDelete[] = $path;
            } else if ($type === MediaLibraryItem::TYPE_FOLDER) {
                /*
                 * Delete single folder
                 */
                $library->deleteFolder($path);

                /*
                 * Extensibility
                 */
//                $this->fireSystemEvent('media.folder.delete', [$path]);
            }
        }

        if (count($filesToDelete) > 0) {
            /*
             * Delete collection of files
             */
            $library->deleteFiles($filesToDelete);

            /*
             * Extensibility
             */
//            foreach ($filesToDelete as $path) {
//                $this->fireSystemEvent('media.file.delete', [$path]);
//            }
        }

        $library->resetCache();
        $this->prepareVars();

        return [
            '#MediaManager-manager-item-list' => view('media::partials.item-list')->render()
        ];
    }

    public function onLoadRenamePopup()
    {
        $path = Request::get('path');
        $path = MediaLibrary::validatePath($path);

        $originalPath = $path;
        $name = basename($path);
        $listId = Request::get('listId');
        $type = Request::get('type');

        return [
            'result' => view('media::partials.rename-form', compact('originalPath', 'name', 'listId', 'type'))->render()
        ];
    }

    public function onApplyName()
    {
        $newName = trim(Request::get('name'));
        if (!strlen($newName)) {
            throw new Exception('名称不能为空');
        }

        if (!$this->validateFileName($newName)) {
            throw new Exception('不合法的部件名: :name.');
        }

        if (!$this->validateFileType($newName)) {
            throw new Exception('由于安全原因，所使用的文件类型已被阻止.');
        }

        $originalPath = Request::get('originalPath');
        $originalPath = MediaLibrary::validatePath($originalPath);
        $newPath = dirname($originalPath) . '/' . $newName;
        $type = Request::get('type');

        if ($type == MediaLibraryItem::TYPE_FILE) {
            /*
             * Move single file
             */
            MediaLibrary::instance()->moveFile($originalPath, $newPath);

            /*
             * Extensibility
             */
//            $this->fireSystemEvent('media.file.rename', [$originalPath, $newPath]);
        } else {
            /*
             * Move single folder
             */
            MediaLibrary::instance()->moveFolder($originalPath, $newPath);

            /*
             * Extensibility
             */
//            $this->fireSystemEvent('media.folder.rename', [$originalPath, $newPath]);
        }

        MediaLibrary::instance()->resetCache();

        return [
            'X_OCTOBER_ASSETS' => [
                'rss' => []
            ]
        ];
    }

    public function onCreateFolder()
    {
        $name = trim(Request::get('name'));
        if (!strlen($name)) {
            throw new Exception('名称不能为空');
        }

        if (!$this->validateFileName($name)) {
            throw new Exception('名称只能包含数字, 拉丁字母, 空格和以下字符: _-');
        }

        if (!$this->validateFileType($name)) {
            throw new Exception('由于安全原因，所使用的文件类型已被阻止');
        }

        $path = Request::get('path');
        $path = MediaLibrary::validatePath($path);

        $newFolderPath = $path . '/' . $name;

        $library = MediaLibrary::instance();

        if ($library->folderExists($newFolderPath)) {
            throw new Exception('文件夹或文件已经存在.');
        }

        /*
         * Create the new folder
         */
        if (!$library->makeFolder($newFolderPath)) {
            throw new Exception('新建文件夹错误');
        }

        /*
         * Extensibility
         */
//        $this->fireSystemEvent('media.folder.create', [$newFolderPath]);

        $library->resetCache();

        $this->prepareVars();

        return [
            '#MediaManager-manager-item-list' => view('media::partials.item-list')->render()
        ];
    }

    public function onLoadMovePopup()
    {
        $exclude = Request::get('exclude', []);
        if (!is_array($exclude)) {
            throw new Exception('Invalid input data');
        }

        $folders = MediaLibrary::instance()->listAllDirectories($exclude);

        $folderList = [];
        foreach ($folders as $folder) {
            $path = $folder;

            if ($folder == '/') {
                $name = '库';
            } else {
                $segments = explode('/', $folder);
                $name = str_repeat('&nbsp;', (count($segments) - 1) * 4) . basename($folder);
            }

            $folderList[$path] = $name;
        }

        $folders = $folderList;
        $originalPath = Request::get('path');

        return [
            'result' => view('media::partials.move-form', compact('folders', 'originalPath'))->render()
        ];
    }

    public function onMoveItems()
    {
        $dest = trim(Request::get('dest'));
        if (!strlen($dest)) {
            throw new Exception('请选择目标文件夹.');
        }

        $dest = MediaLibrary::validatePath($dest);
        if ($dest == Request::get('originalPath')) {
            throw new Exception('请选择另一个目标文件夹.');
        }

        $files = Request::get('files', []);
        if (!is_array($files)) {
            throw new Exception('Invalid input data');
        }

        $folders = Request::get('folders', []);
        if (!is_array($folders)) {
            throw new Exception('Invalid input data');
        }

        $library = MediaLibrary::instance();

        foreach ($files as $path) {
            /*
             * Move a single file
             */
            $library->moveFile($path, $dest . '/' . basename($path));

            /*
             * Extensibility
             */
//            $this->fireSystemEvent('media.file.move', [$path, $dest]);
        }

        foreach ($folders as $path) {
            /*
             * Move a single folder
             */
            $library->moveFolder($path, $dest . '/' . basename($path));

            /*
             * Extensibility
             */
//            $this->fireSystemEvent('media.folder.move', [$path, $dest]);
        }

        $library->resetCache();

        $this->prepareVars();

        return [
            '#MediaManager-manager-item-list' => view('media::partials.item-list')->render()
        ];
    }

    public function onSetSidebarVisible()
    {
        $visible = Request::get('visible');

        $this->setSidebarVisible($visible);
    }

    public function onLoadPopup()
    {
        $this->bottomToolbar = Request::get('bottomToolbar', $this->bottomToolbar);

        $this->cropAndInsertButton = Request::get('cropAndInsertButton', $this->cropAndInsertButton);

        return view('popup-body')->render();
    }

    public function onLoadImageCropPopup()
    {
        $path = Request::get('path');
        $path = MediaLibrary::validatePath($path);
        $cropSessionKey = md5(Request::get('_session_key'));
        $selectionParams = $this->getSelectionParams();

        $urlAndSize = $this->getCropEditImageUrlAndSize($path, $cropSessionKey);
        $width = $urlAndSize['dimensions'][0];
        $height = $urlAndSize['dimensions'][1] ? $urlAndSize['dimensions'][1] : 1;

        $currentSelectionMode = $selectionParams['mode'];
        $currentSelectionWidth = $selectionParams['width'];
        $currentSelectionHeight = $selectionParams['height'];
        $imageUrl = $urlAndSize['url'];
        $dimensions = $urlAndSize['dimensions'];
        $originalRatio = round($width / $height, 5);

        return view('image-crop-popup-body', compact(
            'currentSelectionMode',
            'currentSelectionWidth',
            'currentSelectionHeight',
            'cropSessionKey',
            'imageUrl',
            'dimensions',
            'originalRatio',
            'path'
        ))->render();
    }

    public function onEndCroppingSession()
    {
        $cropSessionKey = Request::get('cropSessionKey');
        if (!preg_match('/^[0-9a-z]+$/', $cropSessionKey)) {
            throw new Exception('Invalid input data');
        }

        $this->removeCropEditDir($cropSessionKey);
    }

    public function onCropImage()
    {
        $imageSrcPath = trim(Request::get('img'));
        $selectionData = Request::get('selection');
        $cropSessionKey = Request::get('cropSessionKey');
        $path = Request::get('path');
        $path = MediaLibrary::validatePath($path);

        if (!strlen($imageSrcPath)) {
            throw new Exception('Invalid input data');
        }

        if (!preg_match('/^[0-9a-z]+$/', $cropSessionKey)) {
            throw new Exception('Invalid input data');
        }

        if (!is_array($selectionData)) {
            throw new Exception('Invalid input data');
        }

        $result = $this->cropImage($imageSrcPath, $selectionData, $cropSessionKey, $path);

        $selectionMode = Request::get('selectionMode');
        $selectionWidth = Request::get('selectionWidth');
        $selectionHeight = Request::get('selectionHeight');

        $this->setSelectionParams($selectionMode, $selectionWidth, $selectionHeight);

        return $result;
    }

    public function onResizeImage()
    {
        $cropSessionKey = Request::get('cropSessionKey');
        if (!preg_match('/^[0-9a-z]+$/', $cropSessionKey)) {
            throw new Exception('Invalid input data');
        }

        $width = trim(Request::get('width'));
        if (!strlen($width) || !ctype_digit($width)) {
            throw new Exception('Invalid input data');
        }

        $height = trim(Request::get('height'));
        if (!strlen($height) || !ctype_digit($height)) {
            throw new Exception('Invalid input data');
        }

        $path = Request::get('path');
        $path = MediaLibrary::validatePath($path);

        $params = array(
            'width' => $width,
            'height' => $height
        );

        return $this->getCropEditImageUrlAndSize($path, $cropSessionKey, $params);
    }

    //
    // Methods for th internal use
    //

    protected function prepareVars()
    {
        clearstatcache();

        $folder = $this->getCurrentFolder();
        $viewMode = $this->getViewMode();
        $filter = $this->getFilter();
        $sortBy = $this->getSortBy();
        $searchTerm = $this->getSearchTerm();
        $searchMode = strlen($searchTerm) > 0;
        $sortModes = [
            MediaLibrary::SORT_BY_TITLE => '标题',
            MediaLibrary::SORT_BY_SIZE => '大小',
            MediaLibrary::SORT_BY_MODIFIED => '最近修改'
        ];

        if (!$searchMode) {
            $items = $this->listFolderItems($folder, $filter, $sortBy);
        } else {
            $items = $this->findFiles($searchTerm, $filter, $sortBy);
        }

        $currentFolder = $folder;
        $isRootFolder = $folder == self::FOLDER_ROOT;
        $pathSegments = $this->splitPathToSegments($folder);
        $thumbnailParams = $this->getThumbnailParams($viewMode);
        $currentFilter = $filter;
        $sidebarVisible = $this->getSidebarVisible();
        return view()->share(compact(
            'items',
            'viewMode',
            'sortModes',
            'sortBy',
            'searchMode',
            'currentFolder',
            'isRootFolder',
            'pathSegments',
            'thumbnailParams',
            'currentFilter',
            'sidebarVisible'
        ));
    }

    protected function listFolderItems($folder, $filter, $sortBy)
    {
        $filter = $filter !== self::FILTER_EVERYTHING ? $filter : null;
        return MediaLibrary::instance()->listFolderContents($folder, $sortBy, $filter);
    }

    protected function findFiles($searchTerm, $filter, $sortBy)
    {
        $filter = $filter !== self::FILTER_EVERYTHING ? $filter : null;

        return MediaLibrary::instance()->findFiles($searchTerm, $sortBy, $filter);
    }

    protected function setCurrentFolder($path)
    {
        $path = MediaLibrary::validatePath($path);

        session()->put('media_folder', $path);
    }

    protected function getCurrentFolder()
    {
        $folder = session('media_folder', self::FOLDER_ROOT);

        return $folder;
    }

    protected function setFilter($filter)
    {
        if (!in_array($filter, [
            self::FILTER_EVERYTHING,
            MediaLibraryItem::FILE_TYPE_IMAGE,
            MediaLibraryItem::FILE_TYPE_AUDIO,
            MediaLibraryItem::FILE_TYPE_DOCUMENT,
            MediaLibraryItem::FILE_TYPE_VIDEO
        ])
        ) {
            throw new Exception('Invalid input data');
        }

        return session()->put('media_filter', $filter);
    }

    protected function getFilter()
    {
        return session('media_filter', self::FILTER_EVERYTHING);
    }

    protected function setSearchTerm($searchTerm)
    {
        session()->put('media_search', trim($searchTerm));
    }

    protected function getSearchTerm()
    {
        return session('media_search', null);
    }

    protected function setSortBy($sortBy)
    {
        if (!in_array($sortBy, [
            MediaLibrary::SORT_BY_TITLE,
            MediaLibrary::SORT_BY_SIZE,
            MediaLibrary::SORT_BY_MODIFIED
        ])
        ) {
            throw new Exception('Invalid input data');
        }

        return session()->put('media_sort_by', $sortBy);
    }

    protected function getSortBy()
    {
        return session('media_sort_by', MediaLibrary::SORT_BY_TITLE);
    }

    protected function getSelectionParams()
    {
        $result = session('media_crop_selection_params');

        if ($result) {
            if (!isset($result['mode'])) {
                $result['mode'] = MediaManager::SELECTION_MODE_NORMAL;
            }

            if (!isset($result['width'])) {
                $result['width'] = null;
            }

            if (!isset($result['height'])) {
                $result['height'] = null;
            }

            return $result;
        }

        return [
            'mode' => MediaManager::SELECTION_MODE_NORMAL,
            'width' => null,
            'height' => null
        ];
    }

    protected function setSelectionParams($selectionMode, $selectionWidth, $selectionHeight)
    {
        if (!in_array($selectionMode, [
            MediaManager::SELECTION_MODE_NORMAL,
            MediaManager::SELECTION_MODE_FIXED_RATIO,
            MediaManager::SELECTION_MODE_FIXED_SIZE
        ])
        ) {
            throw new Exception('Invalid input data');
        }

        if (strlen($selectionWidth) && !ctype_digit($selectionWidth)) {
            throw new Exception('Invalid input data');
        }

        if (strlen($selectionHeight) && !ctype_digit($selectionHeight)) {
            throw new Exception('Invalid input data');
        }

        return session()->put('media_crop_selection_params', [
            'mode' => $selectionMode,
            'width' => $selectionWidth,
            'height' => $selectionHeight
        ]);
    }

    protected function setSidebarVisible($visible)
    {
        return session()->put('sideba_visible', !!$visible);
    }

    protected function getSidebarVisible()
    {
        return session('sideba_visible', true);
    }

    public function itemTypeToIconClass($item, $itemType)
    {
        if ($item->type == MediaLibraryItem::TYPE_FOLDER) {
            return 'icon-folder';
        }

        switch ($itemType) {
            case MediaLibraryItem::FILE_TYPE_IMAGE:
                return "icon-picture-o";
            case MediaLibraryItem::FILE_TYPE_VIDEO:
                return "icon-video-camera";
            case MediaLibraryItem::FILE_TYPE_AUDIO:
                return "icon-volume-up";
            default:
                return "icon-file";
        }
    }

    protected function splitPathToSegments($path)
    {
        $path = MediaLibrary::validatePath($path, true);
        $path = explode('/', ltrim($path, '/'));

        $result = [];
        while (count($path) > 0) {
            $folder = array_pop($path);

            $result[$folder] = implode('/', $path) . '/' . $folder;
            if (substr($result[$folder], 0, 1) != '/') {
                $result[$folder] = '/' . $result[$folder];
            }
        }

        return array_reverse($result);
    }

    protected function setViewMode($viewMode)
    {
        if (!in_array($viewMode, [
            self::VIEW_MODE_GRID,
            self::VIEW_MODE_LIST,
            self::VIEW_MODE_TILES
        ])
        ) {
            throw new Exception('Invalid input data');
        }

        return session()->put('view_mode', $viewMode);
    }

    protected function getViewMode()
    {
        return session('view_mode', self::VIEW_MODE_GRID);
    }

    protected function getThumbnailParams($viewMode = null)
    {
        $result = [
            'mode' => 'crop',
            'ext' => 'png'
        ];

        if ($viewMode) {
            if ($viewMode == self::VIEW_MODE_LIST) {
                $result['width'] = 75;
                $result['height'] = 75;
            } else {
                $result['width'] = 165;
                $result['height'] = 165;
            }
        }

        return $result;
    }

    protected function getThumbnailImagePath($thumbnailParams, $itemPath, $lastModified)
    {
        $itemSignature = md5($itemPath) . $lastModified;

        $thumbFile = 'thumb_' .
            $itemSignature . '_' .
            $thumbnailParams['width'] . 'x' .
            $thumbnailParams['height'] . '_' .
            $thumbnailParams['mode'] . '.' .
            $thumbnailParams['ext'];

        $partition = implode('/', array_slice(str_split($itemSignature, 3), 0, 3)) . '/';

        $result = $this->getThumbnailDirectory() . $partition . $thumbFile;

        return $result;
    }

    public function getThumbnailImageUrl($imagePath)
    {
        return Url::to('/storage/temp' . $imagePath);
    }

    public function thumbnailExists($thumbnailParams, $itemPath, $lastModified)
    {
        $thumbnailPath = $this->getThumbnailImagePath($thumbnailParams, $itemPath, $lastModified);

        $fullPath = storage_path('app/public/temp/'.ltrim($thumbnailPath, '/'));

        if (File::exists($fullPath)) {
            return $thumbnailPath;
        }

        return false;
    }

    public function thumbnailIsError($thumbnailPath)
    {
        $fullPath = storage_path('app/public/temp/'.ltrim($thumbnailPath, '/'));

        return hash_file('crc32', $fullPath) == $this->getBrokenImageHash();
    }

    protected function getLocalTempFilePath($fileName)
    {
        $fileName = md5($fileName . uniqid() . microtime());

        $path = storage_path() . 'app/public/temp/media';

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
        }

        return $path . '/' . $fileName;
    }

    protected function getThumbnailDirectory()
    {
        return '/public/';
    }

    public function getPlaceholderId($item)
    {
        return 'placeholder' . md5($item->path . '-' . $item->lastModified . uniqid(microtime()));
    }

    /**
     * @param $thumbnailInfo
     * @param null $thumbnailParams
     * @return array
     */
    protected function generateThumbnail($thumbnailInfo, $thumbnailParams = null)
    {
        $tempFilePath = null;
        $fullThumbnailPath = null;
        $thumbnailPath = null;
        $markup = null;

        try {
            /*
             * Get and validate input data
             */
            $path = $thumbnailInfo['path'];
            $width = $thumbnailInfo['width'];
            $height = $thumbnailInfo['height'];
            $lastModified = $thumbnailInfo['lastModified'];

            if (!is_numeric($width) || !is_numeric($height) || !is_numeric($lastModified)) {
                throw new Exception('Invalid input data');
            }

            if (!$thumbnailParams) {
                $thumbnailParams = $this->getThumbnailParams();
                $thumbnailParams['width'] = $width;
                $thumbnailParams['height'] = $height;
            }

            $thumbnailPath = $this->getThumbnailImagePath($thumbnailParams, $path, $lastModified);
            $fullThumbnailPath = storage_path('app/public/temp/'.ltrim($thumbnailPath, '/'));

            /*
             * Save the file locally
             */
            $library = MediaLibrary::instance();
            $tempFilePath = $this->getLocalTempFilePath($path);

            if (!@File::put($tempFilePath, $library->get($path))) {
                throw new Exception('Error saving remote file to a temporary location');
            }

            /*
             * Resize the thumbnail and save to the thumbnails directory
             */
            $this->resizeImage($fullThumbnailPath, $thumbnailParams, $tempFilePath);

            /*
             * Delete the temporary file
             */
            File::delete($tempFilePath);
            $markup = view('media::partials.thumbnail-image', [
                'isError' => false,
                'imageUrl' => $this->getThumbnailImageUrl($thumbnailPath)
            ])->render();
        } catch (Exception $ex) {
            if ($tempFilePath) {
                File::delete($tempFilePath);
            }

            if ($fullThumbnailPath) {
                $this->copyBrokenImage($fullThumbnailPath);
            }

            $markup = view('media::thumbnail-image', ['isError' => true])->render();

            Log($ex->getMessage());
        }

        if ($markup && ($id = $thumbnailInfo['id'])) {
            return [
                'id' => $id,
                'markup' => $markup
            ];
        }
    }

    protected function resizeImage($fullThumbnailPath, $thumbnailParams, $tempFilePath)
    {
        $thumbnailDir = dirname($fullThumbnailPath);
        if (!File::isDirectory($thumbnailDir)) {
            if (File::makeDirectory($thumbnailDir, 0777, true) === false) {
                throw new Exception('Error creating thumbnail directory');
            }
        }

        $targetDimensions = $this->getTargetDimensions($thumbnailParams['width'], $thumbnailParams['height'], $tempFilePath);

        $targetWidth = $targetDimensions[0];
        $targetHeight = $targetDimensions[1];

        Resizer::open($tempFilePath)
            ->resize($targetWidth, $targetHeight, [
                'mode' => $thumbnailParams['mode'],
                'offset' => [0, 0]
            ])
            ->save($fullThumbnailPath);

        File::chmod($fullThumbnailPath);
    }

    protected function getBrokenImagePath()
    {
        return dirname(__DIR__) . '/assets/images/broken-thumbnail.gif';
    }

    protected function getBrokenImageHash()
    {
        if ($this->brokenImageHash) {
            return $this->brokenImageHash;
        }

        $fullPath = $this->getBrokenImagePath();
        return $this->brokenImageHash = hash_file('crc32', $fullPath);
    }

    protected function copyBrokenImage($path)
    {
        try {
            $thumbnailDir = dirname($path);
            if (!File::isDirectory($thumbnailDir)) {
                if (File::makeDirectory($thumbnailDir, 0777, true) === false)
                    return;
            }
            File::copy($this->getBrokenImagePath(), $path);
        } catch (Exception $ex) {
            Log($ex->getMessage());
        }
    }

    protected function getTargetDimensions($width, $height, $originalImagePath)
    {
        $originalDimensions = [$width, $height];

        try {
            $dimensions = getimagesize($originalImagePath);
            if (!$dimensions) {
                return $originalDimensions;
            }

            if ($dimensions[0] > $width || $dimensions[1] > $height) {
                return $originalDimensions;
            }

            return $dimensions;
        } catch (Exception $ex) {
            return $originalDimensions;
        }
    }

    protected function checkUploadPostback()
    {
        $fileName = null;
        $quickMode = false;

        if (
            !($uniqueId = Request::header('X-OCTOBER-FILEUPLOAD')) &&
            (!$quickMode = Request::header('X_OCTOBER_MEDIA_MANAGER_QUICK_UPLOAD'))
        ) {
            return false;
        }

        try {
            if (!Request::hasFile('file_data')) {
                throw new Exception('File missing from request');
            }

            $uploadedFile = Request::file('file_data');

            $fileName = $uploadedFile->getClientOriginalName();

            /*
             * Convert uppcare case file extensions to lower case
             */
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            $fileName = File::name($fileName) . '.' . $extension;

            /*
             * File name contains non-latin characters, attempt to slug the value
             */
            if (!$this->validateFileName($fileName)) {
                $fileNameClean = $this->cleanFileName(File::name($fileName));
                $fileName = $fileNameClean . '.' . $extension;
            }

            /*
             * Check for unsafe file extensions
             */
            if (!$this->validateFileType($fileName)) {
                throw new Exception('由于安全原因,所使用的文件类型已被阻止.');
            }

            /*
             * See mime type handling in the asset manager
             */
            if (!$uploadedFile->isValid()) {
                throw new Exception($uploadedFile->getErrorMessage());
            }

            $path = $quickMode ? '/uploaded-files' : Request::get('path');
            $path = MediaLibrary::validatePath($path);
            $filePath = $path . '/' . $fileName;

            MediaLibrary::instance()->put(
                $filePath,
                File::get($uploadedFile->getRealPath())
            );

            /*
             * Extensibility
             */
//            $this->fireSystemEvent('media.file.upload', [$filePath, $uploadedFile]);

            Response::json([
                'link' => MediaLibrary::url($filePath),
                'result' => 'success'
            ])->send();
        } catch (Exception $ex) {
            Response::json($ex->getMessage(), 400)->send();
        }

        exit;
    }

    /**
     * Validate a proposed media item file name.
     * @param string
     * @return bool
     */
    protected function validateFileName($name)
    {
        if (!preg_match('/^[0-9a-z@\.\s_\-]+$/i', $name)) {
            return false;
        }

        if (strpos($name, '..') !== false) {
            return false;
        }

        return true;
    }

    /**
     * Check for blocked / unsafe file extensions
     * @param string
     * @return bool
     */
    protected function validateFileType($name)
    {
        $extension = strtolower(File::extension($name));

        $blockedFileTypes = [
            'asp',
            'avfp',
            'aspx',
            'cshtml',
            'cfm',
            'go',
            'gsp',
            'hs',
            'jsp',
            'ssjs',
            'js',
            'lasso',
            'lp',
            'op',
            'lua',
            'p',
            'cgi',
            'ipl',
            'pl',
            'php',
            'php3',
            'php4',
            'phtml',
            'py',
            'rhtml',
            'rb',
            'rbw',
            'smx',
            'tcl',
            'dna',
            'tpl',
            'r',
            'w',
            'wig'
        ];

        if (in_array($extension, $blockedFileTypes)) {
            return false;
        }

        return true;
    }

    /**
     * Creates a slug form the string. A modified version of Str::slug
     * with the main difference that it accepts @-signs
     * @param string
     * @return string
     */
    protected function cleanFileName($name)
    {
        $title = Str::ascii($name);

        // Convert all dashes/underscores into separator
        $flip = $separator = '-';
        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);

        // Remove all characters that are not the separator, letters, numbers, whitespace or @.
        $title = preg_replace('![^' . preg_quote($separator) . '\pL\pN\s@]+!u', '', mb_strtolower($title));

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);

        return trim($title, $separator);
    }

    //
    // Cropping
    //

    protected function getCropSessionDirPath($cropSessionKey)
    {
        return $this->getThumbnailDirectory() . 'edit-crop-' . $cropSessionKey;
    }

    protected function getCropEditImageUrlAndSize($path, $cropSessionKey, $params = null)
    {
        $sessionDirectoryPath = $this->getCropSessionDirPath($cropSessionKey);
        $fullSessionDirectoryPath = storage_path('app/public/temp/'.$sessionDirectoryPath);
        $sessionDirectoryCreated = false;

        if (!File::isDirectory($fullSessionDirectoryPath)) {
            File::makeDirectory($fullSessionDirectoryPath, 0777, true, true);
            $sessionDirectoryCreated = true;
        }

        $tempFilePath = null;

        try {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $library = MediaLibrary::instance();
            $originalThumbFileName = 'original.' . $extension;

            /*
             * If the target dimensions are not provided, save the original image to the
             * crop session directory and return its URL.
             */
            if (!$params) {
                $tempFilePath = $fullSessionDirectoryPath . '/' . $originalThumbFileName;

                if (!@File::put($tempFilePath, $library->get($path))) {
                    throw new Exception('Error saving remote file to a temporary location.');
                }

                $url = $this->getThumbnailImageUrl($sessionDirectoryPath . '/' . $originalThumbFileName);
                $dimensions = getimagesize($tempFilePath);

                return [
                    'url' => $url,
                    'dimensions' => $dimensions
                ];
            } /*
             * If the target dimensions are provided, resize the original image and
             * return its URL and dimensions.
             */
            else {

                $originalFilePath = $fullSessionDirectoryPath . '/' . $originalThumbFileName;
                if (!File::isFile($originalFilePath)) {
                    throw new Exception('The original image is not found in the cropping session directory.');
                }

                $resizedThumbFileName = 'resized-' . $params['width'] . '-' . $params['height'] . '.' . $extension;
                $tempFilePath = $fullSessionDirectoryPath . '/' . $resizedThumbFileName;

                Resizer::open($originalFilePath)
                    ->resize($params['width'], $params['height'], [
                        'mode' => 'exact'
                    ])
                    ->save($tempFilePath);

                $url = $this->getThumbnailImageUrl($sessionDirectoryPath . '/' . $resizedThumbFileName);
                $dimensions = getimagesize($tempFilePath);

                return [
                    'url' => $url,
                    'dimensions' => $dimensions
                ];
            }
        } catch (Exception $ex) {
            if ($sessionDirectoryCreated) {
                @File::deleteDirectory($fullSessionDirectoryPath);
            }

            if ($tempFilePath) {
                File::delete($tempFilePath);
            }

            throw $ex;
        }
    }

    protected function removeCropEditDir($cropSessionKey)
    {
        $sessionDirectoryPath = $this->getCropSessionDirPath($cropSessionKey);
        $fullSessionDirectoryPath = storage_path('app/public/temp/'.$sessionDirectoryPath);

        if (File::isDirectory($fullSessionDirectoryPath)) {
            @File::deleteDirectory($fullSessionDirectoryPath);
        }
    }

    protected function cropImage($imageSrcPath, $selectionData, $cropSessionKey, $path)
    {
        $originalFileName = basename($path);

        $path = rtrim(dirname($path), '/') . '/';
        $fileName = basename($imageSrcPath);

        if (
            strpos($fileName, '..') !== false ||
            strpos($fileName, '/') !== false ||
            strpos($fileName, '\\') !== false
        ) {
            throw new Exception('Invalid image file name.');
        }

        $selectionParams = ['x', 'y', 'w', 'h'];

        foreach ($selectionParams as $paramName) {
            if (!array_key_exists($paramName, $selectionData)) {
                throw new Exception('Invalid selection data.');
            }

            if (!is_numeric($selectionData[$paramName])) {
                throw new Exception('Invalid selection data.');
            }

            $selectionData[$paramName] = (int)$selectionData[$paramName];
        }

        $sessionDirectoryPath = $this->getCropSessionDirPath($cropSessionKey);
        $fullSessionDirectoryPath = storage_path('app/public/temp/'.$sessionDirectoryPath);

        if (!File::isDirectory($fullSessionDirectoryPath)) {
            throw new Exception('The image editing session is not found.');
        }

        /*
         * Find the image on the disk and resize it
         */
        $imagePath = $fullSessionDirectoryPath . '/' . $fileName;
        if (!File::isFile($imagePath)) {
            throw new Exception('The image is not found on the disk.');
        }

        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);

        $targetImageName = basename($originalFileName, '.' . $extension) . '-'
            . $selectionData['x'] . '-'
            . $selectionData['y'] . '-'
            . $selectionData['w'] . '-'
            . $selectionData['h'] . '-';

        $targetImageName .= time();
        $targetImageName .= '.' . $extension;

        $targetTmpPath = $fullSessionDirectoryPath . '/' . $targetImageName;

        /*
         * Crop the image, otherwise copy original to target destination.
         */
        if ($selectionData['w'] == 0 || $selectionData['h'] == 0) {
            File::copy($imagePath, $targetTmpPath);
        } else {
            Resizer::open($imagePath)
                ->crop(
                    $selectionData['x'],
                    $selectionData['y'],
                    $selectionData['w'],
                    $selectionData['h'],
                    $selectionData['w'],
                    $selectionData['h']
                )
                ->save($targetTmpPath);
        }

        /*
         * Upload the cropped file to the Library
         */
        $targetFolder = $path . 'cropped-images';
        $targetPath = $targetFolder . '/' . $targetImageName;

        $library = MediaLibrary::instance();
        $library->put($targetPath, file_get_contents($targetTmpPath));

        return [
            'publicUrl' => $library->getPathUrl($targetPath),
            'documentType' => MediaLibraryItem::FILE_TYPE_IMAGE,
            'itemType' => MediaLibraryItem::TYPE_FILE,
            'path' => $targetPath,
            'title' => $targetImageName,
            'folder' => $targetFolder
        ];
    }
}
