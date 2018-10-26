<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 26/10/18
 */

namespace AppBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class TokenGenerator implements TokenGeneratorInterface
{
    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * TokenGenerator constructor.
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function generateToken()
    {
        return rtrim(strtr(base64_encode($this->getRandomNumber()), '+/', '-_'), '=');
    }

    /**
     * @return string
     */
    private function getRandomNumber()
    {
        return hash('sha256', uniqid(mt_rand(), true), true);
    }
}