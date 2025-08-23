<?php

namespace Litepie\Database\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

/**
 * Enhanced JSON cast with validation and transformation capabilities.
 */
class JsonCast implements CastsAttributes
{
    /**
     * Expected JSON schema for validation.
     *
     * @var array|null
     */
    protected ?array $schema = null;

    /**
     * Default value when null.
     *
     * @var mixed
     */
    protected mixed $default = [];

    /**
     * Whether to return associative array or object.
     *
     * @var bool
     */
    protected bool $associative = true;

    /**
     * Create a new cast instance.
     *
     * @param array|null $schema
     * @param mixed $default
     * @param bool $associative
     */
    public function __construct(?array $schema = null, mixed $default = [], bool $associative = true)
    {
        $this->schema = $schema;
        $this->default = $default;
        $this->associative = $associative;
    }

    /**
     * Cast the given value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes): mixed
    {
        if (is_null($value)) {
            return $this->default;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, $this->associative);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException(
                    "Invalid JSON in {$key}: " . json_last_error_msg()
                );
            }

            return $decoded ?? $this->default;
        }

        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return string|null
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        // Validate against schema if provided
        if ($this->schema && !$this->validateSchema($value)) {
            throw new InvalidArgumentException(
                "Value for {$key} does not match required schema"
            );
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                "Cannot encode {$key} to JSON: " . json_last_error_msg()
            );
        }

        return $encoded;
    }

    /**
     * Validate value against schema.
     *
     * @param mixed $value
     * @return bool
     */
    protected function validateSchema(mixed $value): bool
    {
        if (!$this->schema) {
            return true;
        }

        // Simple schema validation - can be extended with JSON Schema validator
        foreach ($this->schema as $field => $rules) {
            if (is_array($value) && array_key_exists($field, $value)) {
                $fieldValue = $value[$field];
                
                if (isset($rules['required']) && $rules['required'] && is_null($fieldValue)) {
                    return false;
                }
                
                if (isset($rules['type']) && !$this->validateType($fieldValue, $rules['type'])) {
                    return false;
                }
            } elseif (isset($rules['required']) && $rules['required']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate field type.
     *
     * @param mixed $value
     * @param string $expectedType
     * @return bool
     */
    protected function validateType(mixed $value, string $expectedType): bool
    {
        return match($expectedType) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'float' => is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            default => true
        };
    }

    /**
     * Create a cast instance with schema validation.
     *
     * @param array $schema
     * @return static
     */
    public static function withSchema(array $schema): static
    {
        return new static($schema);
    }

    /**
     * Create a cast instance that returns objects instead of arrays.
     *
     * @return static
     */
    public static function asObject(): static
    {
        return new static(null, new \stdClass(), false);
    }

    /**
     * Create a cast instance with a default value.
     *
     * @param mixed $default
     * @return static
     */
    public static function withDefault(mixed $default): static
    {
        return new static(null, $default);
    }
}
