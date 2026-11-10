<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    public function store(UploadedFile $file, string $directory): string
    {
        $name = sha1(time().'_'.rand(1000000000, 1999999999).'_'.rand(1000000000, 1999999999).'_'.$file->getClientOriginalName());
        $name .= '.'.$file->extension();
        Storage::disk('local')->putFileAs($directory, $file, $name);

        return $name;
    }
}
