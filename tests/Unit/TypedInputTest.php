<?php

use App\Support\TypedInput;

it('converts validated integer inputs', function (int|string $input, int $expected) {
    expect(TypedInput::int($input))->toBe($expected)
        ->and(TypedInput::nullableInt($input))->toBe($expected);
})->with([
    'ASCII' => ['42', 42],
    'whitespace' => [' 42 ', 42],
    'zero' => ['0', 0],
    'negative' => ['-42', -42],
    'large integer without float rounding' => ['9007199254740993', 9007199254740993],
    'Persian digits' => [' ۴۲ ', 42],
    'Arabic-Indic digits' => ['٤٢', 42],
]);

it('keeps blank and omitted nullable numbers null', function () {
    expect(TypedInput::nullableInt(''))->toBeNull()
        ->and(TypedInput::nullableInt())->toBeNull()
        ->and(TypedInput::nullableFloat(''))->toBeNull()
        ->and(TypedInput::nullableFloat())->toBeNull();
});

it('converts a decimal without dropping its fraction', function () {
    expect(TypedInput::float(' -۳۵.۶۸۹۲ '))->toBe(-35.6892)
        ->and(TypedInput::nullableFloat('0'))->toBe(0.0);
});
