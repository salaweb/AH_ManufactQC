<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    public function show(Photo $photo): StreamedResponse
    {
        abort_unless(Storage::disk('photos')->exists($photo->path), 404);

        return Storage::disk('photos')->response($photo->path);
    }
}
