<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'report_id',
        'contractor_id',
        'scheduled_date',
        'completion_date',
        'work_description',
        'solution_photo_url',
        'status',
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
            'contractor_id' => 'integer',
            'scheduled_date' => 'date',
            'completion_date' => 'datetime',
            'status' => WorkOrderStatus::class,
        ];
    }

    /**
     * Reporte ciudadano que originó la orden.
     *
     * @return BelongsTo<Report, WorkOrder>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Contratista responsable de la reparación.
     *
     * @return BelongsTo<User, WorkOrder>
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }

    /**
     * Inspecciones de calidad practicadas sobre esta orden.
     *
     * @return HasMany<QualityInspection>
     */
    public function qualityInspections(): HasMany
    {
        return $this->hasMany(QualityInspection::class);
    }
}
