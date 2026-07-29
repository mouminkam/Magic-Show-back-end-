<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Fills legacy non-localized DB columns from *_en / *_ar so MySQL strict mode
 * does not raise 1364 when admins submit only localized fields.
 *
 * Rule: base = first non-empty(_en), else non-empty(_ar), else keep existing base.
 */
trait SyncsLegacyLocaleBaseFields
{
    public static function bootSyncsLegacyLocaleBaseFields(): void
    {
        static::saving(function (Model $model) {
            foreach ($model->getLocaleBaseFieldMap() as $base => $pair) {
                [$enCol, $arCol] = $pair;
                $enVal = $model->getAttribute($enCol);
                $arVal = $model->getAttribute($arCol);
                $baseVal = $model->getAttribute($base);
                $model->setAttribute(
                    $base,
                    $model->resolveLocaleBaseFieldValue($enVal, $arVal, $baseVal)
                );
            }
        });
    }

    protected function resolveLocaleBaseFieldValue($enVal, $arVal, $fallback)
    {
        if ($this->isLocaleValuePresent($enVal)) {
            return $enVal;
        }
        if ($this->isLocaleValuePresent($arVal)) {
            return $arVal;
        }

        return $fallback;
    }

    protected function isLocaleValuePresent($value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_string($value)) {
            return trim($value) !== '';
        }
        if (is_array($value)) {
            return count($value) > 0;
        }

        return true;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    abstract protected function getLocaleBaseFieldMap(): array;
}
