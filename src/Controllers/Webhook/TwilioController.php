<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Controllers\Webhook;

use Boneblaze\SignupLfchdOrg\Services\SmsContactService;
use Twilio\Security\RequestValidator;

final class TwilioController
{
    public function incoming(): void
    {
        if (!$this->isValidTwilioRequest()) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            return;
        }

        $from = trim((string) ($_POST['From'] ?? ''));
        $optOutType = strtoupper(
            trim((string) ($_POST['OptOutType'] ?? ''))
        );

        $body = strtoupper(
            trim((string) ($_POST['Body'] ?? ''))
        );

        if ($optOutType === '') {
            $optOutKeywords = [
                'STOP',
                'UNSUBSCRIBE',
                'END',
                'QUIT',
                'STOPALL',
                'CANCEL',
                'REVOKE',
                'OPTOUT',
            ];

            $optInKeywords = [
                'START',
                'UNSTOP',
            ];

            $helpKeywords = [
                'HELP',
                'INFO',
                'SUPPORT',
            ];

            if (in_array($body, $optOutKeywords, true)) {
                $optOutType = 'STOP';
            } elseif (in_array($body, $optInKeywords, true)) {
                $optOutType = 'START';
            } elseif (in_array($body, $helpKeywords, true)) {
                $optOutType = 'HELP';
            }
        }

        if ($from !== '' && $optOutType !== '') {
            $contacts = new SmsContactService();

            if ($optOutType === 'STOP') {
                $contacts->markOptedOut(
                    $from,
                    'twilio_stop'
                );
            } elseif ($optOutType === 'START') {
                $contacts->markOptedIn($from);
            }

            /*
             * HELP does not change the stored SMS permission.
             *
             * With Twilio Advanced Opt-Out enabled, Twilio has already
             * interpreted STOP/START/HELP and sent its configured reply.
             * We intentionally do not send a second SMS response here.
             */
        }

        http_response_code(200);
        header('Content-Type: text/xml; charset=utf-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Response></Response>';
    }

    private function isValidTwilioRequest(): bool
    {
        $authToken =
            trim((string) ($_ENV['TWILIO_AUTH_TOKEN'] ?? ''));

        $appUrl =
            rtrim(
                trim((string) ($_ENV['APP_URL'] ?? '')),
                '/'
            );

        $signature =
            trim(
                (string) (
                    $_SERVER['HTTP_X_TWILIO_SIGNATURE']
                    ?? ''
                )
            );

        if (
            $authToken === ''
            || $appUrl === ''
            || $signature === ''
        ) {
            return false;
        }

        $url =
            $appUrl
            . '/webhooks/twilio/incoming';

        $queryString =
            (string) ($_SERVER['QUERY_STRING'] ?? '');

        if ($queryString !== '') {
            $url .= '?' . $queryString;
        }

        $validator =
            new RequestValidator($authToken);

        return $validator->validate(
            $signature,
            $url,
            $_POST
        );
    }
}
