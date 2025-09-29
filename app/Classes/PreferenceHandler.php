<?php

namespace App\Classes;

use App\Models\Preference;

class PreferenceHandler
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    /** @return mixed can be NULL */
    static public function get(string $key): mixed
    {
        $data = Preference::where('key', $key)->first();

        $casted = $data?->value['data'];

        return $casted;
    }

    static public function contain(string $key): bool
    {
        $isContain = Preference::where('key', $key)->first();
        return $isContain != null ? true : false;
    }

    static public function set(string $key, mixed $data): bool
    {
        $state = false;

        if (!PreferenceHandler::contain($key)) {
            $state = Preference::where('key', $key)->update(['value' => ['data' => $data]]);
        }

        return $state;
    }

    static function setOrCreate(string $key, mixed $data): bool
    {
        $state = false;

        if (PreferenceHandler::contain($key)) {
            $state = Preference::where('key', $key)->update(['value' => ['data' => $data]]);
        } else {
            $created = Preference::create([
                'key'           => $key,
                'value'         => ['data' => $data],
            ]);

            $state = $created ? true : false;
        }

        return $state;
    }
}
