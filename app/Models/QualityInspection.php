<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InspectionDecision;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'work_order_id',
        'inspector_id',
        'checklist_results',
        'decision',
        'rejection_reason',
        'verification_photo_url',
        'inspector_latitude',
        'inspector_longitude',
        'inspected_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_order_id' => 'integer',
            'inspector_id' => 'integer',
            'checklist_results' => 'array',
            'decision' => InspectionDecision::class,
            'inspector_latitude' => 'float',
            'inspector_longitude' => 'float',
            'inspected_at' => 'datetime',
        ];
    }

    /**
     * Orden de trabajo inspeccionada.
     *
     * @return BelongsTo<WorkOrder, QualityInspection>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Inspector de calidad que validó la obra.
     *
     * @return BelongsTo<User, QualityInspection>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
