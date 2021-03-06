<?php

namespace Main;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

final class User implements AdvancedUserInterface
{
	private $username;
	private $password;
	private $userID;
	private $enabled;
	private $accountNonExpired;
	private $credentialsNonExpired;
	private $accountNonLocked;
	private $roles;

	public function __construct($username, $password, $userid, array $roles = array(), $enabled = true, $userNonExpired = true, $credentialsNonExpired = true, $userNonLocked = true) {
		if ('' === $username || null === $username) {
			throw new \InvalidArgumentException('The username cannot be empty.');
		}
		//future exceptions? no null id/pw ?
		//future activate with email, add to database is_allowed

		$this->userID = $userid;
		$this->username = $username;
		$this->password = $password;
		$this->enabled = $enabled;
		$this->accountNonExpired = $userNonExpired;
		$this->credentialsNonExpired = $credentialsNonExpired;
		$this->accountNonLocked = $userNonLocked;
		$this->roles = $roles;
	}

	public function __toString() {
		return $this->getUsername();
	}

	public function getUsername() {
		return $this->username;
	}

	public function getRoles() {
		return $this->roles;
	}

	public function getID() {
		return $this->userID;
	}

	public function getPassword() {
		return $this->password;
	}

	public function setPassword($inPass): bool {
		return null;
	}

	public function getSalt() {
		return null;
	}

	public function isAccountNonExpired() {
		return $this->accountNonExpired;
	}

	public function isAccountNonLocked() {
		return $this->accountNonLocked;
	}

	public function isCredentialsNonExpired() {
		return $this->credentialsNonExpired;
	}

	public function isEnabled() {
		return $this->enabled;
	}

	public function eraseCredentials() {
	}
}
