<?php
namespace Epay\Exceptions\Common;

use Epay\Exceptions\Exception;

/**
 * Исключение несуществующего конфига.
 */
class ConfigNotFound extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 101;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = 'Config file does not exist.';
}
