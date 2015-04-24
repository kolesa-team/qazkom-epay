<?php
namespace Epay\Exceptions\Amount;

use Epay\Exceptions\Exception;

/**
 * Исключение пустой суммы.
 */
class EmptyAmount extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 400;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Nothing to charge.';
}
