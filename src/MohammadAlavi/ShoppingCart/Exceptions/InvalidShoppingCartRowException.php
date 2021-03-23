<?php

namespace MohammadAlavi\ShoppingCart\Exceptions;

use Exception;

class InvalidShoppingCartRowException extends Exception
{
    public $message = 'Invalid row for this shopping cart.';
}