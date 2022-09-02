<?php

namespace Vormkracht10\LaravelOpenGraphImage\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

class LaravelOpenGraphImageController
{
    public function __invoke(Request $request)
    {
        if (! app()->environment('local') && ! $request->hasValidSignature()) {
            abort(403);
        }

        $title = $request->title ?? config('app.name');
        $subtitle = $request->subtitle ?? null;
        $filename = Str::slug($title).'.jpg';

        $html = View::first([
            'vendor.open-graph-image.template',
            'open-graph-image.template',
            'template',
        ], compact('title', 'subtitle'))
        ->render();

        if ($request->route()->getName() == 'open-graph-image') {
            return $html;
        }

        if (! Storage::disk('public')->exists('social/open-graph/'.$filename)) {
            $this->saveOpenGraphImage($html, $filename);
        }

        return $this->getOpenGraphImageResponse($filename);
    }

    public function saveOpenGraphImage($html, $filename)
    {
        if (!File::isDirectory(storage_path('app/public/social/open-graph'))) {
            File::makeDirectory(storage_path('app/public/social/open-graph'), 0777, true);
        }

        $path = Storage::disk('public')
            ->path('social/open-graph/'.$filename);

        Browsershot::html($html)
            ->showBackground()
            ->windowSize(config('open-graph-image.image_width'), config('open-graph-image.image_height'))
            ->setScreenshotType(config('open-graph-image.image_type'), config('open-graph-image.image_quality'))
            ->save($path);
    }

    public function getOpenGraphImageResponse($filename)
    {
        return response(
            Storage::disk('public')->get('social/open-graph/'.$filename), 200, [
                'Content-Type' => 'image/jpeg',
            ]);
    }
}
