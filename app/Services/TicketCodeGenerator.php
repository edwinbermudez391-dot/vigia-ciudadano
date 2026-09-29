<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Generador de códigos de ticket legibles y secuenciales por año:
 * REP-AAAA-NNNN (ej: REP-2026-0001).
 *
 * Seguridad de concurrencia:
 *  1. El SELECT ... FOR UPDATE sobre el índice UNIQUE de ticket_code toma un
 *     next-key lock en InnoDB: dos transacciones simultáneas se serializan
 *     para el prefijo del año.
 *  2. El índice UNIQUE es la red final: si aun así dos procesos cruzan las
 *     llaves, la segunda inserción lanza UniqueConstraintViolationException y
 *     el llamador reintenta.
 */
final class TicketCodeGenerator
{
    /**
     * Debe invocarse DENTRO de una transacción activa, o el lockForUpdate
     * no tiene ningún efecto (se liberaría al instante).
     */
    public function next(): string
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException(
                'TicketCodeGenerator::next() exige una transacción activa (DB::transaction).'
            );
        }

        $prefix = sprintf('REP-%d-', (int) now()->format('Y'));

        // El máximo se calcula NUMERICAMENTE, no lexicográficamente: como texto,
        // 'REP-2026-9999' > 'REP-2026-10000' (dígito '9' > '1'), lo que hacía que
        // next() devolviera siempre un código ya usado al cruzar los 4 dígitos.
        // SUBSTRING extrae los dígitos tras 'REP-AAAA-' (1-indexado) y CAST los
        // convierte a entero sin dependencia del padding (0001 y 10000 comparables).
        $firstDigitPos = strlen($prefix) + 1;

        $lastSequence = (int) Report::query()
            ->where('ticket_code', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max(DB::raw("CAST(SUBSTRING(ticket_code, {$firstDigitPos}) AS UNSIGNED)"));

        // %04d: relleno MÍNIMO de 4 dígitos; 10001 se emite tal cual (5 dígitos).
        return sprintf('%s%04d', $prefix, $lastSequence + 1);
    }
}
