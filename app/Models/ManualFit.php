<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fits & Clearances pair (manual Table 8001).
 *
 * Links an OD member parameter with an ID member parameter EXPLICITLY — the
 * pairing is no longer inferred from a shared point, so members may live on
 * different points/IPL items, one OD may belong to several fits, and a part can
 * contribute its OD parameter to one fit and its ID parameter to another.
 *
 * Clearances (assembly + permitted) belong to the fit. Stored values reproduce
 * the manual verbatim; the derived* helpers recompute them from the member
 * limits so the UI can flag a stored-vs-derived mismatch.
 */
class ManualFit extends Model
{
    protected $fillable = [
        'manual_id',
        'od_param_id',
        'id_param_id',
        'ref_no',
        'is_fc',
        'assembly_clearance_min',
        'assembly_clearance_max',
        'permitted_clearance',
        'sort_order',
    ];

    protected $casts = [
        'manual_id'              => 'integer',
        'od_param_id'            => 'integer',
        'id_param_id'            => 'integer',
        'is_fc'                  => 'boolean',
        'assembly_clearance_min' => 'decimal:4',
        'assembly_clearance_max' => 'decimal:4',
        'permitted_clearance'    => 'decimal:4',
        'sort_order'             => 'integer',
    ];

    public function manual(): BelongsTo
    {
        return $this->belongsTo(Manual::class);
    }

    public function odParam(): BelongsTo
    {
        return $this->belongsTo(ManualParameter::class, 'od_param_id');
    }

    public function idParam(): BelongsTo
    {
        return $this->belongsTo(ManualParameter::class, 'id_param_id');
    }

    // --- Derived clearances (cross-check against the stored manual values) ----
    //
    // assembly_min = ID_mfg_min - OD_mfg_max ; assembly_max = ID_mfg_max - OD_mfg_min
    // permitted    = ID_wear_max - OD_wear_min
    // (verified on real Table 8001 data). Return null when a needed limit is absent.

    public function derivedAssemblyClearanceMin(): ?float
    {
        return $this->sub($this->idParam?->orig_dim_min, $this->odParam?->orig_dim_max);
    }

    public function derivedAssemblyClearanceMax(): ?float
    {
        return $this->sub($this->idParam?->orig_dim_max, $this->odParam?->orig_dim_min);
    }

    public function derivedPermittedClearance(): ?float
    {
        return $this->sub($this->idParam?->wear_dim_max, $this->odParam?->wear_dim_min);
    }

    /** Effective value: stored (manual) when present, else derived. */
    public function effectiveAssemblyClearanceMin(): ?float
    {
        return $this->assembly_clearance_min !== null
            ? (float) $this->assembly_clearance_min
            : $this->derivedAssemblyClearanceMin();
    }

    public function effectiveAssemblyClearanceMax(): ?float
    {
        return $this->assembly_clearance_max !== null
            ? (float) $this->assembly_clearance_max
            : $this->derivedAssemblyClearanceMax();
    }

    public function effectivePermittedClearance(): ?float
    {
        return $this->permitted_clearance !== null
            ? (float) $this->permitted_clearance
            : $this->derivedPermittedClearance();
    }

    /** True when a stored clearance disagrees with the derived one (UI flag). */
    public function hasClearanceMismatch(float $tolerance = 0.00005): bool
    {
        foreach ([
            [$this->assembly_clearance_min, $this->derivedAssemblyClearanceMin()],
            [$this->assembly_clearance_max, $this->derivedAssemblyClearanceMax()],
            [$this->permitted_clearance,    $this->derivedPermittedClearance()],
        ] as [$stored, $derived]) {
            if ($stored !== null && $derived !== null
                && abs((float) $stored - $derived) > $tolerance) {
                return true;
            }
        }

        return false;
    }

    private function sub($a, $b): ?float
    {
        if ($a === null || $b === null) {
            return null;
        }

        return round((float) $a - (float) $b, 4);
    }
}
