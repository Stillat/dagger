<?php

namespace Stillat\Dagger\Runtime;

use Illuminate\Support\Str;
use Illuminate\View\ComponentAttributeBag;

class Attributes
{
    public static function mergeNestedAttributes(array $data, array $props): array
    {
        if (! isset($data['attributes']) || empty($props)) {
            return $data;
        }

        $attributes = $data['attributes'];

        if ($attributes instanceof ComponentAttributeBag) {
            $attributeData = $attributes->all();
        } elseif (is_array($attributes)) {
            $attributeData = $attributes;
        } else {
            return $data;
        }

        $adjustedData = [];

        foreach ($attributeData as $key => $value) {
            $camelCased = Str::camel($key);

            if (isset($props[$camelCased])) {
                $key = $camelCased;
            }

            $adjustedData[$key] = $value;
        }

        unset($attributeData);
        $data = array_merge($adjustedData, $data);

        if ($data['attributes'] === $attributes) {
            unset($data['attributes']);
        }

        return $data;
    }
}
