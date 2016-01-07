<?php

/**
 * ThreeCol
 *
 * Plugin to switch Roundcube to a three column layout
 *
 * @version @package_version@
 * @author Philip Weir
 */
class threecol extends rcube_plugin
{
	public $task = 'mail|settings';
	private $driver;

	function init()
	{
		$rcmail = rcube::get_instance();
		$no_override = array_flip($rcmail->config->get('dont_override', array()));
		$this->driver = $this->home .'/skins/'. $rcmail->config->get('skin') .'/func.php';

		// Config hook
		if ($rcmail->config->get('previewpane_layout', 'below') == 'right') {
		  $this->add_hook('config_get', array($this,'config_get'));
		}

		if (is_readable($this->driver)) {
			if ($rcmail->task == 'mail' && $rcmail->action == '' && $rcmail->config->get('previewpane_layout', 'below') == 'right') {
					$this->add_hook('render_page', array($this, 'render'));
					$this->include_script('threecol.js');
					$this->include_stylesheet($this->local_skin_path() .'/threecol.css');
			}
			elseif ($rcmail->task == 'settings' && !isset($no_override['previewpane_layout'])) {
				$this->add_hook('preferences_list', array($this, 'show_settings'));
				$this->add_hook('preferences_save', array($this, 'save_settings'));
			}
		}
		else {
			rcube::raise_error(array(
				'code' => 600,
				'type' => 'php',
				'file' => __FILE__,
				'line' => __LINE__,
				'message' => "ThreeCol plugin: Unable to open driver file $this->driver"
				), true, false);
		}
	}

	function render($args)
	{
		include_once($this->driver);

		if (!function_exists('render_page')) {
			rcube::raise_error(array(
				'code' => 600,
				'type' => 'php',
				'file' => __FILE__,
				'line' => __LINE__,
				'message' => "ThreeCol plugin: Broken driver: $this->driver"
				), true, false);
		}

		$args = render_page($args);

		return $args;
	}

	function show_settings($args)
	{
		if ($args['section'] == 'mailbox') {
			$this->add_texts('localization/');

			$field_id = 'rcmfd_previewpane_layout';
			$select = new html_select(array('name' => '_previewpane_layout', 'id' => $field_id));
			$select->add(rcmail::Q($this->gettext('threecol.none')), 'none');
			$select->add(rcmail::Q($this->gettext('threecol.below')), 'below');
			$select->add(rcmail::Q($this->gettext('threecol.right')), 'right');

			// add new option at the top of the list
			$val = rcube::get_instance()->config->get('preview_pane') ? rcube::get_instance()->config->get('previewpane_layout', 'below') : 'none';
			$args['blocks']['main']['options']['preview_pane']['content'] = $select->show($val);
		}

		return $args;
	}

	function save_settings($args)
	{
		if ($args['section'] == 'mailbox') {
			$args['prefs']['preview_pane'] = rcube_utils::get_input_value('_previewpane_layout', rcube_utils::INPUT_POST) == 'none' ? false : true;
			$args['prefs']['previewpane_layout'] = rcube_utils::get_input_value('_previewpane_layout', rcube_utils::INPUT_POST) != 'none' ? rcube_utils::get_input_value('_previewpane_layout', rcube_utils::INPUT_POST) : rcube::get_instance()->config->get('previewpane_layout', 'below');
		}

		return $args;
	}

	/**
	 * Modification de la configuration utilisateur pour s'adapter au mobile
	 *
	 * @param array $args
	 */
	public function config_get($args) {
	  switch ($args['name']) {
	    // Liste des colonnes affichées
	    case 'list_cols' :
	      $args['result'] = array('status','date','from','subject','attachment','flag','labels','size');
	      break;
	    case 'dont_override' :
	      if (! is_array($args['result']))
	        $args['result'] = array();
	        $args['result'][] = 'list_cols';
	        break;
	  }
	  return $args;
	}
}

?>