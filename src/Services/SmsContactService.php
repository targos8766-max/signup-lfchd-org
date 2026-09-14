<?php

declare(strict_types=1);

namespace Boneblaze\SignupLfchdOrg\Services;

use Boneblaze\SignupLfchdOrg\Database\Database;

final class SmsContactService
{
    public function isOptedOut(string $phoneNumber): bool
    {
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

        if ($phoneNumber === '') {
            return false;
        }

        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'SELECT sms_status
             FROM sms_contacts
             WHERE phone_number = ?
             LIMIT 1'
        );

        $statement->execute([$phoneNumber]);

        $status = $statement->fetchColumn();

        return $status === 'opted_out';
    }

    public function canReceiveSms(string $phoneNumber): bool
    {
        return !$this->isOptedOut($phoneNumber);
    }

    public function markOptedOut(
        string $phoneNumber,
        string $source = 'twilio_stop'
    ): void {
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

        if ($phoneNumber === '') {
            return;
        }

        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'INSERT INTO sms_contacts
                (
                    phone_number,
                    sms_status,
                    opted_out_at,
                    last_opt_out_source
                )
             VALUES
                (?, "opted_out", NOW(), ?)
             ON DUPLICATE KEY UPDATE
                sms_status = "opted_out",
                opted_out_at = NOW(),
                last_opt_out_source = VALUES(last_opt_out_source)'
        );

        $statement->execute([
            $phoneNumber,
            $source,
        ]);
    }

    public function markOptedIn(string $phoneNumber): void
    {
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

        if ($phoneNumber === '') {
            return;
        }

        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'INSERT INTO sms_contacts
                (
                    phone_number,
                    sms_status,
                    opted_in_at,
                    opted_out_at,
                    last_opt_out_source
                )
             VALUES
                (?, "opted_in", NOW(), NULL, NULL)
             ON DUPLICATE KEY UPDATE
                sms_status = "opted_in",
                opted_in_at = NOW(),
                opted_out_at = NULL,
                last_opt_out_source = NULL'
        );

        $statement->execute([$phoneNumber]);
    }

    /**
     * Normalize numbers so registrations such as "(859) 555-1234" match
     * Twilio webhook From values such as "+18595551234".
     *
     * North American 10-digit and 11-digit numbers are converted to E.164.
     * International numbers already supplied with a leading + are preserved
     * in normalized digit-only E.164 form.
     */
    public function normalizePhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = trim($phoneNumber);

        if ($phoneNumber === '') {
            return '';
        }

        $hasLeadingPlus = str_starts_with($phoneNumber, '+');
        $digits = preg_replace('/\D+/', '', $phoneNumber);

        if (!is_string($digits) || $digits === '') {
            return '';
        }

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }

        if (
            strlen($digits) === 11
            && str_starts_with($digits, '1')
        ) {
            return '+' . $digits;
        }

        if (
            $hasLeadingPlus
            && strlen($digits) >= 8
            && strlen($digits) <= 15
        ) {
            return '+' . $digits;
        }

        return $phoneNumber;
    }
}
