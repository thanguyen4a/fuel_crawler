<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Fuel Development Team
 * @license		MIT License
 * @copyright	2010 Dan Horrigan
 * @link		http://fuelphp.com
 */

namespace Fuel\Auth;
use Fuel\App;

abstract class Auth_Login_Driver extends App\Auth_Driver {

	public static function factory(Array $config = array())
	{
		// default driver id to driver name when not given
		! array_key_exists('id', $config) && $config['id'] = $config['driver'];

		if (array_key_exists('group_drivers', $config))
		{
			foreach ($config['group_drivers'] as $driver => $config)
			{
				$config = is_int($driver)
					? array('driver' => $config)
					: array_merge($config, array('driver' => $driver));
				$class = 'App\\Auth_Group_'.$config['driver'];
				$class::factory($config);
			}
		}
		if (array_key_exists('acl_drivers', $config))
		{
			foreach ($config['acl_drivers'] as $driver => $config)
			{
				$config = is_int($driver)
					? array('driver' => $config)
					: array_merge($config, array('driver' => $driver));
				$class = 'App\\Auth_Acl_'.$config['driver'];
				$class::factory($config);
			}
		}

		$class = 'App\\Auth_Login_'.ucfirst($config['driver']);
		$driver = new $class($config);

		return $driver;
	}

	// ------------------------------------------------------------------------

	/**
	 * @var	array	config values
	 */
	protected $config = array(
		'salt_prefix' => '',
		'salt_postfix' => ''
	);

	/**
	 * Check for login
	 * (final method to (un)register verification, work is done by _check())
	 *
	 * @return	bool
	 */
	final public function check()
	{
		if ( ! $this->perform_check())
		{
			App\Auth::_unregister_verified($this);
			return false;
		}

		App\Auth::_register_verified($this);
		return true;
	}

	/**
	 * Return user info in an array, always includes email & screen_name
	 * Additional fields can be requested in the first param or set in config,
	 * all additional fields must have their own method "get_user_" + fieldname
	 *
	 * @param	array	additional fields
	 * @return	array
	 */
	final public function get_user_array(Array $additional_fields = array())
	{
		$user = array(
			'email'			=> $this->get_user_email(),
			'screen_name'	=> $this->get_user_screen_name()
		);

		$additional_fields = array_merge($this->config['additional_fields'], $additional_fields);
		foreach($additional_fields as $af)
		{
			// only works if it actually can be fetched through a get_ method
			if (is_callable(array($this, $method = 'get_user_'.$af)))
			{
				$user[$af] = $this->$method();
			}
		}
		return $user;
	}

	/**
	 * Verify Group membership
	 *
	 * @param	mixed	group identifier to check for membership
	 * @param	string	group driver id or null to check all
	 * @param	array	user identifier to check in form array(driver_id, user_id)
	 * @return	bool
	 */
	public function member($group, $driver = null, $user = null)
	{
		$user = $user ?: $this->get_user_id();

		if ($driver === null)
		{
			foreach (App\Auth::group(true) as $group)
			{
				if ($group->group($group, $user))
				{
					return true;
				}
			}

			return false;
		}

		return App\Auth::group($driver)->member($group, $user);
	}

	/**
	 * Verify Acl access
	 *
	 * @param	mixed	condition to validate
	 * @param	string	acl driver id or null to check all
	 * @param	array	user identifier to check in form array(driver_id, user_id)
	 * @return	bool
	 */
	public function has_access($condition, $driver = null, $user = null)
	{
		$user = $user ?: $this->get_user_id();

		if ($driver === null)
		{
			foreach (App\Auth::acl(true) as $acl)
			{
				if ($acl->has_access($condition, $user))
				{
					return true;
				}
			}

			return false;
		}

		return App\Auth::acl($driver)->has_access($condition, $user);
	}

	/**
	 * Default password hash method
	 * NOTICE: works by reference
	 *
	 * @param	string
	 */
	public function hash_password(&$password)
	{
		$password = sha1(@$this->config['salt_prefix'].$password.@$this->config['salt_postfix']);
	}

	// ------------------------------------------------------------------------

	/**
	 * Perform the actual login check
	 *
	 * @return bool
	 */
	abstract protected function perform_check();

	/**
	 * Login method
	 *
	 * @return	bool	whether login succeeded
	 */
	abstract protected function login();

	/**
	 * Logout method
	 */
	abstract protected function logout();

	/**
	 * Get User Identifier of the current logged in user
	 * in the form: array(driver_id, user_id)
	 *
	 * @return	array
	 */
	abstract public function get_user_id();

	/**
	 * Get User Groups of the current logged in user
	 * in the form: array(array(driver_id, group_id), array(driver_id, group_id), etc)
	 *
	 * @return	array
	 */
	abstract public function get_user_groups();

	/**
	 * Get emailaddress of the current logged in user
	 *
	 * @return	string
	 */
	abstract public function get_user_email();

	/**
	 * Get screen name of the current logged in user
	 *
	 * @return	string
	 */
	abstract public function get_user_screen_name();
}

/* end of file auth.php */