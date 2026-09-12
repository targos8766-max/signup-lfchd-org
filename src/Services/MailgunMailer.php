<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Services;

use DateTimeImmutable;
use RuntimeException;

class MailgunMailer
{
    private string $apiKey;
    private string $domain;
    private string $fromEmail;
    private string $fromName;
    private string $apiBaseUrl;
    private string $appUrl;

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

        $this->appUrl =
            rtrim(
                trim((string) ($_ENV['APP_URL'] ?? '')),
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
        if (empty($registration['email'])) {
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

        $eventLocation =
            $language === 'es'
            && !empty($event['location_es'])
                ? $event['location_es']
                : ($event['location'] ?? '');

        $start = new DateTimeImmutable(
            $slot['start_datetime']
        );

        $end = new DateTimeImmutable(
            $slot['end_datetime']
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
            $subject =
                'Confirmación de registro: '
                . $eventTitle;

            $text =
                "Hola {$registration['first_name']},\n\n"
                . "Su registro está confirmado.\n\n"
                . "Evento: {$eventTitle}\n"
                . 'Fecha: '
                . $this->formatDate($start, 'es')
                . "\n"
                . 'Hora: '
                . $start->format('g:i A')
                . ' - '
                . $end->format('g:i A')
                . "\n";

            if ($eventLocation !== '') {
                $text .=
                    "Ubicación: {$eventLocation}\n";
            }

            $text .=
                "\nVer detalles o cancelar su registro:\n"
                . $confirmationUrl
                . "\n\n"
                . "Lexington-Fayette County Health Department";

            $html =
                '<p>Hola '
                . $this->escape(
                    $registration['first_name']
                )
                . ',</p>'
                . '<p>Su registro está confirmado.</p>'
                . '<p><strong>Evento:</strong> '
                . $this->escape($eventTitle)
                . '<br><strong>Fecha:</strong> '
                . $this->escape(
                    $this->formatDate($start, 'es')
                )
                . '<br><strong>Hora:</strong> '
                . $this->escape(
                    $start->format('g:i A')
                    . ' - '
                    . $end->format('g:i A')
                );

            if ($eventLocation !== '') {
                $html .=
                    '<br><strong>Ubicación:</strong> '
                    . $this->escape($eventLocation);
            }

            $html .=
                '</p>'
                . '<p><a href="'
                . $this->escape($confirmationUrl)
                . '">Ver detalles o cancelar su registro</a></p>'
                . '<p>Lexington-Fayette County Health Department</p>';
        } else {
            $subject =
                'Registration Confirmation: '
                . $eventTitle;

            $text =
                "Hello {$registration['first_name']},\n\n"
                . "Your registration is confirmed.\n\n"
                . "Event: {$eventTitle}\n"
                . 'Date: '
                . $this->formatDate($start, 'en')
                . "\n"
                . 'Time: '
                . $start->format('g:i A')
                . ' - '
                . $end->format('g:i A')
                . "\n";

            if ($eventLocation !== '') {
                $text .=
                    "Location: {$eventLocation}\n";
            }

            $text .=
                "\nView details or cancel your registration:\n"
                . $confirmationUrl
                . "\n\n"
                . "Lexington-Fayette County Health Department";

            $html =
                '<p>Hello '
                . $this->escape(
                    $registration['first_name']
                )
                . ',</p>'
                . '<p>Your registration is confirmed.</p>'
                . '<p><strong>Event:</strong> '
                . $this->escape($eventTitle)
                . '<br><strong>Date:</strong> '
                . $this->escape(
                    $this->formatDate($start, 'en')
                )
                . '<br><strong>Time:</strong> '
                . $this->escape(
                    $start->format('g:i A')
                    . ' - '
                    . $end->format('g:i A')
                );

            if ($eventLocation !== '') {
                $html .=
                    '<br><strong>Location:</strong> '
                    . $this->escape($eventLocation);
            }

            $html .=
                '</p>'
                . '<p><a href="'
                . $this->escape($confirmationUrl)
                . '">View details or cancel your registration</a></p>'
                . '<p>Lexington-Fayette County Health Department</p>';
        }

        $this->send(
            $registration['email'],
            $subject,
            $text,
            $html
        );
    }

    public function sendCancellationConfirmation(
        array $registration
    ): void {
        if (empty($registration['email'])) {
            return;
        }

        $language =
            ($registration['preferred_language'] ?? 'en') === 'es'
                ? 'es'
                : 'en';

        $eventTitle =
            $language === 'es'
            && !empty($registration['title_es'])
                ? $registration['title_es']
                : $registration['title'];

        $eventLocation =
            $language === 'es'
            && !empty($registration['location_es'])
                ? $registration['location_es']
                : ($registration['location'] ?? '');

        $start = new DateTimeImmutable(
            $registration['start_datetime']
        );

        if ($language === 'es') {
            $subject =
                'Registro cancelado: '
                . $eventTitle;

            $text =
                "Hola {$registration['first_name']},\n\n"
                . "Su registro ha sido cancelado.\n\n"
                . "Evento: {$eventTitle}\n"
                . 'Fecha: '
                . $this->formatDate($start, 'es')
                . "\n"
                . 'Hora: '
                . $start->format('g:i A')
                . "\n";

            if ($eventLocation !== '') {
                $text .=
                    "Ubicación: {$eventLocation}\n";
            }

            $text .=
                "\nLexington-Fayette County Health Department";

            $html =
                '<p>Hola '
                . $this->escape(
                    $registration['first_name']
                )
                . ',</p>'
                . '<p>Su registro ha sido cancelado.</p>'
                . '<p><strong>Evento:</strong> '
                . $this->escape($eventTitle)
                . '<br><strong>Fecha:</strong> '
                . $this->escape(
                    $this->formatDate($start, 'es')
                )
                . '<br><strong>Hora:</strong> '
                . $this->escape(
                    $start->format('g:i A')
                );

            if ($eventLocation !== '') {
                $html .=
                    '<br><strong>Ubicación:</strong> '
                    . $this->escape($eventLocation);
            }

            $html .=
                '</p>'
                . '<p>Lexington-Fayette County Health Department</p>';
        } else {
            $subject =
                'Registration Cancelled: '
                . $eventTitle;

            $text =
                "Hello {$registration['first_name']},\n\n"
                . "Your registration has been cancelled.\n\n"
                . "Event: {$eventTitle}\n"
                . 'Date: '
                . $this->formatDate($start, 'en')
                . "\n"
                . 'Time: '
                . $start->format('g:i A')
                . "\n";

            if ($eventLocation !== '') {
                $text .=
                    "Location: {$eventLocation}\n";
            }

            $text .=
                "\nLexington-Fayette County Health Department";

            $html =
                '<p>Hello '
                . $this->escape(
                    $registration['first_name']
                )
                . ',</p>'
                . '<p>Your registration has been cancelled.</p>'
                . '<p><strong>Event:</strong> '
                . $this->escape($eventTitle)
                . '<br><strong>Date:</strong> '
                . $this->escape(
                    $this->formatDate($start, 'en')
                )
                . '<br><strong>Time:</strong> '
                . $this->escape(
                    $start->format('g:i A')
                );

            if ($eventLocation !== '') {
                $html .=
                    '<br><strong>Location:</strong> '
                    . $this->escape($eventLocation);
            }

            $html .=
                '</p>'
                . '<p>Lexington-Fayette County Health Department</p>';
        }

        $this->send(
            $registration['email'],
            $subject,
            $text,
            $html
        );
    }

    private function send(
        string $to,
        string $subject,
        string $text,
        string $html
    ): void {
        $url =
            $this->apiBaseUrl
            . '/'
            . rawurlencode($this->domain)
            . '/messages';

        $postFields = [
            'from' =>
                $this->fromName
                . ' <'
                . $this->fromEmail
                . '>',
            'to' => $to,
            'subject' => $subject,
            'text' => $text,
            'html' => $html,
        ];

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException(
                'Unable to initialize Mailgun request.'
            );
        }

        curl_setopt_array(
            $ch,
            [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD =>
                    'api:' . $this->apiKey,
                CURLOPT_TIMEOUT => 20,
            ]
        );

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new RuntimeException(
                'Mailgun request failed: '
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
                'Mailgun returned HTTP '
                . $statusCode
                . ': '
                . $response
            );
        }
    }

    private function formatDate(
        DateTimeImmutable $date,
        string $language
    ): string {
        if ($language !== 'es') {
            return $date->format('l, F j, Y');
        }

        $days = [
            'Sunday' => 'domingo',
            'Monday' => 'lunes',
            'Tuesday' => 'martes',
            'Wednesday' => 'miércoles',
            'Thursday' => 'jueves',
            'Friday' => 'viernes',
            'Saturday' => 'sábado',
        ];

        $months = [
            'January' => 'enero',
            'February' => 'febrero',
            'March' => 'marzo',
            'April' => 'abril',
            'May' => 'mayo',
            'June' => 'junio',
            'July' => 'julio',
            'August' => 'agosto',
            'September' => 'septiembre',
            'October' => 'octubre',
            'November' => 'noviembre',
            'December' => 'diciembre',
        ];

        return $days[$date->format('l')]
            . ', '
            . $date->format('j')
            . ' de '
            . $months[$date->format('F')]
            . ' de '
            . $date->format('Y');
    }

    private function escape(
        string $value
    ): string {
        return htmlspecialchars(
            $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}
