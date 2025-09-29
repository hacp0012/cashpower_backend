<?php

namespace App\Http\Controllers\Sonel\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\QuestResponse;
use Hacp0012\Quest\SpawMethod;

class ClientProfileController extends Controller
{
    #[QuestSpaw(ref: 'o4D1ivxoI')]
    function ping(string $phoneCode, string $phoneNumber): bool
    {
        new QuestResponse(ref: 'o4D1ivxoI');
        $exist = Client::firstWhere('phone', "$phoneCode,$phoneNumber");
        if ($exist) return true;

        return false;
    }

    #[QuestSpaw(ref: 'knQNwFisb')]
    function update(
        string $clientId,
        string $cNumber,
        string $clientName,
        string $phoneCode,
        string $phoneNumber,
        string $phoneNetwork,
        string $address,
    ): bool {
        new QuestResponse(ref: "knQNwFisb", dataName: 'success');
        $client = Client::find($clientId);
        if ($client) {
            $client->phone      = "$phoneCode,$phoneNumber";
            $client->name       = $clientName;
            $client->c_number   = $cNumber;
            $client->address    = $address;
            $client->provider   = $phoneNetwork;

            return $client->save();
        }

        return false;
    }

    #[QuestSpaw(ref: 'iV70RuoMg')]
    function remove(string $phoneCode, string $phoneNumber): bool
    {
        new QuestResponse(ref: 'iV70RuoMg');
        $client = Client::firstWhere('phone', "$phoneCode,$phoneNumber");
        if ($client) {
            return $client->delete();
        }

        return false;
    }

    #[QuestSpaw(ref: 'V7JUSG8ZM', method: SpawMethod::GET)]
    function get(string $phoneCode, string $phoneNumber)
    {
        new QuestResponse(ref: "V7JUSG8ZM");
        $client = Client::firstWhere('phone', "$phoneCode,$phoneNumber");
        if ($client) {
            return $client;
        }

        return null;
    }
}
