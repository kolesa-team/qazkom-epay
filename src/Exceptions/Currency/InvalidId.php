<?php
namespace Epay\Exceptions\Currency;

use Epay\Exceptions\Exception;

/**
 * Исключение неверного кода валюты.
 */
class InvalidId extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 301;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Invalid currency code';
}
