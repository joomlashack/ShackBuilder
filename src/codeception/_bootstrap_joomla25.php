<?php
// Bootstrap server variables
$_SERVER['HTTP_HOST']      = 'http://localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '';

// Check if we have a valid joomla location
if (!is_dir($localConfig['joomla_path'])) {
    throw new Exception('Could not find the Joomla folder: ' . $localConfig['joomla_path']);
}

define('ALLEDIA_BUILDER_PATH', $config->allediaBuilderPath);
define('TEST_HOST_BASEURL', 'http://joomla.dev:8025');

// Load a minimal Joomla framework
define('_JEXEC', 1);

if (!defined('JPATH_BASE')) {
    define('JPATH_BASE', realpath($localConfig['joomla_path']));
}
require_once JPATH_BASE . '/includes/defines.php';

require_once JPATH_BASE . '/includes/framework.php';

// Copied from /includes/framework.php
@ini_set('magic_quotes_runtime', 0);
@ini_set('zend.ze1_compatibility_mode', '0');

require_once JPATH_LIBRARIES . '/import.php';

error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);

// Force library to be in JError legacy mode
JError::$legacy = true;
JError::setErrorHandling(E_NOTICE, 'message');
JError::setErrorHandling(E_WARNING, 'message');
JError::setErrorHandling(E_ERROR, 'message');

jimport('joomla.application.menu');
jimport('joomla.environment.uri');
jimport('joomla.utilities.utility');
jimport('joomla.event.dispatcher');
jimport('joomla.utilities.arrayhelper');

// Bootstrap the CMS libraries.
if (!defined('JPATH_PLATFORM')) {
    define('JPATH_PLATFORM', JPATH_BASE . '/libraries');
}
if (!defined('JDEBUG')) {
    define('JDEBUG', false);
}
require_once JPATH_LIBRARIES.'/cms.php';

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

// Instantiate some needed objects
JFactory::getApplication('site');

// Load the support classes
$supportClassBasePath = __DIR__ . '/_support';
require_once $supportClassBasePath . '/AssertHelper.php';
require_once $supportClassBasePath . '/ClassHelper.php';
require_once $supportClassBasePath . '/JoomlaDboHelper.php';
