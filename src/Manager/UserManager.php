<?php

declare(strict_types=1);

/*
 * This file is part of a Upply project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Manager;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class UserManager.
 */
class UserManager extends BaseManager
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * UserManager constructor.
     */
    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManager, string $className = null)
    {
        parent::__construct($entityManager, $className);
        $this->userRepository = $userRepository;
    }
}
