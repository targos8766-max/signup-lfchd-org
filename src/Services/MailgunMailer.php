<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Services;

use RuntimeException;

class MailgunMailer
{
    private string $apiKey;
    private string $domain;
    private string $fromEmail;
    private string $fromName;
    private string $apiBaseUrl;

    public function __construct()
    {
        $this->apiKey =
            trim((string) ($_ENV['MAILGUN_API_KEY'] ?? ''));

        $this->domain =
            trim((string) ($_ENV['MAILGUN_DOMAIN'] ?? ''));

        $this->fromEmail =
            trim((string) ($_ENV['MAILGUN_FROM_EMAIL'] ?? ''));

        $this->fromName =
            trim((string) ($_ENV['MAILGUN_FROM_NAME'] ?? 'LFCHD'));

        $this->apiBaseUrl =
            rtrim(
                trim(
                    (string) (
                        $_ENV['MAILGUN_API_BASE_URL']
                        ?? 'https://api.mailgun.net/v3'
                    )
                ),
                '/'
            );

        if (
            $this->apiKey === ''
            || $this->domain === ''
            || $this->fromEmail === ''
        ) {
            throw new RuntimeException(
                'Mailgun configuration is incomplete.'
            );
        }
    }

    public function sendRegistrationConfirmation(
        array $event,
        array $slot,
        array $registration
    ): void {
        $recipientEmail =
            trim((string) ($registration['email'] ?? ''));

        if ($recipientEmail === '') {
            return;
        }

        $recipientName = trim(
            (string) ($registration['first_name'] ?? '')
            . ' '
            . (string) ($registration['last_name'] ?? '')
        );

        $subject =
            'Registration Confirmation - '
            . (string) $event['title'];

        $confirmationUrl =
            $this->buildConfirmationUrl(
                (string) $event['public_slug'],
                (string) $registration['confirmation_code']
            );

        $eventDate =
            new \DateTimeImmutable(
                (string) $event['event_date']
            );

        $start =
            new \DateTimeImmutable(
                (string) $slot['start_datetime']
            );

        $end =
            new \DateTimeImmutable(
                (string) $slot['end_datetime']
            );

        $location =
            trim((string) ($event['location'] ?? ''));

        $textLines = [
            'Your registration has been confirmed.',
            '',
            'Event: ' . (string) $event['title'],
            'Date: ' . $eventDate->format('l, F j, Y'),
            'Time: '
                . $start->format('g:i A')
                . ' - '
                . $end->format('g:i A'),
        ];

        if ($location !== '') {
            $textLines[] =
                'Location: ' . $location;
        }

        $textLines[] = '';
        $textLines[] =
            'Confirmation code: '
            . (string) $registration['confirmation_code'];

        $textLines[] = '';
        $textLines[] =
            'View or cancel your registration: '
            . $confirmationUrl;

        $textLines[] = '';
        $textLines[] =
            'Lexington-Fayette County Health Department';

        $textBody =
            implode("\n", $textLines);

        $safeEventTitle =
            htmlspecialchars(
                (string) $event['title'],
                ENT_QUOTES,
                'UTF-8'
            );

        $safeLocation =
            htmlspecialchars(
                $location,
                ENT_QUOTES,
                'UTF-8'
            );

        $safeConfirmationCode =
            htmlspecialchars(
                (string) $registration['confirmation_code'],
                ENT_QUOTES,
                'UTF-8'
            );

        $safeConfirmationUrl =
            htmlspecialchars(
                $confirmationUrl,
                ENT_QUOTES,
                'UTF-8'
            );

        $htmlBody =
            '<!doctype html>'
            . '<html><body style="font-family:Arial,sans-serif;'
            . 'color:#212529;line-height:1.5;">'
            . '<h2>Registration Confirmed</h2>'
            . '<p>Your registration has been confirmed.</p>'
            . '<table cellpadding="6" cellspacing="0" border="0">'
            . '<tr><td><strong>Event</strong></td><td>'
            . $safeEventTitle
            . '</td></tr>'
            . '<tr><td><strong>Date</strong></td><td>'
            . htmlspecialchars(
                $eventDate->format('l, F j, Y'),
                ENT_QUOTES,
                'UTF-8'
            )
            . '</td></tr>'
            . '<tr><td><strong>Time</strong></td><td>'
            . htmlspecialchars(
                $start->format('g:i A')
                . ' - '
                . $end->format('g:i A'),
                ENT_QUOTES,
                'UTF-8'
            )
            . '</td></tr>';

        if ($location !== '') {
            $htmlBody .=
                '<tr><td><strong>Location</strong></td><td>'
                . $safeLocation
                . '</td></tr>';
        }

        $htmlBody .=
            '</table>'
            . '<p><strong>Confirmation code:</strong> '
            . $safeConfirmationCode
            . '</p>'
            . '<p><a href="'
            . $safeConfirmationUrl
            . '">View or cancel your registration</a></p>'
            . '<p>Lexington-Fayette County Health Department</p>'
            . '</body></html>';

        $this->send(
            $recipientEmail,
            $recipientName,
            $subject,
            $textBody,
            $htmlBody
        );
    }

