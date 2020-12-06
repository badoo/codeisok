<?php
/**
 * @team QA <qa@corp.badoo.com>
 * @maintainer Aleksandr Izmaylov <a.izmaylov@corp.badoo.com>
 */

namespace GitPHP\Controller;

/**
 * Interface ControllerInterface
 * @package GitPHP\Controller
 */
interface ControllerInterface
{
    /**
     * Called to render page
     * @return mixed
     */
    public function Render();
}
