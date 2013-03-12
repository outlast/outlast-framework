<?php
/**
 * Update controller is a special system controller which handles installation, upgrade, database update, cache, and template cache related tasks.
 * @package Controller
 * @subpackage BuiltinControllers
 **/
 	define('ZAJ_INSTALL_DONTCHECK', 'dont_check_install');

	class zajapp_update extends zajController{

		/**
		 * Authenticate the request if not in debug mode
		 **/
		function __load(){
			// is update disabled?
				if(!$this->zajlib->zajconf['update_enabled']) return exit("Update disabled.");

			// check for recommended updates
				if(defined('MOZAJIK_RECOMMENDED_HTACCESS_VERSION') && MOZAJIK_RECOMMENDED_HTACCESS_VERSION > $this->zajlib->htver) $this->zajlib->variable->htver_upgrade = MOZAJIK_RECOMMENDED_HTACCESS_VERSION;
				if(defined('MOZAJIK_RECOMMENDED_CONFIG_VERSION') && MOZAJIK_RECOMMENDED_CONFIG_VERSION > $this->zajlib->zajconf['config_file_version']) $this->zajlib->variable->conf_upgrade = MOZAJIK_RECOMMENDED_CONFIG_VERSION;
			// check for other stuff
				$this->zajlib->variable->mysql_setting_enabled = $this->zajlib->zajconf['mysql_enabled'];


			// am i not in debug mode?
				if(!$this->zajlib->debug_mode){
					// is my password defined?
						if(!$this->zajlib->zajconf['update_user'] || !$this->zajlib->zajconf['update_password']) return $this->install();
					// all is good, so authenticate
						return $this->zajlib->security->protect_me($this->zajlib->zajconf['update_user'], $this->zajlib->zajconf['update_password'], "Mozajik update");
				}
			return true;
		}
		
		/**
		 * Display main menu for update
		 **/
		function main(){
			// load menu
				$this->zajlib->template->show("update/base-update.html");						
		}

		/**
		 * Run the deployment script
		 **/
		function deploy(){
			// Run template update
				$this->zajlib->variable->count = $this->template(false);
			// Run unit tests
				$this->test(false);
			// Get test results
				$this->zajlib->variable->testresults = $this->zajlib->template->block("update/update-test.html", "testresults", false, false, true);
			// all is okay, continue with update
				$this->zajlib->template->show("update/update-deploy.html");			
		}



		/**
		 * Display the database update menu
		 **/
		function database(){
			// check to see if my current install is up to date
				$version_status = MozajikVersion::check();
			// if all is good, display that message
				if($version_status < 0) return $this->zajlib->template->show('update/update-version-toonew.html'); 
			// if database exists and it is too old, then update!
				if($version_status == 0 && is_object($this->zajlib->mozajik)) return $this->zajlib->template->show('update/update-version-needed.html'); 
			// all is okay, continue with update
				$this->zajlib->variable->title = "app update | database";
				$this->zajlib->variable->updatename = "database model";
				$this->zajlib->variable->updateframeurl = "database/go/";
				$this->zajlib->template->show("update/update-process.html");			
		}

		/**
		 * Display the database update log
		 **/
		function database_go(){			
			// first let's show the update log template
				$this->zajlib->template->show("update/update-log.html");
			// now let's start the db update
				$this->zajlib->load->library("model");
				$db_update_result = $this->zajlib->model->update();
				$db_update_todo = $this->zajlib->model->num_of_todo;
			// start output
				print "<div class='updatelog-results'>";
			// now check if any errors
				if($db_update_result[0] >= 0) print $db_update_result[1];
				else exit("<input class='mozajik-update' type='hidden' id='update_result' value='$db_update_result[0]'><br>error: stopping update</div></body></html>");
			// now print the update_result
				print "<input class='mozajik-update' type='hidden' id='update_result' value='$db_update_result[0]'><input class='mozajik-update' type='hidden' id='update_todo' value='$db_update_todo'></div></body></html>";
			exit;
		}

		/**
		 * Run all the unit tests.
		 * @param boolean $show_results If set to true, it will display results.
		 **/
		function test($show_result = true){
			// Prepare the tests
				$this->zajlib->test->prepare_all();
			// Run all
				$result = $this->zajlib->test->run();
			// Now return error if any error found
				if(count($this->zajlib->variable->test->Errors) > 0){
					header('HTTP/1.1 500 Internal Server Error');
				}
			// Display!
				if($show_result) return $this->zajlib->template->show("update/update-test.html");
				else return $result;
		}

		/**
		 * Display the object cache reset menu
		 **/
		function cache(){
			// count all the files
				if(empty($_GET['force'])){
					$this->zajlib->variable->folder = $this->zajlib->basepath."cache/object/";
					return $this->zajlib->template->show("update/update-cache.html");
				}
			// load variables
				$this->zajlib->variable->title = "object cache update | reset";
				$this->zajlib->variable->folder = $this->zajlib->cache->clear_objects();
				return $this->zajlib->template->show("update/update-cache.html");
		}

		/**
		 * Display the template cache reset menu
		 * @param boolean $show_result If set to true (the default), a message will be displayed once done. Otherwise the count will be returned.
		 * @return integer The count is returned if $show_result is set to false.
		 **/
		function template($show_result = true){
			// enable update mode
				file_put_contents($this->zajlib->basepath."cache/progress.dat", time());
			// get all the files in the template cache folder
				$this->zajlib->load->library("file");
				$my_files = $this->zajlib->file->get_files_in_dir($this->zajlib->basepath."cache/view/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count = count($my_files);

			// get all the files in the conf folder
				$my_files = $this->zajlib->file->get_files_in_dir($this->zajlib->basepath."cache/conf/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count += count($my_files);

			// get all the files in the conf folder
				$my_files = $this->zajlib->file->get_files_in_dir($this->zajlib->basepath."cache/lang/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count += count($my_files);

			// get all the files in the temp folder
				$my_files = $this->zajlib->file->get_files_in_dir($this->zajlib->basepath."cache/temp/", true);
			// delete them
				if(is_array($my_files)) foreach($my_files as $f) @unlink($f);
				$total_count += count($my_files);

			// disable update mode
				unlink($this->zajlib->basepath."cache/progress.dat");
			// print them
				if($show_result){
					$this->zajlib->variable->title = "template cache update | reset";
					$this->zajlib->variable->count = $total_count;
					$this->zajlib->template->show("update/update-template.html");
				}
				else return $total_count;
		}

		
		/**
		 * Install a new version of Mozajik
		 * @todo Add plugin install check for dynamically loaded plugins (so check the folder instead of plugin_apps)
		 **/
		function install(){
			// Load my version information
				$this->zajlib->load->model('MozajikVersion');
			// Define my statuses
				$done = '<span class="label label-success">Done</span>';
				$todo = '<span class="label label-important">Not done</span>';
				$optional = '<span class="label label-info">Optional</span>';
				$na = '<span class="label label-inverse">Not enabled</span>';
				$ready_to_activate = true;
				$ready_to_dbupdate = true;
			// Check install status of plugins
				// 1. Calls __install() method on each plugin
				// 2. Checks return value: if it is ZAJ_INSTALL_DONTCHECK, then the installation check is not continued (USE ONLY WHEN OTHER INSTALL PROCEDURES NEEDED. Ex: Wordpress).
				// 3. Checks return value: if it is a string, then it is an error and it is displayed.
				foreach(array_reverse($this->zajlib->zajconf['plugin_apps']) as $plugin){
					// first load up the plugin without __plugin execution
						$this->zajlib->plugin->load($plugin, false);
					// only do this if either default controller exists in the plugin folder
						if(file_exists($this->zajlib->basepath.'plugins/'.$plugin.'/controller/'.$plugin.'.ctl.php') || file_exists($this->zajlib->basepath.'plugins/'.$plugin.'/controller/'.$plugin.'/default.ctl.php')){			
							// reroute but if no __install method, just skip without an error message (TODO: maybe remove the false here?)!
								$result = $this->zajlib->reroute($plugin.'/__install/', array($app_request, $zaj_app, $zaj_mode), false);
							// __install should return a string if it fails, otherwise it is considered to pass
								if(is_string($result) && $result == ZAJ_INSTALL_DONTCHECK) return true;
								elseif(is_string($result)){ $plugin_text .= "<li>Checking plugin $plugin. <span class='label label-important'>Failed</span><pre class='well' style='font-family: monospace; padding: 10px; overflow: auto; background-color: #f5f5f5; margin-top: 10px;'>$result</pre></li>"; $ready_to_activate = false; }
								else $plugin_text .= "<li>Checking plugin $plugin... <span class='label label-success'>Done</span></li>";
						}
				}				
			// Check status for each step
				// 1. Check writable
					if(!is_writable($this->zajlib->basepath."cache/") || !is_writable($this->zajlib->basepath."data/")){ $status_write  = $todo; $ready_to_dbupdate = false; $ready_to_activate = false; }
					else $status_write  = $done;
				// 2. Check database permissions
					if(!$this->zajlib->zajconf['mysql_enabled']){ $status_db  = $na; $ready_to_dbupdate = false; }
					else{
						if($this->zajlib->db->connect($this->zajlib->zajconf['mysql_server'], $this->zajlib->zajconf['mysql_user'], $this->zajlib->zajconf['mysql_password'], $this->zajlib->zajconf['mysql_db'], false)) $status_db = $done;
						else{ $status_db  = $todo; $ready_to_dbupdate = false; $ready_to_activate = false; }
					}
				// 3. Check user/pass for update
					if(empty($this->zajlib->zajconf['update_user']) || empty($this->zajlib->zajconf['update_password'])){
						if($this->zajlib->debug_mode) $status_updatepass = $optional;
						else{ $status_updatepass = $todo; $ready_to_activate = false; }
					}
					else $status_updatepass = $done;
				// 4. Check database update (photo table should always exist)
					if(!$this->zajlib->zajconf['mysql_enabled']) $status_dbupdate  = $na;
					elseif($status_db == $todo){ $status_dbupdate  = $todo; $ready_to_activate = false; }
					else{				
						$result = $this->zajlib->db->query("SELECT count(*) as c FROM information_schema.tables WHERE table_schema = '".addslashes($this->zajlib->zajconf['mysql_db'])."' AND table_name = 'photo'")->next();
						if($result->c <= 0){ $status_dbupdate = $todo; $ready_to_activate = false; }
						else $status_dbupdate = $done;
					}
				// 5. Check activation
					if(!is_object($this->zajlib->mozajik) || !MozajikVersion::check()) $status_activate = $todo;
					else $status_activate = $done;
			
			?>
<head>
	<meta charset="utf-8">
	<title>Mozajik Installation</title>
	<meta name="description" content="">
	<meta name="author" content="">
	<!--[if lt IE 9]><script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/bootstrap/css/bootstrap.min.css">

	<link href="//fonts.googleapis.com/css?family=Source+Sans+Pro:300" rel="stylesheet" type="text/css">
	<link rel="shortcut icon" type="image/png" href="//localhost/wlp/system/img/outlast-favicon.png">
	<link rel="stylesheet" type="text/css" href="//localhost/wlp/system/css/outlast-update.css?v3" media="all">

	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/jquery/jquery-1.8.0.min.js" type="text/javascript"></script>
	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/mozajik-base-jquery.js" type="text/javascript"></script>

</head>
<body>
	<div class="container">
		<div class="row">
			<div class="span12">
			<br/><br/>
			<h1>Outlast Framework Installation</h1>
			<h3>Welcome to the Outlast Framework installation for version <?php echo MozajikVersion::$major; ?>.<?php echo MozajikVersion::$minor; ?>.<?php echo MozajikVersion::$build; ?> <?php if(MozajikVersion::$beta) echo "beta"; ?></h3>
			<?php if($this->zajlib->debug_mode){ ?><h5><span style="color: red;">Attention!</span> This installation will be running in <strong>debug mode</strong>. This is not recommended for production sites!</h5><?php } ?>
			<hr/>
			<ul>
				<?php if(empty($plugin_text)) echo "<li>Checking plugins... No plugins activated.</li>"; else echo $plugin_text ?>
			</ul>
			<hr/>
			<ol>
				<li>Make /cache and /data folders writable by webserver. - <?php echo $status_write; ?></li>
				<li>Create read/write permissions for database (if mysql is enabled). - <?php echo $status_db; ?></li>
				<li>Create an update user and password in /site/index.php (required if in production mode). - <?php echo $status_updatepass; ?></li>
				<li>Update the database (if mysql is enabled). - <?php echo $status_dbupdate; ?></li>
				<li>Activate this installation. - <?php echo $status_activate; ?></li>
				<?php if($ready_to_activate && $status_activate == $done){ ?><br/><div class="alert alert-success center">Your installation is currently <span style="color:green;">activated</span>. Go to the <a href="<?php echo $this->zajlib->baseurl; ?>">home page</a>.</div><?php } ?>
			</ol>
			<hr/>
			</div>
		</div>
		<div class="row">
			<div class="span4 center">
				<input class="btn" type="button" onclick="zaj.reload();" value="Recheck install status">
			</div>
			<div class="span4 center">
				<input class="btn btn-primary" type="button" onclick="zaj.open('<?php echo $this->zajlib->baseurl; ?>update/database/', 1000, 500);" <?php if(!$ready_to_dbupdate){ ?>disabled="disabled"<?php } ?> value="Update the database">
			</div>
			<div class="span4 center">
				<input class="btn btn-success" type="submit" onclick="window.location = '<?php echo $this->zajlib->baseurl; ?>update/install/go/';" <?php if(!$ready_to_activate || $status_activate == $done){ ?>disabled="disabled"<?php } ?> value="Activate this installation">			
			</div>
		</div>
		<div class="row">
			<div class="span12 center">
				<br/>
				<a href="<?php echo $this->zajlib->baseurl; ?>update/">Back to the update page</a>
			</div>
		</div>
	</div>
</body>
			
			
			
			
			<?php
			
			exit();
		}	

		/**
		 * Run the installation/activation.
		 **/
		function install_go($redirect_me = true){
			// manually load model (avoids issues when database disabled)
				$this->zajlib->load->model('MozajikVersion');
			// install
				MozajikVersion::install();
			// now redirect to check
				if($redirect_me) return $this->zajlib->redirect("update/install/");
				else return true;
		}


		/**
		 * Update in progress
		 **/
		function progress(){
			?>
<head>
	<meta charset="utf-8">
	<title>Site update in progress...</title>
	<meta name="description" content="">
	<meta name="author" content="">
	<!--[if lt IE 9]><script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/skeleton/base.css">
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/skeleton/skeleton.css">
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/skeleton/layout.css">
	<link rel="stylesheet" href="<?php echo $this->zajlib->baseurl; ?>system/css/mozajik.css">


	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/mootools/mootools-core-1.3.js" type="text/javascript"></script>
	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/mootools/mootools-more-1.3.js" type="text/javascript"></script>	
	<script language="JavaScript" src="<?php echo $this->zajlib->baseurl; ?>system/js/mozajik-base-1.3.js" type="text/javascript"></script>

</head>
<body>
	<div class="container">
		<div class="sixteen columns">
			<br/><br/>
			<h1>Update in progress...</h1>
			<h3>This site is being updated. Please retry in a few minutes.</h3>
			<hr/>
			<p>If this message does not go away after a few minutes, please contact the site administrator.</p>
		</div>
		<div class="five columns left">
			<input type="button" onclick="zaj.reload();" value="Reload page now">
		</div>
		<div class="five columns center">

		</div>
		<div class="five columns center">

		</div>
	</div>
</body>
			
			
			
			
			<?php
			
			exit();
		}	

		/**
		 * Display error log
		 **/
		function errors(){
			// get my errors
				$this->zajlib->variable->errors = MozajikError::fetch()->paginate(50);
			// send to template
				return $this->zajlib->template->show('update/update-errors.html');
		}

		/**
		 * Display error details
		 * @param MozajikError $eobj An error object.
		 **/
		function error_details($eobj){
			$backtrace = unserialize($eobj->data->backtrace);
			if(is_array($backtrace)){
				foreach($backtrace as $key=>$data){
					$this->zajlib->variable->number = $key;
					$this->zajlib->variable->detail = (object) $data;
					$this->zajlib->template->show('update/update-error-detail.html');
				}
			}
			else{
				print "No backtrace available for this error.";
			}
		}
	
	}
	

?>