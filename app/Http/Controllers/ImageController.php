<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\ImageStoreRequest;
use App\Models\Image;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function store(ImageStoreRequest $request)
    {
        $path = $request->validated('path', '');
        $image = Functions::store_uploaded_image($request->image, $path);

        return [
            'image' => $image
        ];
    }

    public function destroy(Image $image)
    {
        $deleted = $image->delete();
        
        return [
            'deleted' => $deleted
        ];
    }
}
