<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingMatrixRow extends Model
{
    protected $fillable = ['training_category_id', 'description', 'part_number', 'sort_order', 'manual_id'];

    public function category()
    {
        return $this->belongsTo(TrainingCategory::class, 'training_category_id');
    }

    public function manual()
    {
        return $this->belongsTo(Manual::class, 'manual_id');
    }

    /** Нормализация парт-номера для автопривязки CMM (пробелы/регистр не значимы). */
    public static function normalizePartNumber(?string $pn): string
    {
        return mb_strtolower(preg_replace('/\s+/u', '', (string) $pn));
    }

    /**
     * PN-токены для матчинга строк матрицы с manuals: разбивка по разделителям,
     * срез суффиксов -series/-ser(.), у дэш-номеров («49800-3») добавляется база.
     *
     * @return list<string>
     */
    public static function pnTokens(?string $raw): array
    {
        $tokens = [];
        foreach (preg_split('/[\/;,\s]+/u', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            $t = mb_strtolower(trim($part));
            $t = preg_replace('/[-–]*\s*(series|ser)\.?$/u', '', $t);
            $t = trim($t, "-. \t");
            if (mb_strlen($t) >= 4 && preg_match('/\d/', $t)) {
                $tokens[] = $t;
                if (preg_match('/^(.{4,})-\d{1,2}$/', $t, $m)) {
                    $tokens[] = $m[1];
                }
            }
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Автопривязка CMM к строке матрицы по PN-токенам.
     * Линкует только при однозначном совпадении с непривязанной строкой.
     */
    public static function autoLinkManual(Manual $manual): ?self
    {
        if (empty($manual->unit_name_training) || $manual->matrixRow()->exists()) {
            return null;
        }

        $manualTokens = self::pnTokens($manual->unit_name_training);
        if (!$manualTokens) {
            return null;
        }

        $matches = self::whereNull('manual_id')->get()
            ->filter(fn (self $row) => array_intersect(self::pnTokens($row->part_number), $manualTokens) !== []);

        if ($matches->count() !== 1) {
            return null;
        }

        $row = $matches->first();
        $row->update(['manual_id' => $manual->id]);

        return $row;
    }
}
