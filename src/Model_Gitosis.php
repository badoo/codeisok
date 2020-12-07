<?php

namespace GitPHP;

class Model_Gitosis
{
    private $db;

    public function __construct()
    {
        $this->db = \GitPHP\Db::getInstance();
    }

    public function getLastError()
    {
        return $this->db->getError();
    }

    /* User */

    public function getUsers()
    {
        return $this->db->getAssoc(static::QUERY_GET_USERS, array(), 'id');
    }

    public function getUser($id)
    {
        return $this->db->getRow(
            static::QUERY_GET_USER,
            array(
                'id' => (int)$id,
            )
        );
    }

    public function getUserByUsername($username)
    {
        return $this->db->getRow(
            static::QUERY_GET_USER_BY_USERNAME,
            array(
                'username' => $this->db->quote($username),
            )
        );
    }

    public function saveUser($username, $email, $public_key, $access_mode, $comment = '')
    {
        return $this->db->query(
            static::QUERY_SAVE_USER,
            array(
                'username'    => $this->db->quote($username),
                'email'       => $this->db->quote($email),
                'public_key'  => $this->db->quote($public_key),
                'access_mode' => $this->db->quote($access_mode),
                'comment'     => $this->db->quote($comment),
            )
        );
    }

    public function removeUser($id)
    {
        $id = (int)$id;
        $this->db->query(
            static::QUERY_REMOVE_USER,
            array(
                'id' => $id,
            )
        );
        $this->db->query(
            static::QUERY_REMOVE_USER_ACCESS,
            array(
                'user_id' => $id,
            )
        );
    }

    /* Access */

    public function getAccessGroupByUserId($user_id = 0)
    {
        $result = array();
        if (!empty($user_id)) {
            $data = $this->db->getAll(static::QUERY_GET_ACCESS_BY_USER_ID, array('user_id' => (int)$user_id));
        } else {
            $data = $this->db->getAll(static::QUERY_GET_ACCESS);
        }
        foreach ($data as $access) {
            if (!isset($result[$access['user_id']])) {
                $result[$access['user_id']] = array();
            }
            if (!isset($result[$access['user_id']][$access['mode']])) {
                $result[$access['user_id']][$access['mode']] = array();
            }

            $result[$access['user_id']][$access['mode']][] = $access['repository_id'];
        }

        return $result;
    }

    public function getAccessGroupByRepositoryId($repository_id = 0)
    {
        $result = array();
        if (!empty($repository_id)) {
            $data = $this->db->getAll(
                static::QUERY_GET_ACCESS_BY_REPOSITORY_ID,
                array('repository_id' => (int)$repository_id)
            );
        } else {
            $data = $this->db->getAll(static::QUERY_GET_ACCESS);
        }
        foreach ($data as $access) {
            if (!isset($result[$access['repository_id']])) {
                $result[$access['repository_id']] = array();
            }
            if (!isset($result[$access['repository_id']][$access['mode']])) {
                $result[$access['repository_id']][$access['mode']] = array();
            }

            $result[$access['repository_id']][$access['mode']][] = $access['user_id'];
        }

        return $result;
    }

    /**
     * @param $username
     * @param $repository
     * @return string
     */
    public function getUserAccessToRepository($username, $repository)
    {
        $data = $this->db->getRow(
            self::QUERY_GET_ACCESS_BY_USER_AND_REPO,
            ['username' => $this->db->quote($username), 'project' => $this->db->quote($repository)]
        );
        return $data['mode'] ?? '';
    }

    public function delUserAccess($user_id, $repositories_ids)
    {
        return $this->db->query(
            static::QUERY_DEL_USER_ACCESS,
            [
                'user_id'          => (int)$user_id,
                'repositories_ids' => $this->db->quote(array_map('intval', $repositories_ids), true),
            ]
        );
    }

    public function saveUserAccess($user_id, $repository_id, $mode)
    {
        return $this->db->query(
            static::QUERY_SAVE_USER_ACCESS,
            array(
                'user_id'       => (int)$user_id,
                'repository_id' => (int)$repository_id,
                'mode'          => $this->db->quote($mode),
            )
        );
    }

    /* Repository */

    public function getRepositories($is_display = null)
    {
        $display = array('Yes', 'No');
        if (true === $is_display) {
            $display = array('Yes');
        } else if (false === $is_display) {
            $display = array('No');
        }
        return $this->db->getAssoc(
            static::QUERY_GET_REPOSITORIES,
            array(
                'display' => $this->db->quote($display, true),
            ),
            'id'
        );
    }

    public function getRepository($id)
    {
        return $this->db->getRow(
            static::QUERY_GET_REPOSITORY,
            array(
                'id' => (int)$id,
            )
        );
    }

    public function getRepositoryByProject($project)
    {
        return $this->db->getRow(
            static::QUERY_GET_REPOSITORY_BY_PROJECT,
            array(
                'project' => $this->db->quote($project),
            )
        );
    }

