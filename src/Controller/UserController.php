<?php

declare(strict_types=1);

/*
 * This file is part of a Upply project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Manager\UserManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends AbstractFOSRestController
{
    /**
     * @var JWTEncoderInterface
     */
    private $jwtEncoder;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * UserController constructor.
     */
    public function __construct(JWTEncoderInterface $jwtEncoder, UserPasswordEncoderInterface $passwordEncoder, UserManager $userManager)
    {
        $this->jwtEncoder      = $jwtEncoder;
        $this->passwordEncoder = $passwordEncoder;
        $this->userManager     = $userManager;
    }

    /**
     * @SWG\Post(
     *     path="/api/register",
     *     summary="Create an user",
     *     tags={"User"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @Model(type="App\Entity\User")
     *     ),
     *     @SWG\Response(
     *         response=201,
     *         description="Returns the user created",
     *         @Model(type="App\Entity\User", groups={"id", "user"})
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Bad Request"
     *     )
     * )
     * @Rest\Post("/api/register")
     *
     * @ParamConverter("user", converter="fos_rest.request_body")
     */
    public function postUsersAction(User $user): JsonResponse
    {
        $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));
        $this->userManager->save($user);

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => ['id', 'user']]);
    }

    /**
     * @SWG\Post(
     *     path="/api/login",
     *     tags={"User"},
     *     operationId="login",
     *     summary="Authentification",
     *      @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         @Model(type="App\Entity\User")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successful"
     *     ),
     *     @SWG\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     * @Rest\Post("/api/login", name="login")
     *
     * @throws JWTEncodeFailureException
     */
    public function tokenAuthenticationAction(Request $request): View
    {
        $user = $this->userManager->findOneBy(['username' => $request->request->get('username')]);

        /** @var User $user */
        if (!$user || !$this->passwordEncoder->isPasswordValid($user, $request->request->get('password'))) {
            throw new UnauthorizedHttpException('Basic', 'Authentication denied. User does not exist or the password is wrong.');
        }

        $now            = new \DateTime();
        $expirationTime = (string) ($now->format('U').$this->getParameter('token_ttl'));
        $token          = $this->jwtEncoder->encode([
            'exp'      => $expirationTime,
            'username' => $user->getUsername(),
        ]);

        return $this->view([
            'success' => true,
            'token'   => $token,
        ], Response::HTTP_OK)->setFormat($request->get('_format', 'json'));
    }
}
