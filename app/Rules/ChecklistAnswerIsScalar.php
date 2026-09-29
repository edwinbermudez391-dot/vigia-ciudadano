<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\InvokableRule;

/**
 * Cada respuesta del checklist de inspección debe ser un escalar pequeño.
 *
 * Bloquea arrays/objetos anidados: la única superficie JSON aceptada en este
 * payload es el mapa plano { "item": respuesta }, no estructuras arbitrarias
 * (previene JSON smuggling y DoS por profundidad de objeto).
 */
final class ChecklistAnswerIsScalar implements InvokableRule
{
    public const MAX_ANSWER_LENGTH = 255;

    /**
     * @param  Closure(string): void  $fail
     */
    public function __invoke(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_bool($value) || is_int($value) || is_float($value)) {
            return;
        }

        if (is_string($value) && mb_strlen($value) <= self::MAX_ANSWER_LENGTH) {
            return;
        }

        $fail('Cada respuesta debe ser verdadero/falso, un número o un texto de máximo '
            .self::MAX_ANSWER_LENGTH.' caracteres.');
    }
}
