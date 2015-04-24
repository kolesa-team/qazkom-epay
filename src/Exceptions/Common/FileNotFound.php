<?php
namespace Epay\Exceptions\Common;

use Epay\Exceptions\Exception;

/**
 * Исключение несуществующего XML-файла.
 */
class FileNotFound extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 100;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = '[FILE NOT FOUND]';
}
