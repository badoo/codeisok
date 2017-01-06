<?php

class GitPHP_Controller_GitosisApply extends GitPHP_Controller_GitosisBase
{
    protected function ReadQuery()
    {
        $this->ModelGitosis->addApplyRequest($this->Session->getUser()->getId());
    }

    protected function LoadData()
    {
        $this->redirect(empty($_SERVER['HTTP_REFERER']) ? '/?a=gitosis' : $_SERVER['HTTP_REFERER']);
    }
}
