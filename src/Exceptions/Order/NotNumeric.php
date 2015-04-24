<?php
namespace Epay\Exceptions\Order;

use Epay\Exceptions\Exception;

/**
 * Исключение неверного формата идентификатора заказа.
 */
class NotNumeric extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 202;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Order ID must be number';
}
