<?php
namespace GitPHP\Controller;

/**
 * Search controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class SearchText extends Base
{
    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @return string template filename
     */
    protected function GetTemplate()
    {
        return null;
    }

    protected function GetCacheKey()
    {
        return null;
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @access public
     * @param boolean $local true if caller wants the localized action name
     * @return string action name
     */
    public function GetName($local = false)
    {
        return 'searchtext';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    protected function ReadQuery()
    {
        $this->params['text'] = htmlspecialchars_decode(urldecode($_POST['text']));
        $this->params['project'] = $_POST['project'];
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    protected function LoadData()
    {
        $response = 'Nothing found...';
        $Project = \GitPHP_ProjectList::GetInstance()->GetProject($this->params['project']);
        if (!empty($Project)) {
            $branch = 'master';
            $search = $Project->SearchText($this->params['text'], $branch);
            if (!empty($search)) {
                $response = $search;
                //$response = str_replace(htmlspecialchars($this->params['text']), '<span class="highlight">' . htmlspecialchars($this->params['text']) . '</span>', $response);
                $response = preg_replace_callback(
                    '/(' . str_replace('/', '\/', $this->params['text']) . ')/',
                    function ($matches) {
                        return '[HIGHLIGHTSPANBEGIN]' . $matches['1'] . '[HIGHLIGHTSPANEND]';
                    },
                    $response
                );
                $otherVars = $this->params;

                $search = explode("\n", $response);
                foreach ($search as $key => $line) {
                    if (strpos($line, $branch . ':') === 0) {
                        list(, $file) = explode(':', $line);
                        $search[$key] = '[HIGHLIGHTFILEURL' . str_replace('/', '', strtoupper($file)) . ']';
                        $url = '/index.php?p=' . $otherVars['project'] . '&a=blob&hb=' . $branch . '&f=' . strip_tags($file);
                        $otherVars['fileurls'][$search[$key]] = '<a target="_blank" href="' . $url . '">' . $branch . ':' . $file . '</a>';
                    } elseif (!empty($line) && !empty($url)) {
                        list($num, $code) = explode(':', $line);
                        $search[$key] = '[HIGHLIGHTFILENUMURL' . str_replace('/', '', strtoupper($file . $num)) . ']';
                        $otherVars['fileurls'][$search[$key]] = '<a target="_blank" class="line-number" href="' . $url . '#' . $num . '">' . $num . '</a>:' . htmlspecialchars($code);
                    }
                }
                $response = implode("\n", $search);
            }
        }
        header('Content-Type: application/json; charset=UTF-8');
        $response = nl2br(htmlspecialchars($response));
        if (isset($otherVars) && count($otherVars['fileurls'])) {
            $response = str_replace(
                array_keys($otherVars['fileurls']),
                array_values($otherVars['fileurls']),
                $response
            );
        }
        $response = str_replace(
            array('[HIGHLIGHTSPANBEGIN]', '[HIGHLIGHTSPANEND]'),
            array('<span class="highlight">', '</span>'),
            $response
        );
        echo json_encode(array('response' => $response));
        die;
    }
}
