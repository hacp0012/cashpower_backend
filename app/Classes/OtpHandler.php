<?php

namespace App\Classes;

use App\Models\Otp;
use Illuminate\Support\Str;
use InnerOtpHandler\Generator as InnerOtpHandlerGenerator;
use InnerOtpHandler\Notifier;

class OtpHandler
{
  /**
   * Create a new class instance.
   */
  public function __construct()
  {
    $this->notify = new Notifier;
  }

  public Notifier $notify;

  public function generate(int $length = 4): InnerOtpHandlerGenerator
  {
    $generator = new InnerOtpHandlerGenerator;
    $generator->generate(length: $length);

    return $generator;
  }

  public function check(string|int $otp): bool
  {
    $this->invalidateExpireds();

    $result = Otp::where('otp', $otp)->first();

    if ($result) {
      $result->delete();
      return true;
    }

    return false;
  }

  /** Delete all expired otp's. */
  public function invalidateExpireds(): bool
  {
    $state = otp::where('expire_at', '<', time())->delete();
    return $state;
  }

  private function invalidateAllOf(string $ref): void
  {
    Otp::where('ref', $ref)->delete();
  }

  /** @return array [state:SENT|FAILED, otp, expire_at] */
  static public function sendSmsOtp(string $phoneCode, string $phoneNumber, ?string $message = null): array
  {
    $expireAt = time() + (60 * 3); // expire in 3Munites.
    $otpHandler = new OtpHandler;
    // SMS OTP MESSAGE :
    $otpMessageText = $message ?: env('OPT_SMS_TEMPLATE', "Collecta. \nVoici votre code: C-[OTP-CODE]");

    $generateOtp = $otpHandler->generate(length: 4);
    $generateOtp->store(for: $phoneCode . $phoneNumber, expirAt: $expireAt);

    $state = $otpHandler->notify->message(text: $otpMessageText, mask: '[OTP-CODE]', maskReplacer: $generateOtp->otp)->sendSMS(
      phoneNumber: $phoneCode . $phoneNumber,
    );

    // Invalidate all expired's.
    $otpHandler->invalidateExpireds();

    return ['state' => $state ? 'SENT' : 'FAILED', 'otp' => $generateOtp->otp, 'expire_at' => $expireAt];
  }
}

namespace InnerOtpHandler;

use App\Classes\SmsHandler as ClassesSmsHandler;
use App\core_system\Classes\SmsHandler;
use App\Models\Otp;
use Illuminate\Support\Str;

class Generator
{
  public string $otp;

  public function generate(int $length = 4): int
  {
    $generated = null;

    $start = '1';
    $end = '9'; { // Generable length.
      --$length;
      for ($len = 0; $len < $length; $len++) {
        $start .= '0';
        $end .= '9';
      }
    }

    while (true) {
      $generated = random_int(intval($start), intval($end));

      // Cheche if exist.
      $founden = Otp::where('otp', $generated)->first();

      if ($founden == null) break;
    }

    $this->otp = $generated;

    return $generated;
  }

  private function invalidateAllOf(string $ref): void
  {
    Otp::where('ref', $ref)->delete();
  }

  /** Store in DB. */
  public function store(string $for, ?int $expirAt = null): void
  {
    $otp = new Otp;

    $this->invalidateAllOf(ref: $for);

    $otp->otp = $this->otp;
    $otp->ref = $for;
    $otp->expire_at = $expirAt ?: time() + (60 * 3); // Default 3 Munites.

    $otp->save();
  }
}

class Notifier
{
  public ?string $messageText = null;

  /** Parse message and limite lenght (144 Chars). */
  public function message(string $text, ?string $mask = null, ?string $maskReplacer = null): Notifier
  {
    $maxLength = 144;

    if ($mask) $text = str_replace($mask, $maskReplacer, $text);

    if (Str::length($text) <= $maxLength) {
      $this->messageText = $text;
    } else {
      $this->messageText = Str::limit($text, $maxLength - 3, '...');
    }

    return $this;
  }

  public function sendSMS(string $phoneNumber): bool
  {
    return ClassesSmsHandler::send(sms: $this->messageText, phoneNumber: $phoneNumber);
  }
}
