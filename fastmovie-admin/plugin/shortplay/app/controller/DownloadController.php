<?php

namespace plugin\shortplay\app\controller;

use app\Basic;
use support\Cache;
use support\Request;

class DownloadController extends Basic
{
    public function storyboard(Request $request)
    {
        $uuid = $request->get('uuid');
        $cacheData = Cache::get('downloadPackage_' . $uuid);
        if (!$cacheData) {
            return not_found();
        }
        return response()->download($cacheData['filepath'] . $cacheData['filename'], $cacheData['filename']);
    }
}
