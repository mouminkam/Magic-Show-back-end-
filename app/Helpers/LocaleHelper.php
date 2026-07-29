<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;

class LocaleHelper
{
    /**
     * Get the translated value for an attribute from a model.
     * Tries: attribute_{locale}, then attribute_ar, then attribute_en.
     *
     * @param Model $model
     * @param string $attribute Base attribute name (e.g. 'title', 'description')
     * @return mixed
     */
    public static function transAttr(Model $model, string $attribute)
    {
        $locale = app()->getLocale();
        $key = $attribute . '_' . $locale;
        if (isset($model->$key) && $model->$key !== null && $model->$key !== '') {
            return $model->$key;
        }
        $ar = $attribute . '_ar';
        $en = $attribute . '_en';
        if (isset($model->$ar) && $model->$ar !== null && $model->$ar !== '') {
            return $model->$ar;
        }
        if (isset($model->$en) && $model->$en !== null && $model->$en !== '') {
            return $model->$en;
        }
        $legacy = $model->$attribute ?? null;
        return $legacy;
    }
}
