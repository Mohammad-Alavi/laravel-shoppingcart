<?php

namespace MohammadAlavi\ShoppingCart\Models;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use MohammadAlavi\ShoppingCart\Contracts\Buyable;
use MohammadAlavi\ShoppingCart\Traits\FormatsMoneyTrait;
use Money\Money;

class CartItem implements Arrayable
{
	use FormatsMoneyTrait;

	/**
	 * The rowID of the cart item.
	 *
	 * @var string
	 */
	private $rowId;

	/**
	 * The ID of the cart item.
	 *
	 * @var int|string
	 */
	private $id;

	/**
	 * The quantity for this cart item.
	 *
	 * @var int
	 */
	private $qty = 1;

	/**
	 * The name of the cart item.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The money object representing the price WITHOUT taxes
	 *
	 * @var null
	 */
	private $price = null;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $uri;

	/**
	 * The options for this cart item.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * The FQN of the associated model.
	 *
	 * @var string|null
	 */
	private $model = null;

	/**
	 * The real-ID of the associated model
	 *
	 * @var null
	 */
	private $modelId = null;

	/**
	 * The tax rate for the cart item.
	 *
	 * @var int|float
	 */
	private $taxRate = 0;

	/**
	 * CartItem constructor.
	 *
	 * @param int|string $id
	 * @param string $name
	 * @param string $type
	 * @param Money $price
	 * @param string $uri
	 * @param array $options
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct($id, $name, $type, Money $price, $uri = null, array $options = [])
	{
		if (empty($id)) {
			throw new InvalidArgumentException('Please supply a valid identifier.');
		}

		if (empty($name)) {
			throw new InvalidArgumentException('Please supply a valid name.');
		}

		if (empty($type)) {
			throw new InvalidArgumentException('Please supply a valid type.');
		}

		if ($price->isNegative()) {
			throw new InvalidArgumentException('Please supply a valid price.');
		}

		$this->id = $id;
		$this->name = $name;
		$this->type = $type;
		$this->price = $price;
		$this->uri = $uri;
		$this->options = new CartItemOptions($options);
		$this->rowId = $this->generateRowId($id, $options);
	}

	/**
	 * Generate a unique id for the cart item.
	 *
	 * @param string $id
	 * @param array $options
	 * @return string
	 */
	protected function generateRowId($id, array $options): string
	{
		ksort($options);

		return md5($id . serialize($options));
	}

	/**
	 * Create a new instance from a Buyable.
	 *
	 * @param Buyable $item
	 * @param array $options
	 *
	 * @return CartItem
	 */
	public static function fromBuyable(Buyable $item, array $options = []): CartItem
	{
		$cartItem = new self(
			$item->getBuyableIdentifier($options),
			$item->getBuyableDescription($options),
			$item->getBuyableType($options),
			$item->getBuyablePrice($options),
			$item->getBuyableURI($options),
			$options
		);

		$cartItem->setModel($item);

		return $cartItem;
	}

	/**
	 * Create a new instance from the given attributes.
	 *
	 * @param int|string $id
	 * @param string $name
	 * @param string $type
	 * @param Money $price
	 * @param string $uri
	 * @param array $options
	 *
	 * @return CartItem
	 */
	public static function fromAttributes($id, $name, $type, $price, $uri, array $options = []): CartItem
	{
		$cartItem = new self($id, $name, $type, $price, $uri, $options);

		return $cartItem;
	}

	/**
	 * Set the quantity for this cart item.
	 *
	 * @param int $qty
	 *
	 * @throws InvalidArgumentException
	 */
	public function setQuantity($qty)
	{
		if (empty($qty) || !is_numeric($qty)) {
			throw new InvalidArgumentException('Please supply a valid quantity.');
		}

		$this->qty = $qty;
	}

	/**
	 * @return int
	 */
	public function getQuantity(): int
	{
		return $this->qty;
	}

	/**
	 * @param       $qty
	 * @param array $options
	 */
	public function updateItem($qty, $options = [])
	{
		$this->qty = $qty;
		$this->options = $options;
	}

	/**
	 * @return null|string
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * Associate the cart item with the given model.
	 *
	 * @param mixed $model
	 *
	 * @return CartItem
	 */
	public function setModel($model): CartItem
	{
		$this->model = get_class($model);
		$this->modelId = $model->id;

		return $this;
	}

	/**
	 * @return null|Buyable
	 */
	public function resolveModel()
	{
		if ($this->model == null) {
			return null;
		}

		try {
			$instance = with(new $this->model)->find($this->getModelId());
			return $instance;
		} catch (Exception $exception) {
			return null;
		}
	}

	/**
	 * @return mixed;
	 */
	private function getModelId()
	{
		return $this->modelId;
	}

	/**
	 * Set the tax rate.
	 *
	 * @param int $taxRate
	 *
	 * @return CartItem
	 * @throws InvalidArgumentException
	 */
	public function setTaxRate($taxRate): CartItem
	{
		if (empty($taxRate) || !is_numeric($taxRate)) {
			throw new InvalidArgumentException('Please supply a valid tax rate.');
		}

		$this->taxRate = $taxRate;

		return $this;
	}

	/**
	 * Get an attribute from the cart item or get the associated model.
	 *
	 * @param string $attribute
	 * @return mixed
	 */
	public function __get($attribute)
	{
		if (property_exists($this, $attribute)) {
			return $this->{$attribute};
		}

		return null;
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			'rowId' => $this->rowId,
			'id' => $this->id,
			'name' => $this->name,
			'type' => $this->type,
			'qty' => $this->qty,
			'uri' => $this->uri,

			'value' => [
				'currency' => $this->price->getCurrency(),

				'price' => $this->formatMoney($this->getPrice()),
				'subtotal' => $this->formatMoney($this->getSubtotal()),

				'taxes' => [
					'tax' => $this->formatMoney($this->getTax()),
					'rate' => (string)$this->taxRate,
					'total' => $this->formatMoney($this->getTaxTotal()),
				],

				'total' => $this->formatMoney($this->getTotal()),
			],

			'options' => $this->options,
		];
	}

	/**
	 * Returns the price without TAX.
	 *
	 * @return Money
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * Returns the subtotal (price for whole CartItem without TAX)
	 *
	 * @return Money
	 */
	public function getSubtotal(): Money
	{
		$value = $this->price->multiply($this->qty);
		return $value;
	}

	/**
	 * Returns the tax for one single item.
	 *
	 * @return Money
	 */
	public function getTax(): Money
	{
		$rate = $this->taxRate / 100;
		$value = $this->price->multiply($rate);
		return $value;
	}

	/**
	 * Returns the total taxes.
	 *
	 * @return Money
	 */
	public function getTaxTotal(): Money
	{
		$value = $this->getTax()->multiply($this->qty);
		return $value;
	}

	/**
	 * Returns the total price for whole CartItem with TAX
	 *
	 * @return Money
	 */
	public function getTotal(): Money
	{
		$value = $this->getPriceWithTax()->multiply($this->qty);
		return $value;
	}

	/**
	 * Returns the price with tax.
	 *
	 * @return Money
	 */
	public function getPriceWithTax(): Money
	{
		$value = $this->price->add($this->getTax());
		return $value;
	}
}
