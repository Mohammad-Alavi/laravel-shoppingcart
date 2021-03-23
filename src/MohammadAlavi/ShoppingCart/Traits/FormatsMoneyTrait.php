<?php

namespace MohammadAlavi\ShoppingCart\Traits;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;

trait FormatsMoneyTrait
{
	/**
	 * @param Money $value
	 *
	 * @return array
	 */
	public function formatMoneyAsArray(Money $value): array
	{
		return [
			'amount' => $this->formatMoney($value),
			'currency' => $value->getCurrency(),
		];
	}

	/**
	 * Format a money string
	 *
	 * @param Money $value
	 *
	 * @return string
	 */
	public function formatMoney(Money $value): string
	{
		$currencies = new ISOCurrencies();
		$moneyFormatter = new DecimalMoneyFormatter($currencies);

		return $moneyFormatter->format($value);
	}

	/**
	 * @param Money $value
	 * @param bool $appendCurrency Whether the currency (e.g., EUR) should be appended or prepended
	 *
	 * @return string
	 */
	public function formatMoneyAsSimpleString(Money $value, $appendCurrency = true): string
	{
		$str = $this->formatMoney($value);
		if ($appendCurrency) {
			$str .= ' ' . $value->getCurrency();
		} else {
			$str = $value . ' ' . $str;
		}

		return $str;
	}
}