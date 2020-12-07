<?php

namespace GitPHP\Cache;

class Cache
{
	/**
	 * Template
	 *
	 * Cache template
	 */
	const Template = 'data.tpl';

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
	 * GetInstance
	 *
	 * Return the singleton instance
	 *
	 * @access public
	 * @static
	 * @return mixed instance of cache class
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new \GitPHP\Cache\Cache();
		}
		return self::$instance;
	}

	/**
	 * tpl
	 *
	 * Smarty instance
	 *
	 * @access protected
     * @var \Smarty
	 */
	protected $tpl = null;

	/**
	 * enabled
	 *
	 * Stores whether the cache is enabled
	 *
	 * @access protected
	 */
	protected $enabled = false;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		if (\GitPHP\Config::GetInstance()->GetValue('objectcache', false)) {
            $this->SetEnabled(true);
        }
	}

	/**
	 * GetEnabled
	 *
	 * Gets whether the cache is enabled
	 *
	 * @access public
	 * @return boolean true if enabled
	 */
	public function GetEnabled()
	{
		return $this->enabled;
	}

	/**
	 * SetEnabled
	 *
	 * Sets whether the cache is enabled
	 *
	 * @access public
	 * @param boolean $enable true to enable, false to disable
	 */
	public function SetEnabled($enable)
	{
		if ($enable == $this->enabled)
			return;

		$this->enabled = $enable;

		if ($this->enabled)
			$this->CreateSmarty();
		else
			$this->DestroySmarty();
	}

	/**
	 * Get
	 *
	 * Get an item from the cache
	 *
	 * @access public
	 * @param string $key cache key
	 * @return mixed the cached object, or false
	 */
	public function Get($key = null)
	{
		if (empty($key))
			return false;

		if (!$this->enabled)
			return false;

		if (!$this->tpl->is_cached(\GitPHP\Cache\Cache::Template, $key))
			return false;

		$data = $this->tpl->fetch(\GitPHP\Cache\Cache::Template, $key);

		return unserialize($data);
	}

	/**
	 * Set
	 *
	 * Set an item in the cache
	 *
	 * @access public
	 * @param string $key cache key
	 * @param mixed $value value
	 */
	public function Set($key = null, $value = null)
	{
		if (empty($key) || empty($value))
			return;

		if (!$this->enabled)
			return;

		$this->Delete($key);
		$this->tpl->clear_all_assign();
		$this->tpl->assign('data', serialize($value));

		// Force it into smarty's cache
		$tmp = $this->tpl->fetch(\GitPHP\Cache\Cache::Template, $key);
		unset($tmp);
	}

	/**
	 * Exists
	 *
	 * Tests if a key is cached
	 *
	 * @access public
	 * @param string $key cache key
	 * @return boolean true if cached, false otherwise
	 */
	public function Exists($key = null)
	{
		if (empty($key))
			return false;

		if (!$this->enabled)
			return false;

		return $this->tpl->is_cached(\GitPHP\Cache\Cache::Template, $key);
	}

	/**
	 * Delete
	 *
	 * Delete an item from the cache
	 *
	 * @access public
	 * @param string $key cache key
	 */
	public function Delete($key = null)
	{
		if (empty($key))
			return;

		if (!$this->enabled)
			return;

		$this->tpl->clear_cache(\GitPHP\Cache\Cache::Template, $key);
	}

	/**
	 * Clear
	 *
	 * Clear the cache
	 *
	 * @access public
	 */
	public function Clear()
	{
		if (!$this->enabled)
			return;

		$this->tpl->clear_all_cache();
	}

	/**
	 * CreateSmarty
	 *
	 * Instantiates Smarty cache
	 *
	 * @access private
	 */
	private function CreateSmarty()
	{
		if ($this->tpl)
			return;

		$this->tpl = new \Smarty;
        $this->tpl->template_dir = GITPHP_TEMPLATESDIR;
        $this->tpl->compile_dir = GITPHP_TEMPLATESCACHEDIR;

		$this->tpl->caching = 2;

		$this->tpl->cache_lifetime = \GitPHP\Config::GetInstance()->GetValue('objectcachelifetime', 86400);

		$servers = \GitPHP\Config::GetInstance()->GetValue('memcache', null);
		if (isset($servers) && is_array($servers) && (count($servers) > 0)) {
			\GitPHP\Cache\Memcache::GetInstance()->AddServers($servers);
			$this->tpl->cache_handler_func = 'memcache_cache_handler';
		}

	}

	/**
	 * DestroySmarty
	 *
	 * Destroys Smarty cache
	 *
	 * @access private
	 */
	private function DestroySmarty()
	{
		if (!$this->tpl)
			return;

		$this->tpl = null;
	}
}
