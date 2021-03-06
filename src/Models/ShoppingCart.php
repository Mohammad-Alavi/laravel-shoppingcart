<?php

namespace MohammadAlavi\ShoppingCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use MohammadAlavi\ShoppingCart\Contracts\Buyable;
use MohammadAlavi\ShoppingCart\Exceptions\InvalidShoppingCartRowException;
use MohammadAlavi\ShoppingCart\Traits\FormatsMoneyTrait;
use Money\Money;

class ShoppingCart extends Model
{
	use FormatsMoneyTrait;

	const DEFAULT_NAME = 'default';

	protected $table = 'shoppingcarts';

	protected $fillable = [
		'identifier',
		'name',
		'content',
	];

	protected $hidden = [];

	protected $casts = [];

	protected $dates = [
		'created_at',
		'updated_at',
	];

	/**
	 * Load a cart from the database. If no cart exists, an empty cart is returned
	 *
	 * @param array|string $identifier
	 * @param null $name
	 *
	 * @return mixed
	 */
	public function load($identifier, $name = null)
	{
		$name = $name ?: self::DEFAULT_NAME;

		$classname = config('shoppingcart.models.shoppingcart');
		$shoppingcart = $classname::firstOrNew($this->defaultValues($identifier, $name));

		return $shoppingcart;
	}

	/**
	 * @param $identifier
	 * @param $name
	 *
	 * @return array
	 */
	private function defaultValues($identifier, $name): array
	{
		return [
			'identifier' => $identifier,
			'name' => $name,
		];
	}

	/**
	 * Add an item to the cart.
	 *
	 * @param mixed $id
	 * @param mixed $name
	 * @param mixed $type
	 * @param int|float $qty
	 * @param Money $price
	 * @param null $uri
	 * @param array $options
	 *
	 * @return ShoppingCart
	 */
	public function addItem($id, $name = null, $type = null, $qty = 1, Money $price = null, $uri = null, array $options = []): ShoppingCart
	{
		$cartItem = CartItem::fromAttributes($id, $name, $type, $price, $uri, $options);

		return $this->addItemToCart($cartItem, $qty);
	}

	/**
	 * @param CartItem $cartItem
	 * @param int $qty
	 *
	 * @return $this
	 */
	private function addItemToCart(CartItem $cartItem, $qty = 1): self
	{
		$cartItem->setQuantity($qty);
		$cartItem->setTaxRate(config('shoppingcart.tax'));

		$content = $this->getContent();

		if ($content->has($cartItem->rowId)) {
			$cartItem->setQuantity($cartItem->getQuantity() + $content->get($cartItem->rowId)->qty);
		}

		$content->put($cartItem->rowId, $cartItem);

		$this->content = serialize($content);

		$this->save();

		return $this;
	}

	/**
	 * @return Collection
	 */
	public function getContent(): Collection
	{
		if (null === $this->content) {
			return collect();
		}

		return unserialize($this->content);
	}

	/**
	 * @param Buyable $item
	 * @param int $qty
	 * @param array $options
	 *
	 * @return ShoppingCart
	 */
	public function addBuyable(Buyable $item, $qty = 1, array $options = []): ShoppingCart
	{
		$cartItem = CartItem::fromBuyable($item, $options);

		return $this->addItemToCart($cartItem, $qty);
	}

	/**
	 * Remove a specified row from the shoppingcart
	 *
	 * @param $row
	 *
	 * @return $this
	 */
	public function removeItem($row): self
	{
		$content = $this->getContent();

		// the cart contains this row - so remove it
		if ($content->has($row)) {
			$content->pull($row);
			$this->content = serialize($content);

			$this->save();
		}

		return $this;
	}

	/**
	 * Removes the cart from the database
	 */
	public function clear()
	{
		$this->delete();
	}

	/**
	 * Update the cart item with the given rowId.
	 *
	 * @param string $row
	 * @param mixed $qty
	 * @param array $options
	 *
	 * @return ShoppingCart
	 */
	public function updateItem($row, $qty = 1, array $options = []): ShoppingCart
	{
		$cartItem = $this->getRow($row);

		$cartItem->updateItem($qty, $options);

		$content = $this->getContent();

		$content->put($cartItem->rowId, $cartItem);

		$this->content = serialize($content);

		$this->save();

		return $this;
	}

	/**
	 * Get a cart item from the cart by its rowId.
	 *
	 * @param string $row
	 *
	 * @return CartItem
	 * @throws InvalidShoppingCartRowException
	 */
	private function getRow($row): CartItem
	{
		$content = $this->getContent();

		if (!$content->has($row)) {
			throw new InvalidShoppingCartRowException();
		}

		return $content->get($row);
	}

	/**
	 * @return int
	 */
	public function getItemCount(): int
	{
		return $this->getContent()->count();
	}

	/**
	 * Get the total price of the items in the cart.
	 *
	 * @return Money
	 */
	public function getTotal(): Money
	{
		$content = $this->getContent();

		$total = $content->reduce(function (Money $total, CartItem $cartItem) {
			return $total->add($cartItem->getTotal());
		}, new Money(0, Config::get('shoppingcart.currency')));

		return $total;
	}

	/**
	 * Get the total tax of the items in the cart.
	 *
	 * @return Money
	 */
	public function getTax(): Money
	{
		$content = $this->getContent();

		$tax = $content->reduce(function (Money $tax, CartItem $cartItem) {
			return $tax->add($cartItem->getTaxTotal());
		}, new Money(0, Config::get('shoppingcart.currency')));

		return $tax;
	}

	/**
	 * Get the subtotal (total - tax) of the items in the cart.
	 *
	 * @return Money
	 */
	public function getSubTotal(): Money
	{
		$content = $this->getContent();

		$subTotal = $content->reduce(function (Money $subTotal, CartItem $cartItem) {
			return $subTotal->add($cartItem->getSubtotal());
		}, new Money(0, Config::get('shoppingcart.currency')));

		return $subTotal;
	}
}
