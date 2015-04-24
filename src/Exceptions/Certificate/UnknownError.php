<?php
namespace Epay\Exceptions\Certificate;

use Epay\Exceptions\Exception;

/**
 * Исключение ошибки неизвестного генезиса.
 */
class UnknownError extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 255;
}
