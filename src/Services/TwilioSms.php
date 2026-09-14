<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Services;

use DateTimeImmutable;
use RuntimeException;

class TwilioSms
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private string $messagingServiceSid;
    private string $apiBaseUrl;
    private string $appUrl;
    private SmsContactService $smsContacts;

    public function __construct()
    {
        $this->accountSid =
            trim((string) ($_ENV['TWILIO_ACCOUNT_SID'] ?? ''));

        $this->authToken =
            trim((string) ($_ENV['TWILIO_AUTH_TOKEN'] ?? ''));

        $this->fromNumber =
            trim((string) ($_ENV['TWILIO_FROM_NUMBER'] ?? ''));

        $this->messagingServiceSid =
            trim(
                (string) (
                    $_ENV['TWILIO_MESSAGING_SERVICE_SID']
                    ?? ''
                )
            );

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

        $this->appUrl =
            rtrim(
                trim((string) ($_ENV['APP_URL'] ?? '')),
                '/'
            );

        $this->smsContacts = new SmsContactService();

        if (
            $this->accountSid === ''
            || $this->authToken === ''
        ) {
            throw new RuntimeException(
                'Twilio configuration is incomplete.'
            );
        }

        if (
            $this->fromNumber === ''
            && $this->messagingServiceSid === ''
        ) {
            throw new RuntimeException(
                'Twilio requires a From number or Messaging Service SID.'
            );
        }
    }

    public function sendRegistrationConfirmation(
        array $event,
        array $slot,
        array $registration
    ): void {
        if (
            empty($registration['phone'])
            || (int) ($registration['sms_opt_in'] ?? 0) !== 1
        ) {
            return;
        }

        /*
         * Registration-level consent is not allowed to override a prior
         * global STOP request. The recipient must opt back in through
         * Twilio/START before application SMS can resume.
         */
        if (
            !$this->smsContacts->canReceiveSms(
                (string) $registration['phone']
            )
        ) {
            return;
        }

        $language =
            ($registration['preferred_language'] ?? 'en') === 'es'
                ? 'es'
                : 'en';

        $eventTitle =
            $language === 'es'
            && !empty($event['title_es'])
                ? $event['title_es']
                : $event['title'];

        $start = new DateTimeImmutable(
            $slot['start_datetime']
        );

        $confirmationUrl =
            $this->appUrl
            . '/event/'
            . rawurlencode($event['public_slug'])
            . '/confirmation/'
            . rawurlencode(
                $registration['confirmation_code']
            );

        if ($language === 'es') {
            $body =
                'LFCHD: Está registrado para '
                . $eventTitle
                . ' el '
                . $this->formatSpanishDate($start)
                . ' a las '
                . $start->format('g:i A')
                . '. Detalles/cancelar: '
                . $confirmationUrl
                . ' Responda STOP para dejar de recibir mensajes.';
        } else {
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
        }

        $this->sendMessage(
            (string) $registration['phone'],
            $body
        );
    }

    /**
     * Sends an SMS and returns the Twilio Message SID.
     *
     * Returns an empty string when the number is globally opted out.
     */
    public function sendMessage(
        string $to,
        string $body
    ): string {
        $to = $this->smsContacts->normalizePhoneNumber($to);

        if ($to === '') {
            throw new RuntimeException(
                'SMS recipient phone number is invalid.'
            );
        }

        /*
         * Central enforcement point. This protects confirmations as well as
         * any future reminder or administrative SMS that uses sendMessage().
         */
        if (!$this->smsContacts->canReceiveSms($to)) {
            return '';
        }

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

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException(
                'Unable to initialize Twilio request.'
            );
        }

        curl_setopt_array(
            $ch,
            [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS =>
                    http_build_query($postFields),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_USERPWD =>
                    $this->accountSid
                    . ':'
                    . $this->authToken,
                CURLOPT_TIMEOUT => 20,
            ]
        );

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(
                'Twilio request failed: '
                . $error
            );
        }

        $statusCode =
            (int) curl_getinfo(
                $ch,
                CURLINFO_RESPONSE_CODE
            );

        curl_close($ch);

        if (
            $statusCode < 200
            || $statusCode >= 300
        ) {
            throw new RuntimeException(
                'Twilio returned HTTP '
                . $statusCode
                . ': '
                . $response
            );
        }

        $decoded = json_decode(
            $response,
            true
        );

        if (
            is_array($decoded)
            && !empty($decoded['sid'])
        ) {
            return (string) $decoded['sid'];
        }

        return '';
    }

    private function formatSpanishDate(
        DateTimeImmutable $date
    ): string {
        $months = [
            1 => 'ene',
            2 => 'feb',
            3 => 'mar',
            4 => 'abr',
            5 => 'may',
            6 => 'jun',
            7 => 'jul',
            8 => 'ago',
            9 => 'sep',
            10 => 'oct',
            11 => 'nov',
            12 => 'dic',
        ];

        return $date->format('j')
            . ' '
            . $months[(int) $date->format('n')]
            . ' '
            . $date->format('Y');
    }
}
