<?php

namespace App\Support;

final class GodName
{
    // Пробелы любого вида считаются одним пробелом, в том числе неразрывные.
    public static function normalize(string $name): string
    {
        return trim(preg_replace('/[\p{Z}\s]+/u', ' ', $name) ?? $name);
    }

    // Ключ для проверки дублей: регистр и лишние пробелы не влияют.
    // Ё/Е при сравнении считаются эквивалентными, чтобы не обходить ограничение.
    public static function key(string $name): string
    {
        $value = mb_strtolower(self::normalize($name), 'UTF-8');
        return str_replace('ё', 'е', $value);
    }
}
