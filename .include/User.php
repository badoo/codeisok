<?php

namespace GitPHP;

class User
{
    protected $id;
    protected $name = 'anonymous';
    protected $email;
    protected $token;
    protected $groups = [];
    protected $is_gitosis_admin;

    public static function fromAuthData($auth_data)
    {
        $id = ($auth_data && !empty($auth_data['user_id'])) ? $auth_data['user_id'] : null;

        $User = new self($id);
        if ($id) {
            $User->setToken($auth_data['user_token']);
            $User->setName($auth_data['user_name']);
            $User->setEmail($auth_data['user_email']);
            if (isset($auth_data['user_groups'])) {
                $User->setGroups($auth_data['user_groups']);
            }
        }
        return $User;
    }

    public function toAuthData()
    {
        return [
            'user_id' => $this->getId(),
            'user_token' => $this->getToken(),
            'user_name' => $this->getName(),
            'user_email' => $this->getEmail(),
            'user_groups' => $this->getGroups(),
        ];
    }

    public function __construct($user_id = null)
    {
        $this->id = $user_id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function isInGroup($group)
    {
        return isset($this->groups[$group]) ? $this->groups[$group] : null;
    }

    public function setInGroup($group, $presence)
    {
        return $this->groups[$group] = $presence;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    public function setIsGitosisAdmin($value)
    {
        $this->is_gitosis_admin = (bool)$value;
    }

    public function isGitosisAdmin()
    {
        return $this->is_gitosis_admin;
    }
}
