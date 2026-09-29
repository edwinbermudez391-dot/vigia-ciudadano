<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ticket_code',
        'user_id',
        'citizen_name',
        'citizen_phone',
        'citizen_email',
        'category_id',
        'title',
        'description',
        'damage_photo_url',
        'latitude',
        'longitude',
        'address_reference',
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
            'user_id' => 'integer',
            'category_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'status' => ReportStatus::class,
        ];
    }

    /**
     * Ciudadano que registró el reporte (nulo si la cuenta fue eliminada).
     *
     * @return BelongsTo<User, Report>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Categoría / tipo de daño reportado.
     *
     * @return BelongsTo<Category, Report>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Orden de trabajo derivada de este reporte (0..1).
     *
     * @return HasOne<WorkOrder>
     */
    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    /**
     * Ciudadanos suscritos a las novedades del reporte.
     *
     * @return BelongsToMany<User>
     */
    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'report_subscribers')
            ->withTimestamps();
    }

    /**
     * Filtro por estado del ciclo de vida.
     *
     * @param  Builder<Report>  $query
     */
    public function scopeStatus(Builder $query, ReportStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }
}
