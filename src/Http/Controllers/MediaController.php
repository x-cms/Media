<?php

namespace Xcms\Media\Http\Controllers;

use Illuminate\Http\Request;
use Xcms\Base\Http\Controllers\SystemController;
use Xcms\Media\Support\MediaManager;

class MediaController extends SystemController
{
    protected $manager;

    public function __construct()
    {
        parent::__construct();

        $this->middleware(function (Request $request, $next) {

            menu()->setActiveItem('pages');

            $this->breadcrumbs
                ->addLink('内容管理')
                ->addLink('媒体管理', route('media.index'));

            $media = new MediaManager('manager');

            $this->manager = $media;

            view()->share(compact('media'));

            return $next($request);
        });

    }

    public function index(Request $request)
    {
        if($request->isMethod('post')){
            $handler = $request->headers->get('x-october-request-handler');
            return $this->manager->$handler();
        }
        $this->setPageTitle('媒体管理');

        return view('media::index');
    }
}