    /**
     * Adds new repository with the specified parameters
     *
     * One may notice that there is no much difference between this method and saveRepository
     * Problem with saveRepository is that it executes SQL request with 'on duplicate key update' statement
     * This may lead to some security problems
     *
     * @param $project
     * @param $description
     * @param $category
     * @param $notify_email
     * @param $restricted
     * @param $display
     * @param $owner
     * @return bool|\GitPHP\Db_Result
     */
    public function addRepository($project, $description, $category, $notify_email, $restricted, $display, $owner)
    {
        return $this->db->query(
            static::QUERY_ADD_REPOSITORY,
            array(
                'project'      => $this->db->quote($project),
                'description'  => $this->db->quote($description),
                'category'     => $this->db->quote($category),
                'notify_email' => $this->db->quote($notify_email),
                'restricted'   => $this->db->quote($restricted),
                'display'      => $this->db->quote($display),
                'owner'        => $this->db->quote($owner),
            )
        );
    }

    public function saveRepository($project, $description, $category, $notify_email, $restricted, $display, $owner)
    {
        return $this->db->query(
            static::QUERY_SAVE_REPOSITORY,
            array(
                'project'      => $this->db->quote($project),
                'description'  => $this->db->quote($description),
                'category'     => $this->db->quote($category),
                'notify_email' => $this->db->quote($notify_email),
                'restricted'   => $this->db->quote($restricted),
                'display'      => $this->db->quote($display),
                'owner'        => $this->db->quote($owner),
            )
        );
    }

    /* User */
    const QUERY_GET_USERS = "SELECT * FROM #TBL_USER# ORDER BY username";

    const QUERY_GET_USER = "SELECT * FROM #TBL_USER# WHERE id = #id#";

    const QUERY_GET_USER_BY_USERNAME = "SELECT * FROM #TBL_USER# WHERE username = #username#";

    const QUERY_SAVE_USER = "INSERT INTO #TBL_USER#
            (username, email, public_key, access_mode, comment, created)
        VALUES (#username#, #email#, #public_key#, #access_mode#, #comment#, NOW())
        ON DUPLICATE KEY UPDATE
            username = #username#, email = #email#, public_key = #public_key#,
            access_mode = #access_mode#, comment = #comment#";

    const QUERY_REMOVE_USER = "DELETE FROM #TBL_USER# WHERE id = #id#";
    const QUERY_REMOVE_USER_ACCESS = "DELETE FROM #TBL_ACCESS# WHERE user_id = #user_id#";

    /* Access */
    const QUERY_GET_ACCESS = "SELECT * FROM #TBL_ACCESS#";
    const QUERY_GET_ACCESS_BY_USER_ID = "SELECT * FROM #TBL_ACCESS# where user_id=#user_id#";
    const QUERY_GET_ACCESS_BY_REPOSITORY_ID = "SELECT * FROM #TBL_ACCESS# where repository_id=#repository_id#";
    const QUERY_GET_ACCESS_BY_USER_AND_REPO = "select ac.mode from #TBL_ACCESS# ac
        inner join #TBL_USER# u on u.id=ac.user_id
        inner join #TBL_REPOSITORY# r on r.id=ac.repository_id
        where u.username=#username# and r.project=#project#";

    const QUERY_DEL_USER_ACCESS = "DELETE FROM #TBL_ACCESS#
        WHERE user_id = #user_id# AND repository_id IN(#repositories_ids#)";

    const QUERY_SAVE_USER_ACCESS = "INSERT INTO #TBL_ACCESS# (user_id, repository_id, mode)
        VALUES (#user_id#, #repository_id#, #mode#)
        ON DUPLICATE KEY UPDATE mode = #mode#";

    /* Repository */
    const QUERY_GET_REPOSITORIES = "SELECT * FROM #TBL_REPOSITORY# WHERE display IN(#display#) ORDER BY project";

    const QUERY_GET_REPOSITORY = "SELECT * FROM #TBL_REPOSITORY# WHERE id = #id#";

    const QUERY_GET_REPOSITORY_BY_PROJECT = "SELECT * FROM #TBL_REPOSITORY# WHERE project = #project#";

    const QUERY_ADD_REPOSITORY = "INSERT INTO #TBL_REPOSITORY#
            (project, description, category, notify_email, restricted, display, created, owner)
        VALUES (#project#, #description#, #category#, #notify_email#, #restricted#, #display#, NOW(), #owner#)";

    const QUERY_SAVE_REPOSITORY = "INSERT INTO #TBL_REPOSITORY#
            (project, description, category, notify_email, restricted, display, created, owner)
        VALUES (#project#, #description#, #category#, #notify_email#, #restricted#, #display#, NOW(), #owner#)
        ON DUPLICATE KEY UPDATE
            project = #project#, description = #description#, category = #category#, notify_email = #notify_email#,
            restricted = #restricted#, display = #display#, owner = #owner#";
}
