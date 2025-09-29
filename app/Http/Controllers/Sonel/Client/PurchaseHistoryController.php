<?php

namespace App\Http\Controllers\Sonel\Client;

use App\Classes\SimpleNavigatorHandler;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Hacp0012\Quest\Attributs\QuestSpaw;
use Hacp0012\Quest\QuestResponse;
use Hacp0012\Quest\SpawMethod;

class PurchaseHistoryController extends Controller
{
    #[QuestSpaw(ref: '5YVwM5XVM', method: SpawMethod::GET)]
    function getOlds(string $phone, int $page, int $pageSize, string $section = 'ALL')
    {
        new QuestResponse(ref: '5YVwM5XVM');
        $this->updateState($phone);

        $recents = Purchase::where('buyer', $phone)
            ->when($section == 'ALL', function ($query) {
                return $query
                    ->orWhere('state', 'FAILED')
                    ->orWhere('state', 'SUCCESS');
            })
            ->when($section == 'SUCCESS', function ($query) {
                return $query->where('state', 'SUCCESS');
            })
            ->when($section == 'SUCCESS', function ($query) {
                return $query->where('state', 'FAILED');
            })
            ->orderByDesc('created_at')
            ->get();

        $list = collect();
        foreach ($recents as $recent) {
            if ($recent->buyer == $phone) {
                $list->add($recent);
            }
        }

        return SimpleNavigatorHandler::pageNavigation(collection: $list, page: $page, pageSize: $pageSize);
    }

    #[QuestSpaw(ref: 'TJjVrnoNU', method: SpawMethod::GET)]
    function getInProcess(string $phone)
    {
        new QuestResponse(ref: 'TJjVrnoNU');
        $this->updateState($phone);

        $recents = Purchase::where('buyer', $phone)
            // ->orWhere('state', 'INWAIT')
            // ->orWhere('state', 'INWAIT_PAIEMENT')
            ->orderByDesc('created_at')
            ->get();

        $list = collect();
        foreach ($recents as $recent) {
            if ($recent->state == 'INWAIT' || $recent->state == 'INWAIT_PAIEMENT') {
                $list->add($recent);
            }
        }

        return $list;
    }

    #[QuestSpaw(ref: '0r8LarEEI', method: SpawMethod::GET)]
    function countInProcess(string $phone): int
    {
        new QuestResponse(ref: '0r8LarEEI');
        $this->updateState($phone);

        $recents = Purchase::where('buyer', $phone)
            // ->orWhere('state', 'INWAIT')
            // ->orWhere('state', 'INWAIT_PAIEMENT')
            ->orderByDesc('created_at')
            ->get();

        $list = collect();
        foreach ($recents as $recent) {
            if ($recent->state == 'INWAIT' || $recent->state == 'INWAIT_PAIEMENT') {
                $list->add($recent);
            }
        }

        return $list->count();
    }

    private function updateState(string $phone)
    {
        $recents = Purchase::where('state', 'INWAIT')
            ->where('buyer', $phone)
            ->where('expire_at', '<>', null)
            ->orWhere('state', 'INWAIT')
            ->orWhere('state', 'INWAIT_PAIEMENT')
            ->get();

        foreach ($recents as $recent) {
            if ($recent->expire_at->lessThan(now())) {
                $recent->state = 'FAILED';
                $recent->save();
            }
        }
    }

    #[QuestSpaw(ref: 'MN7oiFRPU')]
    function cancel() {}

    #[QuestSpaw(ref: '3Ym8JDmLP')]
    function remove() {}
}
