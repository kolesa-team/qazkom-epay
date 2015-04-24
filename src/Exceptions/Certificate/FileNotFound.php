<?php
namespace Epay\Exceptions\Certificate;

use Epay\Exceptions\Exception;

/**
 * Исключение при отсутствующем файле сертификата.
 */
class FileNotFound extends Exception
{
    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected $code = 4;

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $message = '[KEY_FILE_NOT_FOUND]';
}
