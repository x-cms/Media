<?php

return [
    'storage' => [

        'uploads' => [
            'disk'   => 'local',
            'folder' => 'public/uploads',
            'path'   => '/storage/app/public/uploads',
        ],

        'media' => [
            'disk'   => 'local',
            'folder' => 'public/media',
            'path'   => '/storage/app/public/media',
            'imageExtensions' => ['jpg', 'jpeg', 'bmp', 'png', 'gif', 'svg'],
            'videoExtensions' => ['mp4', 'avi', 'mov', 'mpg', 'mpeg', 'mkv', 'webm'],
            'audioExtensions' => ['mp3', 'wav', 'wma', 'm4a', 'ogg'],
            'ignore' => ['.svn', '.git', '.DS_Store', '.AppleDouble']
        ],

    ],
];