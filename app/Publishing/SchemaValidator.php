<?php

namespace App\Publishing;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator;

/**
 * Validates data against the JSON Schemas in resources/schemas — the contract shared with
 *
 * @sbblog/core. Schema IDs live under https://schemas.sbblog.dev/.
 */
class SchemaValidator
{
    public const BASE_URI = 'https://schemas.sbblog.dev/';

    private readonly Validator $validator;

    public function __construct()
    {
        $this->validator = new Validator;
        $this->validator->setMaxErrors(5);
        $this->validator->resolver()?->registerPrefix(self::BASE_URI, resource_path('schemas'));
    }

    /**
     * @return list<string> Error messages; empty when valid.
     */
    public function errors(mixed $data, string $schema): array
    {
        $result = $this->validator->validate(Helper::toJSON($data), self::BASE_URI.$schema);

        if ($result->isValid()) {
            return [];
        }

        $errors = (new ErrorFormatter)->formatFlat($result->error());

        return array_values(array_map(strval(...), $errors));
    }

    public function isValid(mixed $data, string $schema): bool
    {
        return $this->errors($data, $schema) === [];
    }
}
