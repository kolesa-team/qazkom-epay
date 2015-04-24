<?php
namespace Epay\Exceptions\Currency;

use Epay\Exceptions\Exception;

/**
 * Исключение пустого идентификатора валюты.
 */
class EmptyId extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 300;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Empty Currency code';
}
