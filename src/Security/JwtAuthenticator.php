<?php

declare(strict_types=1);

/*
 * This file is part of a Upply project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Class JwtAuthenticator.
 */
class JwtAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var JWTEncoderInterface
     */
    private $jwtEncoder;

    /**
     * @var int
     */
    private $tokenTtl;

    /**
     * JwtAuthenticator constructor.
     */
    public function __construct(EntityManagerInterface $em, JWTEncoderInterface $jwtEncoder, int $tokenTtl)
    {
        $this->em         = $em;
        $this->jwtEncoder = $jwtEncoder;
        $this->tokenTtl   = $tokenTtl;
    }

    /**
     * @return Response|void
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw new UnauthorizedHttpException($authException->getMessageKey());
    }

    /**
     * @return bool|false|mixed|string|string[]|void|null
     */
    public function getCredentials(Request $request)
    {
        if (!$request->headers->has('Authorization')) {
            return;
        }

        $extractor = new AuthorizationHeaderTokenExtractor(
            'Bearer',
            'Authorization'
        );

        if (!$token = $extractor->extract($request)) {
            return;
        }

        return $token;
    }

    /**
     * @throws JWTDecodeFailureException
     *
     * @return User|object|UserInterface|void|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (!$data = $this->jwtEncoder->decode($credentials)) {
            return;
        }

        // Expired Token
        $now = new \DateTime();
        if ($now->format('U') > ($data['iat'] + $this->tokenTtl)) {
            return;
        }

        $username = $data['username'];

        return $this->em->getRepository(User::class)->findOneBy(['username' => $username, 'active' => true]);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    /**
     * @return Response|void|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw new UnauthorizedHttpException('Authentication denied. User does not exist or the password is wrong.');
    }

    /**
     * @param string $providerKey
     *
     * @return Response|void|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * Does the authenticator support the given Request?
     *
     * If this returns false, the authenticator will be skipped.
     */
    public function supports(Request $request): bool
    {
        return false;
    }
}
