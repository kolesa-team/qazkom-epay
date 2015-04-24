<?php
namespace Epay\Exceptions\Order;

use Epay\Exceptions\Exception;

/**
 * Исключение пустого идентификатора заказа.
 */
class NullId extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 201;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Null Order ID';
}
