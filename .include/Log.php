<?php

namespace GitPHP;

class Log
{
	/**
	 * instance
	 *
	 * Stores the singleton instance
	 *
	 * @access protected
	 * @static
	 */
	protected static $instance;

	/**
	 * enabled
	 *
	 * Stores whether logging is enabled
	 *
	 * @access protected
	 */
	protected $enabled = true;

	/**
	 * startTime
	 *
	 * Stores the starting instant
	 *
	 * @access protected
	 */
	protected $startTime;

	/**
	 * startMem
	 *
	 * Stores the starting memory
	 *
	 * @access protected
	 */
	protected $startMem;

	/**
	 * entries
	 *
	 * Stores the log entries
	 *
	 * @access protected
	 */
	protected $entries = array();

    protected $timers = array();

    protected $gtimers = array();

	/**
	 * GetInstance
	 *
	 * Returns the singleton instance
	 *
	 * @access public
	 * @static
	 * @return \GitPHP\Log
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new \GitPHP\Log();
		}

		return self::$instance;
	}

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		$this->startTime = microtime(true);
		$this->startMem = memory_get_usage();

		$this->enabled = \GitPHP\Config::GetInstance()->GetValue('debug', false);
	}

	/**
	 * SetStartTime
	 *
	 * Sets start time
	 *
	 * @access public
	 * @param float $start starting microtime
	 */
	public function SetStartTime($start)
	{
		$this->startTime = $start;
	}

	/**
	 * SetStartMemory
	 *
	 * Sets start memory
	 *
	 * @access public
	 * @param integer $start starting memory
	 */
	public function SetStartMemory($start)
	{
		$this->startMem = $start;
	}

    /**
     * Log
     *
     * Log an entry
     *
     * @param $name
     * @param null $value
     * @param null $time
     */
	public function Log($name, $value = null, $time = null)
	{
		if (!$this->enabled)
			return;

		$entry = array();
        $entry['time'] = $time;
		$entry['mem'] = memory_get_usage();
		$entry['name'] = $name;
		$entry['value'] = $value;
        $bt = explode("\n", new \Exception());
        array_shift($bt);
        array_shift($bt);
        $entry['bt'] = implode("\n", $bt);
        $entry['level'] = count($this->timers);
		$this->entries[] = $entry;
	}

    public function timerStart()
    {
        array_push($this->timers, microtime(true));
    }

    public function timerStop($name, $value = null)
    {
        $timer = array_pop($this->timers);
        $duration = microtime(true) - $timer;
        foreach ($this->timers as &$item) $item += $duration;
        $this->Log($name, $value, $duration);
        if (!isset($this->gtimers[$name])) {
            $this->gtimers[$name] = array(
                'count' => 0,
                'time' => 0,
            );
        }
        $this->gtimers[$name]['count']++;
        $this->gtimers[$name]['time'] += $duration;
    }

	/**
	 * SetEnabled
	 *
	 * Sets whether logging is enabled
	 *
	 * @access public
	 * @param boolean $enable true if logging is enabled
	 */
	public function SetEnabled($enable)
	{
		$this->enabled = $enable;
	}

