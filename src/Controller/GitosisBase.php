<?php

namespace GitPHP\Controller;

abstract class GitosisBase extends Base
{
    const DEFAULT_SECTION = 'users';

    /**
     * @var array
     */
    protected static $_sections = array('users', 'repositories', 'access');

    /**
     * @var array
     */
    protected $_form_errors = array();

    /**
     * @var \GitPHP\Model_Gitosis
     */
    protected $ModelGitosis;

    public static function getSections()
    {
        return static::$_sections;
    }

    public function __construct()
    {
        $this->ModelGitosis = new \GitPHP\Model_Gitosis();
        parent::__construct();
        if (!$this->Session->getUser()->isGitosisAdmin()) {
            $this->redirect('/');
        }
    }

    protected function GetTemplate()
    {
        return 'gitosis.tpl';
    }

    protected function GetCacheKey() {}

    public function GetName($local = false)
    {
        return 'gitosis';
    }

    protected function ReadQuery() {}

    protected function LoadData()
    {
        $this->tpl->assign('adminarea', 1);
        $this->tpl->assign('sections', static::$_sections);
        $this->tpl->assign(
            'current_section',
            $this->getCurrentSection()
        );
        $this->tpl->assign('form_errors', $this->_form_errors);
    }

    abstract protected function getCurrentSection();
}
