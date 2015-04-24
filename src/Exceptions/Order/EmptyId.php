<?php
namespace Epay\Exceptions\Order;

use Epay\Exceptions\Exception;

/**
 * Исключение пустого идентификатора заказа.
 */
class EmptyId extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 200;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Empty Order ID';
}