    public function sendCancellationConfirmation(
        array $registration
    ): void {
        $recipientEmail =
            trim((string) ($registration['email'] ?? ''));

        if ($recipientEmail === '') {
            return;
        }

        $recipientName = trim(
            (string) ($registration['first_name'] ?? '')
            . ' '
            . (string) ($registration['last_name'] ?? '')
        );

        $subject =
            'Registration Cancelled - '
            . (string) $registration['title'];

        $start =
            new \DateTimeImmutable(
                (string) $registration['start_datetime']
            );

        $end =
            new \DateTimeImmutable(
                (string) $registration['end_datetime']
            );

        $location =
            trim((string) ($registration['location'] ?? ''));

        $confirmationUrl =
            $this->buildConfirmationUrl(
                (string) $registration['public_slug'],
                (string) $registration['confirmation_code']
            );

        $textLines = [
            'Your registration has been cancelled.',
            '',
            'Event: ' . (string) $registration['title'],
            'Date: ' . $start->format('l, F j, Y'),
            'Time: '
                . $start->format('g:i A')
                . ' - '
                . $end->format('g:i A'),
        ];

        if ($location !== '') {
            $textLines[] =
                'Location: ' . $location;
        }

        $textLines[] = '';
        $textLines[] =
            'The reserved seat has been released.';

        $textLines[] =
            'Registration details: ' . $confirmationUrl;

        $textLines[] = '';
        $textLines[] =
            'Lexington-Fayette County Health Department';

        $textBody = implode("\n", $textLines);

        $safeTitle = htmlspecialchars(
            (string) $registration['title'],
            ENT_QUOTES,
            'UTF-8'
        );

        $safeLocation = htmlspecialchars(
            $location,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeUrl = htmlspecialchars(
            $confirmationUrl,
            ENT_QUOTES,
            'UTF-8'
        );

        $htmlBody =
            '<!doctype html>'
            . '<html><body style="font-family:Arial,sans-serif;'
            . 'color:#212529;line-height:1.5;">'
            . '<h2>Registration Cancelled</h2>'
            . '<p>Your registration has been cancelled.</p>'
            . '<table cellpadding="6" cellspacing="0" border="0">'
            . '<tr><td><strong>Event</strong></td><td>'
            . $safeTitle
            . '</td></tr>'
            . '<tr><td><strong>Date</strong></td><td>'
            . htmlspecialchars(
                $start->format('l, F j, Y'),
                ENT_QUOTES,
                'UTF-8'
            )
            . '</td></tr>'
            . '<tr><td><strong>Time</strong></td><td>'
            . htmlspecialchars(
                $start->format('g:i A')
                . ' - '
                . $end->format('g:i A'),
                ENT_QUOTES,
                'UTF-8'
            )
            . '</td></tr>';

        if ($location !== '') {
            $htmlBody .=
                '<tr><td><strong>Location</strong></td><td>'
                . $safeLocation
                . '</td></tr>';
        }

        $htmlBody .=
            '</table>'
            . '<p>The reserved seat has been released.</p>'
            . '<p><a href="'
            . $safeUrl
            . '">View registration details</a></p>'
            . '<p>Lexington-Fayette County Health Department</p>'
            . '</body></html>';

        $this->send(
            $recipientEmail,
            $recipientName,
            $subject,
            $textBody,
            $htmlBody
        );
    }

    private function buildConfirmationUrl(
        string $slug,
        string $confirmationCode
    ): string {
        return rtrim(
            (string) ($_ENV['APP_URL'] ?? ''),
            '/'
        )
        . '/event/'
        . rawurlencode($slug)
        . '/confirmation/'
        . rawurlencode($confirmationCode);
    }

    private function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $textBody,
        string $htmlBody
    ): void {
        if (!function_exists('curl_init')) {
            throw new RuntimeException(
                'PHP cURL extension is not installed.'
            );
        }

        $url =
            $this->apiBaseUrl
            . '/'
            . rawurlencode($this->domain)
            . '/messages';

        $from =
            $this->fromName !== ''
            ? $this->fromName
                . ' <'
                . $this->fromEmail
                . '>'
            : $this->fromEmail;

        $to =
            $toName !== ''
            ? $toName
                . ' <'
                . $toEmail
                . '>'
            : $toEmail;

        $curl = curl_init();

        if ($curl === false) {
            throw new RuntimeException(
                'Unable to initialize Mailgun request.'
            );
        }

        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERPWD =>
                    'api:' . $this->apiKey,
                CURLOPT_POSTFIELDS => [
                    'from' => $from,
                    'to' => $to,
                    'subject' => $subject,
                    'text' => $textBody,
                    'html' => $htmlBody,
                ],
            ]
        );

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new RuntimeException(
                'Mailgun request failed: ' . $error
            );
        }

        $statusCode =
            (int) curl_getinfo(
                $curl,
                CURLINFO_HTTP_CODE
            );

        curl_close($curl);

        if (
            $statusCode < 200
            || $statusCode >= 300
        ) {
            throw new RuntimeException(
                'Mailgun returned HTTP '
                . $statusCode
                . '.'
            );
        }
    }
}
