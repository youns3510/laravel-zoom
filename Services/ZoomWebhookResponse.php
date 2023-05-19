<?php

namespace App\Services;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Symfony\Component\HttpFoundation\Response;
use Spatie\WebhookClient\WebhookResponse\RespondsToWebhook;

class ZoomWebhookResponse implements RespondsToWebhook
{
  public function respondToValidWebhook(Request $request, WebhookConfig $config): Response
  {
    if (json_decode($request->getContent(), true)['event'] === 'endpoint.url_validation') {
      $token = json_decode($request->getContent(), true)['payload']['plainToken'];
      return response()->json([
        "plainToken" => $token,
        "encryptedToken" => hash_hmac('sha256', $token, $config->signingSecret)
      ]);
    }


    return response()->json(['message' => 'ok']);
  }
}
