<?php

namespace App\Classes;


use App\Models\File as ModelsFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class FileHanderClass
{
    /** @var array{VIDEO:string,IMAGE:string,USER:string,AUDIO:string,DOCUMENT:string} */
    const TYPE = ['VIDEO' => 'VIDEO', 'IMAGE' => 'IMAGE', 'DOCUMENT' => 'DOCUMENT', 'AUDIO' => 'AUDIO'];

    /** @var array{VIDEO:string,IMAGE:string,DOCUMENT:string,FILE:string} */
    const TYPE_PATH = ['VIDEO' => 'videos', 'IMAGE' => 'photos', 'DOCUMENT' => 'documents', 'AUDIO' => 'audios'];

    /**
     * @return bool if `content_group` & `owner_group` not match, false will be returned.
     */
    public static function store(
        UploadedFile $document,
        string $type,
        string $ownerId,
        ?string $ref        = null,
        ?string $label      = null,
        ?string &$public_id = null,
    ): bool {
        // image name : toLower(owner-content-hash.xyz)

        # Stop if content group not match.
        if (isset(FileHanderClass::TYPE_PATH[Str::upper($type)]) == false) return false;

        # Storing
        $original_name = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
        $mimeType = $document->getMimeType();
        $file_ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
        $file_size = (int) $document->getSize();
        $hashedName = $document->hashName();
        $documentName = Str::lower($type) . '-' . $hashedName;
        $document->storeAs(FileHanderClass::TYPE_PATH[Str::upper($type)], $documentName);

        # Registered
        $_public_id = Str::random(9);
        $_media = new ModelsFile;

        $_media->owner = $ownerId;
        $_media->pid = $_public_id;

        $_media->type = Str::upper($type);
        $_media->hashed_name = $documentName;
        $_media->original_name = $original_name;
        $_media->ext = $file_ext;
        $_media->size = $file_size;
        $_media->mime = $mimeType;


        // $_media->owner_group = $ref;
        // $_media->content_group = $contentGroup ? Str::upper($contentGroup) : null;
        $_media->content_group = $ref;
        if ($label) $_media->label = $label;

        $state = $_media->save();

        if ($state) $public_id = $_public_id;

        return $state;
    }

    static function replace(
        UploadedFile $document,
        string $type,
        string $id,
        ?string $label = null,
        ?string &$new_public_id = null
    ): bool {
        // image name : toLower(owner-content-hash.xyz)

        $imageFile = $document;

        $_medias = ModelsFile::find($id);
        if ($_medias != null) {
            # Geting data
            $ownerGroup = $_medias->owner_group;
            $documentOldName = $_medias->hashed_name;
            $contentGroup = $_medias->content_group;

            # Stop if content group not match.
            if (isset(FileHanderClass::TYPE_PATH[Str::upper($type)]) == false) return false;

            # Storing
            $original_name = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
            $file_ext = pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION);
            $mimeType = $document->getMimeType();
            $file_size = (int) $document->getSize();
            $hashedName = $imageFile->hashName();
            $documentNewName = Str::lower($type) . '-' . Str::lower($ownerGroup) . ($contentGroup ? ('-' . Str::lower($contentGroup)) : '') . '-' . $hashedName;
            $imageFile->storeAs(FileHanderClass::TYPE_PATH[Str::upper($type)], $documentNewName);

            # Registered
            $public_id = Str::random(9);

            $_medias->pid = $public_id;

            $_medias->hashed_name = $documentNewName;

            $_medias->original_name = $original_name;
            $_medias->ext = $file_ext;
            $_medias->size = $file_size;
            $_medias->mime = $mimeType;

            if ($label) $_medias->label = $label;

            # Save
            $dbSaveState = $_medias->save();

            if ($dbSaveState) $new_public_id = $public_id;

            # Deletion
            $deletionState = Storage::delete(FileHanderClass::TYPE_PATH[Str::upper($type)] . '/' . $documentOldName);

            return $deletionState;
        }

        return false;
    }

    public static function updateLabel(string $publicId, string $label): bool
    {
        $query = [];

        // if ($price)     $query['price']     = $price;
        // if ($currency)  $query['currency']  = $currency;
        if ($label)     $query['label']     = $label;
        // if ($mask)      $query['mask']      = isset(FileHanderClass::MASKS_PATH[Str::upper($mask)]) ? Str::upper($mask) : null;

        $state = ModelsFile::wherePid($publicId)->update($query);

        return $state;
    }

    static function destroy(?string $id = null, ?string $publicId = null): bool
    {
        if ($id == null && $publicId == null) return false;

        $media = $id
            ? ModelsFile::find($id)
            : ModelsFile::wherePid($publicId)->first();

        if ($media) {
            # Geting data
            $type = $media->type;
            $documentName = $media->hashed_name;

            # Deletion
            Storage::delete(
                FileHanderClass::TYPE_PATH[Str::upper($type)]
                    . '/'
                    . $documentName
            );

            # Unregister
            return $media->delete();
        }

        return false;
    }

    # GETTING.
    public static function get(string $owner, ?string $ref = null): Collection
    {
        $query = ['owner' => $owner];

        if ($ref) $query['content_group'] = $ref;

        $medias = ModelsFile::where($query)->get();

        return $medias;
    }

    public static function getByPublicId(string $publicId): ?ModelsFile
    {
        $documents = ModelsFile::wherePid($publicId)->get();
        return $documents->first();
    }

    /** Validate file */
    public static function validate(string $type, UploadedFile $uploadedFile, array|null $customRules = null): UploadedFile|null
    {
        if (isset(FileHanderClass::TYPE_PATH[$type]) == false) return null;

        // if ($request->hasFile($name)) {
        // TODO: add more Videos and Audio mimes types and remove un suporteds Documents formats.
        $rules = match ($type) {
            'VIDEO' => [File::types(['video/mp4'])],
            'AUDIO' => [File::types(['audio/mp3', 'audio/aac', 'audio/mpeg', "audio/x-m4a", "audio/mp4", "audio/x-ms-wma", "audio/x-alac-m4a", "audio/flac", "audio/x-flac", "audio/aiff", "audio/x-aiff", "audio/wav", "audio/x-wa"])],
            'IMAGE' || 'USER' => [File::types(['image/jpeg', 'image/pipeg', 'image/png']), File::image()->max(Constants::IMAGE_UPLOAD_SIZE)],
            'DOCUMENT' => [File::types(['application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])],

            default => [],
        };

        if ($customRules) $rules = $customRules;

        # Cancel if size is great than 18Mb.
        if ($type == 'VIDEO' && $uploadedFile->getSize() > (1024 * 18000)) return null;
        # Cancel if size is great than 81Mb.
        if ($type == 'AUDIO' && $uploadedFile->getSize() > (1024 * 81000)) return null;
        # Cancel if size is great than 18Mb.
        if ($type == 'DOCUMENT' && $uploadedFile->getSize() > (1024 * 18000)) return null;

        # Validate.
        $validate = Validator::validate(['file' => $uploadedFile], [
            'file' => [
                'required',
                'file',
                ...$rules,
                // File::types($mimeType),
                // File::image()
                //   ->min(1024)
                //   ->max(12 * 1024)
                //   ->dimensions(Rule::dimensions()->maxWidth(1000)->maxHeight(500)),
            ],
        ]);

        return $validate['file'];
        // } else return null;
    }
}