    public function printHtmlHeader()
    {
        if (!$this->enabled) return;
        ?>
        <script type="text/javascript">
            function bt_toggle(id) {
                var el = document.getElementById(id);
                el.style.display = ((el.style.display == 'none') ? 'block' : 'none');
            }
            function more_toggle(id_short, id_long) {
                var el_short = document.getElementById(id_short);
                var el_long = document.getElementById(id_long);
                el_short.style.display = ((el_short.style.display == 'none') ? 'block' : 'none');
                el_long.style.display = ((el_short.style.display == 'none') ? 'block' : 'none');
            }
            var statslow_requests = [];
            function statslow_add_request_selector(idx) {
                var button_el = document.createElement('span');
                button_el.classList.add('ss_toggle');
                button_el.addEventListener('click', function() {
                    document.getElementById('statslow_content').innerHTML = statslow_requests[idx];
                });
                button_el.innerHTML = 'req' + idx;
                document.getElementById('statslow_request_selector').appendChild(button_el);
            }
            function statslow_add_request(html) {
                statslow_requests.push(html);
                statslow_add_request_selector(statslow_requests.length - 1);
            }
            window.addEventListener('DOMContentLoaded', function() {
                statslow_requests.push(document.getElementById('statslow_content').innerHTML);
                statslow_add_request_selector(0);
            });
        </script>
        <style type="text/css">
            .statslow, .statslow td {
                font: 11px Menlo, Monaco, "Courier New", monospace !important;
            }
            .statslow {
                border: 0;
                border-spacing: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .ss_toggle {
                color: #88a; border-bottom: 1px dashed blue;
                display: inline-block;
                margin: 3px;
                cursor: pointer;
            }
            .ss_key {
                background: #ccf; border-bottom: 1px solid #888;
                max-width: 110px;
                word-wrap: break-word;
            }
            .ss_value {
                background: #ccc; border-bottom: 1px solid #888;
                white-space: pre;
                max-width: 900px;
                word-wrap: break-word;
            }
            .ss_bt {
                white-space: pre;
            }
            .ss_time {
                background: #cff; border-bottom: 1px solid #888;
            }
        </style>
        <div id="statslow_request_selector"></div>
        <table class="statslow"><tbody id="statslow_content">
        <?php
    }

    public function printHtmlFooter()
    {
        if (!$this->enabled) return;
        echo '</tbody></table>';
    }

    public function printHtml()
    {
        if (!$this->enabled) return;

        foreach ($this->gtimers as $name => $timer) {
            if ($timer['count'] < 2) continue;
            $this->Log("g $name", $timer['count'], $timer['time']);
        }

        $this->Log('TOTAL', null, microtime(true) - $this->startTime);

        foreach ($this->entries as $i => $e) {
            $bt_id = 'bt_' . $i;
            $more_short = 'more_short_' . $i;
            $more_long = 'more_long_' . $i;
            list ($short, $long) = $this->shorten($e['value'], 3, 300, 1000);
            if ($long) {
                $short = "<span class='ss_toggle' onclick='more_toggle(\"$more_short\", \"$more_long\");'>more</span>"
                    . "<span id='$more_short'>" . htmlspecialchars($short) . "</span>"
                    . "<span style='display: none;' id='$more_long'>" . htmlspecialchars($long) . "</span>";
            }
            echo "<tr>"
                . "<td class='ss_key'>" . str_repeat('&nbsp;', 2 * $e['level']) . "$e[name]</td>"
                . "<td class='ss_value'><span class='ss_toggle' onclick='bt_toggle(\"$bt_id\");'>trace</span>&nbsp;<div style='display: none;' class='ss_bt' id='$bt_id'>$e[bt]</div>$short</td>"
                . "<td class='ss_time'>" . ($e['time'] ? sprintf("%.3f", $e['time']) : '') . "</td>"
                . "</tr>";
        }
    }

    public function getForJson()
    {
        ob_start();
        $this->printHtml();
        $result = ob_get_clean();
        return $result;
    }

    public function getGTimers()
    {
        return $this->gtimers;
    }

    protected function shorten($str, $new_lines_limit, $chars_limit, $long_limit)
    {
        $len = strlen($str);
        $newlines_count = substr_count($str, "\n");
        if ($len > $chars_limit || $newlines_count > $new_lines_limit) {
            /* take no more than $new_lines_limit lines and $chars_limit characters */
            for ($i = 0, $pos = 0; $i < $new_lines_limit && $pos < $chars_limit; $i++) $pos = strpos($str, "\n", $pos) + 1;
            $long_limit_cut = $len - $long_limit;

            $long = substr($str, 0, $long_limit);
            if ($long_limit_cut > 0) {
                $long .= "... $long_limit_cut characters more";
            }
            return [substr($str, 0, $pos), $long];
        }
        return [$str, null];
    }
}
