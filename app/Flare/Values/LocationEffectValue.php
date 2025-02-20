<?php

namespace App\Flare\Values;

class LocationEffectValue {

    /**
     * @var string $value
     */
    private $value;

    const INCREASE_STATS_BY_TWO_HUNDRED_FIFTY  = 0;
    const INCREASE_STATS_BY_FIVE_HUNDRED       = 1;
    const INCREASE_STATS_BY_ONE_THOUSAND       = 2;
    const INCREASE_STATS_BY_TWO_THOUSAND       = 3;
    const INCREASE_STATS_BY_THREE_THOUSAND     = 4;

    protected static $values = [
        0 => self::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
        1 => self::INCREASE_STATS_BY_FIVE_HUNDRED,
        2 => self::INCREASE_STATS_BY_ONE_THOUSAND,
        3 => self::INCREASE_STATS_BY_TWO_THOUSAND,
        4 => self::INCREASE_STATS_BY_THREE_THOUSAND,
    ];

    /**
     * @var string[] $values
     */
    protected static $namedValues = [
        self::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY => 'Increases monster stats by two hundred and fifty',
        self::INCREASE_STATS_BY_FIVE_HUNDRED      => 'Increases monster stats by five hundred',
        self::INCREASE_STATS_BY_ONE_THOUSAND      => 'Increases monster stats by one thousand',
        self::INCREASE_STATS_BY_TWO_THOUSAND      => 'Increases monster stats by two thousand',
        self::INCREASE_STATS_BY_THREE_THOUSAND    => 'Increases monster stats by three thousand',
    ];

    protected static $integerValues = [
        self::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY  => 250,
        self::INCREASE_STATS_BY_FIVE_HUNDRED       => 500,
        self::INCREASE_STATS_BY_ONE_THOUSAND       => 1000,
        self::INCREASE_STATS_BY_TWO_THOUSAND       => 2000,
        self::INCREASE_STATS_BY_THREE_THOUSAND     => 3000,
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws \Exception
     */
    public function __construct(int $value)
    {

        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Fetch the drop rate for a location based on monster strength.
     *
     * @return float
     */
    public function fetchDropRate(): float {
        switch ($this->value) {
            case self::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY:
                return 0.02;
            case self::INCREASE_STATS_BY_FIVE_HUNDRED:
                return 0.05;
            case self::INCREASE_STATS_BY_ONE_THOUSAND:
                return 0.08;
            case self::INCREASE_STATS_BY_TWO_THOUSAND:
                return 0.10;
            case self::INCREASE_STATS_BY_THREE_THOUSAND:
                return 0.14;
            default:
                // @codeCoverageIgnoreStart
                return 0.0;
                // @codeCoverageIgnoreEnd
        }
    }

    public static function fetchPercentageIncrease($value): float {
        switch ($value) {
            case self::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY:
                return 0.05;
            case self::INCREASE_STATS_BY_FIVE_HUNDRED:
                return 0.10;
            case self::INCREASE_STATS_BY_ONE_THOUSAND:
                return 0.25;
            case self::INCREASE_STATS_BY_TWO_THOUSAND:
                return 0.50;
            case self::INCREASE_STATS_BY_THREE_THOUSAND:
                return 0.70;
            default:
                // @codeCoverageIgnoreStart
                return 0.0;
                // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Get the named value.
     *
     * @return array
     */
    public static function getNamedValues(): array {
        return self::$namedValues;
    }

    /**
     * Get increase value name
     *
     * @param $value
     * @return string
     */
    public static function getIncreaseName($value): string {
        return self::$namedValues[$value];
    }

    /**
     * Get increase amount.
     *
     * @param $value
     * @return int
     */
    public static function getIncreaseByAmount($value): int {
        return self::$integerValues[$value];
    }
}
