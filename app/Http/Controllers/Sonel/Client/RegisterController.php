<?php

namespace App\Http\Controllers\Sonel\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\QuestResponse;

class RegisterController extends Controller
{
    #[QuestSpaw(ref: 'QUQkkYoTF')]
    function check(string $phoneCode, string $phoneNumber): string
    {
        new QuestResponse(ref: 'QUQkkYoTF', dataName: 'state');

        $phone = Client::firstWhere('phone', "$phoneCode,$phoneNumber");
        if ($phone) {
            return 'EXIST';
        }

        return 'NOT_EXIST';
    }

    #[QuestSpaw(ref: 'Ar1O8GYfq')]
    function register(
        string $cNumber,
        string $clientName,
        string $phoneCode,
        string $phoneNumber,
        string $phoneNetwork,
        string $address,
    ): bool {
        $res = new QuestResponse(ref: 'Ar1O8GYfq', dataName: 'success');

        $state = $this->check(phoneCode: $phoneCode, phoneNumber: $phoneNumber);
        if ($state == 'NOT_EXIST') {
            $created = Client::create([
                'phone'     => "$phoneCode,$phoneNumber",
                'name'      => $clientName,
                'c_number'  => $cNumber,
                'address'   => $address,
                'provider'  => $phoneNetwork,
            ]);

            if ($created) {
                $res->setData('created', $created->toArray());
                return true;
            }
        }

        return false;
    }
}
