<?php

namespace App\Classes;

use App\Jobs\FcmSendProcess;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kedniko\FCM\FCM;

class FcmNotificationHandler
{
    private int $maxUserDevices = 2;
    private string $projectID = 'cfcmedia-1df42';
    private $authKeyContent;
    private string $bearerToken;
    private bool $isQueued = false;

    /**
     * Create a new class instance.
     */
    public function __construct(protected bool $userBasicSendMode = false)
    {
        $this->authKeyContent = json_decode(file_get_contents(base_path('cfcmedia-1df42-firebase-adminsdk-fbsvc-ac12ddac31.json')), true);
        $this->bearerToken = FCM::getBearerToken($this->authKeyContent);
    }

    private function getUserDeviceToken(string $userId): array
    {
        $sessions = DB::table('personal_access_tokens')
            ->where('tokenable_type', '=', User::class)
            ->where('tokenable_id', '=', $userId)
            ->where('notification_token', '<>', null)
            ->orderByDesc('last_used_at')
            ->get(['notification_token'])
            ->slice(0, $this->maxUserDevices);

        $tokens = [];
        foreach ($sessions as $session) {
            if (in_array($session->notification_token, $tokens) == false) {
                $tokens[] = $session->notification_token;
            }
        }

        return $tokens;
    }

    function notifyUser(string $userId, string $title, string $message, string|null $image = null, string|null $icon = null, array $payload = [], ?string $link = null): void
    {
        # SEND TO QUEUE.
        if ($this->isQueued) {
            FcmSendProcess::dispatch(
                [$userId],
                $title,
                $message,
                $image,
                $icon,
                $payload,
                $link,
            );

            return;
        }

        # DIRECT SEND.
        $userTokens = $this->getUserDeviceToken(userId: $userId);

        foreach ($userTokens as $deviceToken) {
            if ($this->userBasicSendMode) {
                $response = Http::withHeaders([
                    'Content-Type'  => 'application/json',
                    'Authorization' => "Bearer " . $this->bearerToken,
                ])->post("https://fcm.googleapis.com/v1/projects/" . $this->projectID . "/messages:send", [
                    "message" => [
                        'token' => $deviceToken,
                        "notification" => [
                            "title" => $title,
                            "body"  => $message,
                            "image" => $image,
                        ],
                        "data" => $payload,
                        "webpush" => [
                            "fcm_options" => [
                                "link" => $link ?: 'https://cfc-media.org/app/#/CUMO27YRZ',
                            ],
                            "data" => $payload,
                        ],
                    ]
                ]);
                // Log::alert($bearerToken);
                // Log::alert($response);
            } else {
                $notificationRequestBody = [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => [
                            'title' => $title,
                            'body'  => $message,
                            "image" => $image,
                        ],
                        'data' => $payload,
                        "webpush" => [
                            "fcm_options" => [
                                "link" => $link ?: 'https://cfc-media.org/app/#/CUMO27YRZ',
                            ],
                        ],
                    ],
                ];

                FCM::send($this->bearerToken, $this->projectID, $notificationRequestBody);
            }
        }
    }

    function notifyUsers(array $usersIds, string $title, string $message, string|null $image = null, string|null $icon = null, array $payload = [], ?string $link = null): void
    {
        # SEND TO QUEUE.
        if ($this->isQueued) {
            FcmSendProcess::dispatch(
                $usersIds,
                $title,
                $message,
                $image,
                $icon,
                $payload,
                $link,
            );

            return;
        }

        # DIRECT SEND.
        foreach ($usersIds as $userId) {
            $this->notifyUser(userId: $userId, title: $title, message: $message, image: $image, icon: $icon, payload: $payload, link: $link);
        }
    }

    # SEND VIA QUEUE.
    function dispatchOnQueue(): FcmNotificationHandler
    {
        $this->isQueued = true;
        return $this;
    }
}
