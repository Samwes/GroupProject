<?php

namespace Main;

use Database\DBDataMapper;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
	/** @var DBDataMapper $DB */
	private $DB;

	public function __construct(DBDataMapper $DB) {
		$this->DB = $DB;
	}

	public function refreshUser(UserInterface $user) {
		if (!$user instanceof User) {
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
		}

		return $this->loadUserByUsername($user->getUsername());
	}

	public function loadUserByUsername($username): ?User {
		$user = $this->DB->getUserByUsername($username);

		if (false === $user) {
			throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
		}

		return new User($user['username'], $user['password'], $user['userid'], explode(',', $user['roles']), true, true, true, true);
	}

	public function supportsClass($class) {
		return $class === 'Main\User';
	}
}
