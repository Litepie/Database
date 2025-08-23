<?php

namespace Litepie\Database\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

/**
 * Enhanced Money cast with currency support and formatting.
 */
class MoneyCast implements CastsAttributes
{
    /**
     * Default currency code.
     *
     * @var string
     */
    protected string $currency;

    /**
     * Number of decimal places.
     *
     * @var int
     */
    protected int $precision;

    /**
     * Whether to store as smallest unit (cents).
     *
     * @var bool
     */
    protected bool $asSmallestUnit;

    /**
     * Create a new cast instance.
     *
     * @param string $currency
     * @param int $precision
     * @param bool $asSmallestUnit
     */
    public function __construct(string $currency = 'USD', int $precision = 2, bool $asSmallestUnit = true)
    {
        $this->currency = $currency;
        $this->precision = $precision;
        $this->asSmallestUnit = $asSmallestUnit;
    }

    /**
     * Cast the given value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return array|null
     */
    public function get($model, string $key, $value, array $attributes): ?array
    {
        if (is_null($value)) {
            return null;
        }

        // Handle JSON stored values
        if (is_string($value) && str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $amount = $decoded['amount'] ?? 0;
                $currency = $decoded['currency'] ?? $this->currency;
            } else {
                $amount = (float) $value;
                $currency = $this->currency;
            }
        } else {
            $amount = (float) $value;
            $currency = $this->currency;
        }

        // Convert from smallest unit if necessary
        if ($this->asSmallestUnit) {
            $amount = $amount / (10 ** $this->precision);
        }

        return [
            'amount' => round($amount, $this->precision),
            'currency' => $currency,
            'formatted' => $this->formatMoney($amount, $currency),
            'cents' => $this->asSmallestUnit ? (int) $value : (int) ($amount * (10 ** $this->precision)),
        ];
    }

    /**
     * Prepare the given value for storage.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return string|int|null
     */
    public function set($model, string $key, $value, array $attributes): string|int|null
    {
        if (is_null($value)) {
            return null;
        }

        $amount = null;
        $currency = $this->currency;

        // Handle different input formats
        if (is_array($value)) {
            $amount = $value['amount'] ?? $value['cents'] ?? 0;
            $currency = $value['currency'] ?? $this->currency;
            
            // If cents provided, convert to amount
            if (isset($value['cents']) && !isset($value['amount'])) {
                $amount = $value['cents'] / (10 ** $this->precision);
            }
        } elseif (is_string($value)) {
            // Remove currency symbols and formatting
            $cleanValue = preg_replace('/[^\d.-]/', '', $value);
            $amount = (float) $cleanValue;
        } elseif (is_numeric($value)) {
            $amount = (float) $value;
        } else {
            throw new InvalidArgumentException("Invalid money value: {$value}");
        }

        // Convert to smallest unit if required
        if ($this->asSmallestUnit) {
            $amount = $amount * (10 ** $this->precision);
        }

        // Store as JSON if currency is different from default
        if ($currency !== $this->currency) {
            return json_encode([
                'amount' => $this->asSmallestUnit ? (int) $amount : round($amount, $this->precision),
                'currency' => $currency,
            ]);
        }

        return $this->asSmallestUnit ? (int) $amount : round($amount, $this->precision);
    }

    /**
     * Format money for display.
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    protected function formatMoney(float $amount, string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        
        return $symbol . number_format($amount, $this->precision);
    }

    /**
     * Create a cast instance for a specific currency.
     *
     * @param string $currency
     * @param int $precision
     * @return static
     */
    public static function currency(string $currency, int $precision = 2): static
    {
        return new static($currency, $precision);
    }

    /**
     * Create a cast instance that stores as decimal (not smallest unit).
     *
     * @param string $currency
     * @param int $precision
     * @return static
     */
    public static function asDecimal(string $currency = 'USD', int $precision = 2): static
    {
        return new static($currency, $precision, false);
    }

    /**
     * Create a cast instance for cryptocurrencies.
     *
     * @param string $currency
     * @return static
     */
    public static function crypto(string $currency = 'BTC'): static
    {
        return new static($currency, 8, false);
    }
}
