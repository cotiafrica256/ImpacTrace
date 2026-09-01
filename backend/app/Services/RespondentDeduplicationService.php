<?php

namespace App\Services;

use App\Models\Respondent;
use Illuminate\Support\Str;

/**
 * Prevents the same person's identity being registered twice — within one
 * organisation. Two different client organisations are never compared
 * against each other's respondents; each tenant's data is its own.
 *
 * Honest limitation: we do NOT do forensic image comparison between the
 * signature just captured and the signature printed on the ID photo — no
 * off-the-shelf, reliable open tool does that well for handwritten
 * signatures against a photographed card. Instead we prevent duplicates the
 * way MEAL systems reliably can:
 *   1. Hash the ID number (never store it in the clear) and enforce a hard
 *      unique constraint at the database level, scoped to the organisation.
 *   2. For people with no formal ID, fall back to a fuzzy key of
 *      name + age/DOB + village, which is flagged (not hard-blocked) for a
 *      supervisor to review, since two different people can share this.
 * Both the ID photo and the live signature are still captured and stored
 * as the audit/evidence pair for that submission.
 */
class RespondentDeduplicationService
{
    public function hashIdNumber(string $idNumber): string
    {
        return hash('sha256', 'mecpa-id::'.Str::upper(trim($idNumber)));
    }

    public function fuzzyKey(string $fullName, ?string $ageOrDob, ?string $village): string
    {
        $normalized = Str::lower(trim($fullName)).'|'.Str::lower(trim((string) $ageOrDob)).'|'.Str::lower(trim((string) $village));

        return hash('sha256', $normalized);
    }

    /**
     * Returns ['status' => 'new'|'exact_duplicate'|'possible_duplicate', 'respondent' => Respondent|null]
     * All checks are scoped to $organizationId.
     */
    public function check(int $organizationId, ?string $idNumber, string $fullName, ?string $ageOrDob, ?string $village): array
    {
        if ($idNumber) {
            $hash = $this->hashIdNumber($idNumber);
            $existing = Respondent::where('organization_id', $organizationId)->where('id_number_hash', $hash)->first();
            if ($existing) {
                return ['status' => 'exact_duplicate', 'respondent' => $existing, 'id_number_hash' => $hash];
            }

            return ['status' => 'new', 'respondent' => null, 'id_number_hash' => $hash];
        }

        $fuzzy = $this->fuzzyKey($fullName, $ageOrDob, $village);
        $existing = Respondent::where('organization_id', $organizationId)->where('fuzzy_key', $fuzzy)->first();
        if ($existing) {
            return ['status' => 'possible_duplicate', 'respondent' => $existing, 'fuzzy_key' => $fuzzy];
        }

        return ['status' => 'new', 'respondent' => null, 'fuzzy_key' => $fuzzy];
    }

    public function nextRespondentCode(int $organizationId): string
    {
        $count = Respondent::where('organization_id', $organizationId)->count() + 1;

        return 'RSP-'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
