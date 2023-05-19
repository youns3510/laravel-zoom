<?php

namespace App\Services;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;

class ZoomSignatureValidator implements SignatureValidator
{
  public function isValid(Request $request, WebhookConfig $config): bool
  {

    $signingSecret = $config->signingSecret;

    if (empty($signingSecret)) {
      throw InvalidConfig::signingSecretNotSet();
    }

    $signatureHeaderName = $request->header($config->signatureHeaderName);
    $timestamp = $request->header('x-zm-request-timestamp');
    if (!$signatureHeaderName) {
      return false;
    }   


    $message = 'v0:' . $timestamp . ':' . $request->getContent();
    $hash = hash_hmac('sha256', $message, $signingSecret);
    $signature = "v0={$hash}";


    return hash_equals($signatureHeaderName, $signature);
  }
}
