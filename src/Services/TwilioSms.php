<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Services;

use RuntimeException;

class TwilioSms
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private string $messagingServiceSid;
    private string $apiBaseUrl;

    public function __construct()
    {
        $this->accountSid =
            trim((string) ($_ENV['TWILIO_ACCOUNT_SID'] ?? ''));

        $this->authToken =
            trim((string) ($_ENV['TWILIO_AUTH_TOKEN'] ?? ''));

        $this->fromNumber =
            trim((string) ($_ENV['TWILIO_FROM_NUMBER'] ?? ''));

        $this->messagingServiceSid =
            trim((string) ($_ENV['TWILIO_MESSAGING_SERVICE_SID'] ?? ''));

        $this->apiBaseUrl =
            rtrim(
                trim(
                    (string) (
                        $_ENV['TWILIO_API_BASE_URL']
                        ?? 'https://api.twilio.com/2010-04-01'
                    )
                ),
                '/'
            );

        if ($this->accountSid === '') {
            throw new RuntimeException(
                'TWILIO_ACCOUNT_SID is not configured.'
            );
        }

        if ($this->authToken === '') {
            throw new RuntimeException(
                'TWILIO_AUTH_TOKEN is not configured.'
            );
        }

        if (
            $this->fromNumber === ''
            && $this->messagingServiceSid === ''
        ) {
            throw new RuntimeException(
                'Configure either TWILIO_FROM_NUMBER or TWILIO_MESSAGING_SERVICE_SID.'
            );
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException(
                'PHP cURL extension is required for Twilio SMS.'
            );
        }
    }

    public function sendRegistrationConfirmation(
        array $event,
        array $slot,
        array $registration
    ): void {
        $phone = trim(
            (string) ($registration['phone'] ?? '')
        );

        if ($phone === '') {
            return;
        }

        if (
            empty($registration['sms_opt_in'])
            || (int) $registration['sms_opt_in'] !== 1
        ) {
            return;
        }

        $eventTitle = trim(
            (string) ($event['title'] ?? 'Event')
        );

        $start = new \DateTimeImmutable(
            (string) $slot['start_datetime']
        );

        $confirmationUrl =
            $this->buildConfirmationUrl(
                (string) $event['public_slug'],
                (string) $registration['confirmation_code']
            );

        $body =
            'LFCHD: You are registered for '
            . $eventTitle
            . ' on '
            . $start->format('M j, Y')
            . ' at '
            . $start->format('g:i A')
            . '. Details/cancel: '
            . $confirmationUrl
            . ' Reply STOP to unsubscribe.';

        $this->send(
            $phone,
            $body
        );
    }

    private function send(
        string $to,
        string $body
    ): void {
        $url =
            $this->apiBaseUrl
            . '/Accounts/'
            . rawurlencode($this->accountSid)
            . '/Messages.json';

        $postFields = [
            'To' => $to,
            'Body' => $body,
        ];

        if ($this->messagingServiceSid !== '') {
            $postFields['MessagingServiceSid'] =
                $this->messagingServiceSid;
        } else {
            $postFields['From'] =
                $this->fromNumber;
        }

        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException(
                'Unable to initialize Twilio request.'
            );
        }

        curl_setopt_array(
            $curl,
            [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query(
                    $postFields
                ),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD =>
                    $this->accountSid
                    . ':'
                    . $this->authToken,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
            ]
        );

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new RuntimeException(
                'Twilio request failed: ' . $error
            );
        }

        $httpCode =
            (int) curl_getinfo(
                $curl,
                CURLINFO_HTTP_CODE
            );

        curl_close($curl);

        if ($httpCode < 200 || $httpCode >= 300) {
            $message = 'Twilio returned HTTP ' . $httpCode . '.';

            $decoded = json_decode(
                (string) $response,
                true
            );

            if (
                is_array($decoded)
                && !empty($decoded['message'])
            ) {
                $message .= ' ' . $decoded['message'];
            }

            throw new RuntimeException($message);
        }
    }

    private function buildConfirmationUrl(
        string $slug,
        string $confirmationCode
    ): string {
        $appUrl = rtrim(
            trim(
                (string) (
                    $_ENV['APP_URL']
                    ?? ''
                )
            ),
            '/'
        );

        if ($appUrl === '') {
            throw new RuntimeException(
                'APP_URL is not configured.'
            );
        }

        return
            $appUrl
            . '/event/'
            . rawurlencode($slug)
            . '/confirmation/'
            . rawurlencode($confirmationCode);
    }
}
