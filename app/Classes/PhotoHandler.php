<?php

namespace App\Classes;

use App\Models\File;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as FacadesResponse;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;

class PhotoHandler
{
    /**
     * Return image as response.
     *
     * default image : tonal_logo
     *
     * @param string $imagePath must start at public starage folder.
     * @param int $scale scale parcentage 1 to 100.
     */
    static function getAsResponse(string $public_id, int $scale = 100, ?string $default = null): Response
    {
        $media = File::firstWhere('pid', $public_id);

        $imagePath = null;
        if ($media) {
            $imagePath = FileHanderClass::TYPE_PATH[$media->type] . '/' . $media->hashed_name;
        } else {
            $imagePath = 'defaults/logo.png';

            $defaultExt = '.png';
            $defaultPath = 'defaults';
            $defaultValue = 'logo';
            $default = Str::replace('.', '/', $default);

            $defaultContent = $default ?: $defaultValue;
            $filePath = storage_path('app') . '/' . $defaultPath . '/' . $defaultContent . $defaultExt;

            if (file_exists($filePath)) {
                $imagePath = $defaultPath . '/' . $defaultContent . $defaultExt;
            }
        }

        # Casting scale -------------------------------------------------------------
        if ($scale > 100) $scale = 100 / 100; // 0.1
        elseif ($scale < 1) $scale = 100 / 100; // 0.1
        else $scale = $scale / 100;

        # Creating ------------------------------------------------------------------
        $img = (new ImageManager(new Driver))->read(storage_path('app') . '/private/' . $imagePath);
        $size = $img->size();

        # Scaling -------------------------------------------------------------------
        $image = $img->scale(width: $scale * $size->width(), height: $scale * $size->width());

        // create response and encode image data as response ------------------------
        $encoded = $image->encode(new AutoEncoder());
        $response =  FacadesResponse::make($encoded);

        // set content-type ---------------------------------------------------------
        $response->header('Content-Type', $encoded->mediaType());

        // output
        return $response;
    }

    /** Download a file. */
    static function download(string $public_id, string $mimeType = '*/*')
    {
        $media = File::wherePublic_id($public_id)->first();

        $path = storage_path('app/private/')
            . FileHanderClass::TYPE_PATH[$media->type]
            . '/'
            . $media->hashed_name;

        return Response::download(file: $path, name: $media->original_name . '.' . $media->ext, headers: ['Content-Type' => $mimeType]);
    }

    /** Return file as reponse. */
    static function fileAsResponse(string $public_id, string $mimeType = '*/*')
    {
        $media = File::wherePid($public_id)->first();

        if ($media) {
            $path = storage_path('app/private/')
                . FileHanderClass::TYPE_PATH[$media->type]
                . '/'
                . $media->hashed_name;

            return response()->file($path, ['Content-Type' => $mimeType]);
        }
    }
}
