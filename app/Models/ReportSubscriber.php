<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Suscripción de un ciudadano a las novedades de un reporte.
 *
 * Se modela como Pivot con atributos propios (timestamps) porque la tabla
 * report_subscribers usa clave primaria autoincremental además del
 * índice único compuesto (report_id, user_id).
 */
class ReportSubscriber extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'report_subscribers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'report_id',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_id' => 'integer',
            'user_id' => 'integer',
        ];
    }

    /**
     * Reporte al que aplica la suscripción.
     *
     * @return BelongsTo<Report, ReportSubscriber>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Ciudadano suscrito.
     *
     * @return BelongsTo<User, ReportSubscriber>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
