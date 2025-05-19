<?php

namespace App\Services\Processors;

use Intervention\Image\Facades\Image;

class JpgOptimizer
{
    public function handle(string $file): string
    {
        $path = storage_path('app/' . $file);
        $image = Image::make($path)->resize(800, null, function ($constraint){
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $image->save($path, 75);
        return "Optimized image $file";
    }
}