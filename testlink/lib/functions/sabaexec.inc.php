<?php
	/**
	 * TestLink Open Source Project - http://testlink.sourceforge.net/
	 * This script is distributed under the GNU General Public License 2 or later.
	 *
	 * Functions for saba execution feature (add test results)
	 *
	 * @package 	TestLink
	 * @author 		Rohan Sakhale
	 * @copyright 	2005-2012, TestLink community
	 * @filesource	sabaexec.inc.php
	 * @link 		http://www.teamst.org/index.php
	 *
	 * @internal revisions
	 * @since 1.9.4
	 *
	 **/

	require_once ('common.php');

	/**
	 * Write bugs for execution by deleting first and inserting
	 *
	 * 	@param &$db reference database object
	 * 	@param &$it reference issue tracker object
	 * 	@param $exec_id is the execution id over which bug has to be linked
	 * 	@param $bug_id is the Jira BUG Id to be linked with execution
	 * 	@param $just_delete just delete and don't insert new
	 */
	function write_execution_bug(&$db, $it = null, $exec_id, $bug_id, $just_delete = false) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		$execution_bugs = DB_TABLE_PREFIX . 'execution_bugs';

		// Instead of Check if record exists before inserting, do delete + insert
		$prep_bug_id = $db -> prepare_string($bug_id);

		$sql = "/* $debugMsg */ DELETE FROM {$execution_bugs} WHERE execution_id={$exec_id} " . "AND bug_id='" . $prep_bug_id . "'";
		$result = $db -> exec_query($sql);
		if (!$just_delete) {
			$sql = "/* $debugMsg */ INSERT INTO {$execution_bugs} (execution_id,bug_id) " . "VALUES ({$exec_id},'" . $prep_bug_id . "')";
			$result = $db -> exec_query($sql);
			$it -> insertIssue($bug_id);
		}
		return $result ? 1 : 0;
	}

	/**
	 *	Check TestLink's & Jira Components based on Project
	 * 	Accordingly notifying administration if any change is required to be performed
	 *
	 * 	@param &$db reference databsae connection object
	 */
	function checkTestLinkJiraComponents(&$db) {
		$tproject_mgr = new testproject($db);
		$projects = $tproject_mgr -> get_all(array('active' => 1));
		$project_missing_components = array();
		foreach ($projects as $project) {
			$project_missing_components[$project['id']] = array(
				'new_in_jira' => array(),
				'to_be_removed_from_testlink' => array()
			);
			if ($project['issue_tracker_enabled']) {
				$tl_cfields = $tproject_mgr -> get_linked_custom_fields($project['id'], 'testcase', 'name');
				$tl_component = isset($tl_cfields[$project['prefix'] . 'Components']) ? $tl_cfields[$project['prefix'] . 'Components'] : null;
				if (is_null($tl_component)) {
					continue;
				}
				$tl_component_list = explode("|", $tl_component['possible_values']);
				$it_mgr = new tlIssueTracker($db);
				$its = $it_mgr -> getInterfaceObject($project['id']);
				$its -> setProjectData($project);
				$jira_components = $its -> getComponents();
				$jira_components_arr = array();
				foreach ($jira_components as $c) {
					$jira_components_arr[] = cleanVal($c -> name);
				}

				foreach ($jira_components_arr as $jira_c) {
					if (!in_array($jira_c, $tl_component_list)) {
						$project_missing_components[$project['id']]['new_in_jira'][] = $jira_c;
					}
				}
				foreach ($tl_component_list as $tl_c) {
					if (!in_array($tl_c, $jira_components_arr)) {
						$project_missing_components[$project['id']]['to_be_removed_from_testlink'][] = $tl_c;
					}
				}
			}
		}
		$email_msg = "";
	}

	/**
	 *	Execute Test Case
	 *
	 * 	@param &$db referenced database connection object
	 * 	@param &$its referenced issue tracker object
	 * 	@param $buildId is the build id over which testing happened
	 * 	@param $testerId is the tester who executed this test case
	 * 	@param $status could be
	 * 			p ==> Passed
	 * 			f ==> Failed
	 * 			b ==> Blocked
	 * 			x ==> Not Applicable or Skipped
	 * 			n ==> Not Run
	 * 	@param $tplanId is the Test Plan within which execution happened
	 * 	@param $tcversionId is the test case version id
	 * 	@param $tcversionNumber is the test case version number
	 * 	@param $platfotmId over which the case got executed
	 * 	@param $execType could be
	 * 				1 --> Manual
	 * 				2 --> Automated
	 * 	@param $notes are the additionally results observed by tester
	 * 	@param $bugs could be string or array of bugs that needs to be linked with execution
	 *
	 **/
	function executeTestResult(&$db, &$its, $buildId, $testerId, $status, $tplanId, $tcversionId, $tcversionNumber, $platformId, $execType, $notes, $bugs = null) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		$allow_execute = true;
		
		//var_dump($buildId,$status,$tcversionId,$execType);die();
		//DG- get associated bugs 
		
		if($status!='f')
		$Jirabugs=getBugsAssociated($db,$tplanId, $tcversionId, $platformId);
        
        /*
		foreach ($bugs as $bug) {
			if ($bug['status'] != 6) {
				$allow_execute = false;
			}
		}
		$ret = array(
			'status' => 'NOT_OK',
			'msg' => "B"
		);
		 
		 */
          //DG- get current bug status from JIRA and then checked for passes execution status ,bug should be closed and vice versa fro failed status.
		 foreach ($Jirabugs as $bug) {
		 	$Bugstatus= $its -> getIssue($bug['bug_id']);
			$id=$Bugstatus-> fields -> status -> id;
			validtoexecute($status,$id,$allow_execute,$db,$tplanId, $tcversionId, $platformId,$bug['bug_id'],$error_code);
			//var_dump($error_code,$allow_execute);
		     }
       
	//This loop is check ,when user try to add adhoc bugs to test case	 
        if ($bugs!=NULL)
		 {
		 	$Bugstatus= $its -> getIssue($bugs);
			$id=$Bugstatus-> fields -> status -> id;
		 	validtoexecute($status,$id,$allow_execute,$db,$tplanId, $tcversionId, $platformId,$bugs,$error_code);
		 } 
	
	   if (!($allow_execute))
	   return $error_code;
	   else  {
			/**
			 * Ignore executions for empty or null status
			 * RSakhale
			 */
			 
			$error_msg = '<span style="color:#B22222;"> ERROR: </span>';
			if ($bugs == '') {
				$bugs = null;
			}
			if (is_null($status) or $status == '') {
				return array(
					'status' => 'NOT_OK',
					'msg' => $error_msg . 'Execution status empty'
				);
			}

			if (in_array($status, array(
				'f',
				'b'
			)) and (is_null($bugs) or count($bugs) == 0)) {
				return array(
					'status' => 'NOT_OK',
					'msg' => $error_msg . 'Bug association required for Failed/Blocked Status'
				);
			}

			if ($status == 'p' and (!is_null($bugs) or count($bugs) > 0)) {
				return array(
					'status' => 'NOT_OK',
					'msg' => $error_msg . 'Bug association not allowed for Passed Status'
				);
			}
			if ($platformId == -1) {
				return array(
					'status' => 'NOT_OK',
					'msg' => $error_msg . 'Missing platform/Invalid platform details'
				);
			}
			$tsISONow = date('c', time());
			$buildId = $db -> prepare_int($buildId);
			$testerId = $db -> prepare_int($testerId);
			$status = $db -> prepare_string($status);
			$tplanId = $db -> prepare_int($tplanId);
			$tcversionId = $db -> prepare_int($tcversionId);
			$tcversionNumber = $db -> prepare_int($tcversionNumber);
			$platformId = $db -> prepare_int($platformId);
			$execType = $db -> prepare_int($execType);
			$notes = $db -> prepare_string($notes);
			$sql = "/* $debugMsg */ INSERT INTO `" . DB_TABLE_PREFIX . "executions` (`build_id`,`tester_id`,`execution_ts`,`status`,`testplan_id`
		,`tcversion_id`, `tcversion_number`,`platform_id`,`execution_type`,`notes`) VALUES (
		$buildId,$testerId,'$tsISONow','$status',$tplanId,$tcversionId,$tcversionNumber,$platformId,$execType,'$notes')";
			//echo $sql ."<br />";die();
			$db -> exec_query($sql);
			$exec_id = $db -> insert_id();
			$ret = array(
				'status' => 'OK',
				'msg' => 'Executed'
			);
			if (!is_null($exec_id)) {
				$log = array(
					"build_id" => $buildId,
					"tester_id" => $testerId,
					"status" => $status,
					"tplanID" => $tplanId,
					"tcversionId" => $tcversionId,
					"platformId" => $platformId,
					"bugs" => $bugs
				);
				tLog("Executed: " . json_encode($log), "AUDIT", "API");
				if (!is_null($bugs)) {
					if (!is_array($bugs) and strpos($bugs, ",") !== false) {
						$bugs = explode(",", $bugs);
					}
					if (is_array($bugs)) {
						foreach ($bugs as $bug_id) {
							if ($its -> checkBugIDExistence($bug_id)) {
								write_execution_bug($db, $its, $exec_id, trim($bug_id));
							}
						}
					} else {
						if ($its -> checkBugIDExistence($bugs)) {
							write_execution_bug($db, $its, $exec_id, trim($bugs));
						}
					}
				}
			} else {
				$ret = array(
					'status' => 'NOT_OK',
					'msg' => $db -> error_msg()
				);
			}
		}
		return $ret;
	}

	/**
	 *	Check if test case already linked with a Test Plan
	 * 	This helps avoid adding duplicate cases for same Test Plan
	 *
	 * 	@param &$db is the referenced database connection object
	 * 	@param $tplan_id is the test plan id with whom linkage has to be checked
	 * 	@param $tcversion_id is the test case version id to be checked if linked with plan
	 * 	@param $platform_id to check linking of case with platform is done for the plan
	 *
	 * 	@return Integer either successful id or false
	 *
	 */
	function check_tc_linked_plan(&$db, $tplan_id, $tcversion_id, $platform_id = 0) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		if (is_null($tplan_id) or is_null($tcversion_id) or is_null($platform_id)) {
			return false;
		}
		$tables = tlDBObject::getDBTables();
		$sql = "/* $debugMsg */ SElECT * FROM `{$tables['testplan_tcversions']}` WHERE `testplan_id` = $tplan_id AND `tcversion_id` = $tcversion_id AND `platform_id` = $platform_id";
		$result = $db -> exec_query($sql);

		if ($db -> num_rows($result) > 0) {
			$row = null;
			if ($row = $db -> fetch_array($result)) {
				return $row['id'];
			}
		}
		return false;
	}

	/**
	 *	Optimizes Database Tables
	 *
	 * 	@param &$db is the referenced database connection object
	 */
	function optimizeDBTables(&$db) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		$sql = "/* $debugMsg */ OPTIMIZE TABLE " . implode(", ", tlDBObject::getDBTables());
		$r = $db -> exec_query($sql);
	}

	/**
	 *	Repairs Database Tables
	 *
	 * 	@param &$db is the referenced database connection object
	 */
	function repairDBTables(&$db) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		$sql = "/* $debugMsg */ REPAIR TABLE " . implode(", ", tlDBObject::getDBTables());
		$r = $db -> exec_query($sql);
	}

	/**
	 *	Synchronizes HQ-JIRA latest non-closed id's with its latest available data
	 *
	 * 	@param &$db is the referenced database connection object
	 */
	function syncJira(&$db) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		$tables = tlDBObject::getDBTables();

		$its = null;
		$tproject_mgr = new testproject($db);
		$tplan_mgr = new testplan($db);
		$tcase_mgr = new sabatestcase($db);
		$tprojects = $tproject_mgr -> get_all();

		/**
		 * Get Closed Jira Id's
		 */
		$jira_closed_ids_query = "/* $debugMsg */ SELECT `id` from {$tables['jiraissues']} WHERE `status` = 6";
		$jira_closed_ids = array();
		$result = $db -> get_recordset($jira_closed_ids_query);
		foreach ($result as $close_jira) {
			$jira_closed_ids[] = "'" . $close_jira['id'] . "'";
		}

		/**
		 * Clean Jira's that are not linked to Test Case nor bugs
		 */
		$query = "/* $debugMsg */ DELETE FROM {$tables['tcjira']} WHERE `tcase_id` NOT IN (SELECT `id` FROM {$tables['nodes_hierarchy']})";
		$db -> exec_query($query);
		$query = "/* $debugMsg */ DELETE FROM {$tables['jiraissues']} 
			WHERE `id` NOT IN (
				SELECT `jira_id` FROM {$tables['tcjira']}
			) AND `id` NOT IN (SELECT `bug_id` FROM {$tables['execution_bugs']})";
		$db -> exec_query($query);
		$jira_id_count = 0;
		foreach ($tprojects as $tproject_info) {
			$it_mgr = new tlIssueTracker($db);
			$its = $it_mgr -> getInterfaceObject($tproject_info['id']);
			$its -> setProjectData($tproject_info);
			unset($it_mgr);

			$release_mgr = new release($db, $tproject_info['id']);
			$releases = $release_mgr -> get_all(array('active' => 1));
			$release_ids = array();
			// Get Custom Field Map
			$cf_map = $tplan_mgr -> cfield_mgr -> get_linked_to_testproject($tproject_info['id']);

			if (!is_null($releases)) {
				foreach ($releases as $release_info) {
					$release_ids[] = $release_info['id'];
				}
				$tplans = $release_mgr -> get_linked_plans($release_ids);
				if (!is_null($tplans)) {
					// List bugs from TestCase
					$query = "/* $debugMsg */ SELECT EB.bug_id FROM {$tables['execution_bugs']} EB
					JOIN {$tables['executions']} E ON E.id = EB.execution_id
					WHERE E.testplan_id IN (" . implode(",", array_keys($tplans)) . ")";
					if (count($jira_closed_ids) > 0) {
						//$query .= " AND EB.bug_id NOT IN (" . implode(",", $jira_closed_ids) . ")";
					}
					$query .= " GROUP BY EB.bug_id";
					$result = $db -> exec_query($query);
					if ($db -> num_rows($result) > 0) {
						while ($row = $db -> fetch_array($result)) {
							$its -> deleteIssue($row['bug_id']);
							if ($its -> checkBugIDExistence($row['bug_id'])) {
								$its -> insertIssue($row['bug_id']);
								$jira_id_count++;
							}
						}
					}
					$query = "/* $debugMsg */ SELECT TCJ.* FROM {$tables['tcjira']} TCJ
					JOIN {$tables['testplan_tcversions']} TTCV ON TTCV.tcversion_id = TCJ.tcversion
					WHERE TTCV.testplan_id IN (" . implode(",", array_keys($tplans)) . ")";
					if (count($jira_closed_ids) > 0) {
						$query .= " AND TCJ.jira_id NOT IN (" . implode(",", $jira_closed_ids) . ")";
					}
					$result = $db -> exec_query($query);
					if ($db -> num_rows($result) > 0) {
						while ($row = $db -> fetch_array($result)) {
							$its -> deleteIssue($row['jira_id']);
							if ($its -> checkBugIDExistence($row['jira_id'])) {
								$its -> insertIssue($row['jira_id'], $row['tcase_id'], $row['tcversion'], $tplan_mgr -> cfield_mgr, $cf_map);
								$jira_id_count++;
							}
						}
					}
					unset($result);
					unset($tplans);
				}
			}
			unset($its);
		}
		return $jira_id_count;
	}

	/**
	 * Remove results marked as NA
	 * Unlink the tcversion from testplan
	 */
	function clean_na_cases(&$db) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		$sql = "/* $debugMsg */ SELECT * FROM `" . DB_TABLE_PREFIX . "executions` WHERE `status` = 'x'";
		$result = $db -> get_recordset($sql);
		if (!is_null($result)) {
			foreach ($result as $resultInfo) {
				$sql1 = "/* $debugMsg */ SELECT * FROM `" . DB_TABLE_PREFIX . "testplan_tcversions` WHERE `tcversion_id` = {$resultInfo['tcversion_id']} AND `testplan_id` = {$resultInfo['testplan_id']}";
				$linkedTCVersions = $db -> get_recordset($sql1);
				if (!is_null($linkedTCVersions)) {
					foreach ($linkedTCVersions as $linkedVersion) {
						$sql1 = "/* $debugMsg */ DELETE FROM `" . DB_TABLE_PREFIX . "user_assignments` WHERE `feature_id` = {$linkedVersion['id']}";
						$db -> exec_query($sql1);
						$sql1 = "/* $debugMsg */ DELETE FROM `" . DB_TABLE_PREFIX . "testplan_tcversions` WHERE `id` = {$linkedVersion['id']}";
						$db -> exec_query($sql1);
					}
				}
				$sql1 = "/* $debugMsg */ DELETE FROM `" . DB_TABLE_PREFIX . "executions` WHERE `id` = {$resultInfo['id']}";
				$db -> exec_query($sql1);
			}
		}
	}

	/**
	 *
	 */
	function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir")
						rrmdir($dir . "/" . $object);
					else
						unlink($dir . "/" . $object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	/**
	 *
	 */
	function doBackup(&$db) {
		global $g_from_email;
		require_once ('email_api.php');
		$backupTimerOn = microtime(true);

		// Date used across the backup process
		$date = date("d_m_Y", time());
		// Email details
		$email_to = array("dgogawale@saba.com","svuruputoor@saba.com");
		$message = "Hi,

TestLink backup is ready for download at below link,
http://{$_SERVER['SERVER_NAME']}/backup/backup-latest.zip


Thanks,
TestLink";
		$message = nl2br($message);
		//save file
		$path = TL_ABS_PATH . "backup" . DIRECTORY_SEPARATOR . $date . DIRECTORY_SEPARATOR;
		@mkdir($path);
		backup_database($db, '*', $path, $date);
		backup_files($path . "files_backup");
		for ($i = 1; $i < 7; $i++) {
			if (file_exists(TL_ABS_PATH . "backup" . DIRECTORY_SEPARATOR . "backup-$i.zip")) {
				rename(TL_ABS_PATH . "backup" . DIRECTORY_SEPARATOR . "backup-$i.zip", TL_ABS_PATH . "backup" . DIRECTORY_SEPARATOR . "backup-" . ($i + 1) . ".zip");
			}
		}
		if (file_exists(TL_ABS_PATH . "backup" . DIRECTORY_SEPARATOR . "backup-latest.zip")) {
			rename(TL_ABS_PATH . "backup" . DIRECTORY_SEPARATOR . "backup-latest.zip", TL_ABS_PATH . "backup" . DIRECTORY_SEPARATOR . "backup-1.zip");
		}
		exec("zip -9 -r " . TL_ABS_PATH . "backup" . DIRECTORY_SEPARATOR . "backup-latest.zip $path");
		foreach ($email_to as $emailId) {
			email_send($g_from_email, $emailId, "TestLink Backup - $date", $message, '', false, true);
		}
		rrmdir(TL_ABS_PATH . "backup" . DIRECTORY_SEPARATOR . $date);

		$backupTimerOff = microtime(true);
		echo "<hr />Backup Done & Mailed in " . round($backupTimerOff - $backupTimerOn, 2) . " seconds <hr />";
	}

	/**
	 *
	 */
	function backup_files($path) {
		recurse_copy(TL_ABS_PATH, $path);
	}

	/* Copy files/dir recursively  */
	function recurse_copy($src, $dst) {
		$dir = opendir($src);
		@mkdir($dst);
		while (false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . '/' . $file) and in_array($file, array(
					'backup',
					'.git',
					'.settings',
					'.accurev'
				))) {
					continue;
				}
				if (is_dir($src . '/' . $file)) {
					recurse_copy($src . '/' . $file, $dst . '/' . $file);
				} else {
					copy($src . '/' . $file, $dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

	/**
	 *
	 */
	function backupTableStructureOnly(&$db, $tables = '*') {
		if ($tables == '*') {
			$tables = tlDBObject::getDBTables();
		} else {
			$tables = is_array($tables) ? $tables : explode(',', $tables);
		}
		$return = "-- TestLink Table Structures SQL";
		foreach ($tables as $table) {
			$createtable_result = $db -> exec_query('SHOW CREATE TABLE ' . $table);
			if (is_null($createtable_result)) {
				continue;
			}
			$row2 = $db -> fetch_array($createtable_result);
			$return . "\n-- Create Table : " . $table;
			$return .= "\n\n" . str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $row2['Create Table']) . ";\n\n";
		}
		return $return;
	}

	/**
	 *
	 */
	function backupTableDataOnly(&$db, $tables = '*') {
		if ($tables == '*') {
			$tables = tlDBObject::getDBTables();
		} else {
			$tables = is_array($tables) ? $tables : explode(',', $tables);
		}
		$return = '';
		foreach ($tables as $table) {
			try {
				$return .= "\n-- Table Data: $table\n";
				$result = $db -> exec_query('SELECT * FROM ' . $table);
				$num_fields = $db -> num_rows($result);
				for ($i = 0; $i < $num_fields; $i++) {
					$records = array();
					$record_line = '';
					if (!isset($column_line) or is_null($column_line)) {
						$column_line = '';
					}
					while ($row = $db -> fetch_array($result)) {
						$rowKeys = array();
						foreach ($row as $key => $val) {
							$rowKeys[] = "`$key`";
						}
						$column_line = implode(",", $rowKeys);
						$record_line = '(';
						$j = 0;
						$row_col_count = count($row);
						foreach ($row as $entry) {
							$entry = addslashes($entry);
							$entry = str_replace("\n", "\\n", $entry);
							if (isset($entry)) {
								$record_line .= '"' . $entry . '"';
							} else {
								$record_line .= '""';
							}
							if ($j < ($row_col_count - 1)) {
								$record_line .= ',';
							}
							$j++;
						}
						$record_line .= ')';
						$records[] = $record_line;
						if (count($records) >= 100) {
							$return .= "INSERT INTO $table ($column_line) VALUES " . implode(",\n", $records) . ";\n";
							$records = array();
						}
					}
					if (count($records) > 0) {
						$return .= "INSERT INTO $table ($column_line) VALUES " . implode(",\n", $records) . ";\n";
						$records = array();
					}
				}
				$return .= "\n";
			} catch(Exception $e) {
				echo '<br />==================================<br />';
				echo $e -> getMessage() . '';
				echo '<br />==================================<br />';
			}
		}
		return $return;
	}

	/**
	 *  Backup the db OR just a table
	 *
	 * 	@param object $db is the database object
	 * 	@param string/array $tables containing list of tables to be backed up (default value is '*' to backup all tables)
	 * 	@param string $path is the location to store the SQL's generated as .gzip
	 * 	@param string $date d_m_Y format to add in the filename
	 *
	 */
	function backup_database(&$db, $tables = '*', $path = '', $date = null) {
		if (is_null($date)) {
			$date = date("d_m_Y", time());
		}
		//get all of the tables
		$views = tlDBObject::getDBViews();

		$return = "-- TestLink SQL Dump";
		//cycle through
		$return .= backupTableStructureOnly($db);
		$return .= backupTableDataOnly($db);
		/*
		 foreach ($views as $view) {
		 try {
		 $row2 = $db -> fetch_array($db -> exec_query('SHOW CREATE VIEW ' . $view));
		 $return .= "\n-- Create View : " . $view;
		 $return .= "\n\n" . str_replace('CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW', 'CREATE OR REPLACE VIEW', $row2['Create View']) . ";\n";
		 } catch(Exception $e) {
		 echo '<br />==================================<br />';
		 echo $e -> getMessage() . '';
		 echo '<br />==================================<br />';
		 }
		 }
		 * */
		$hash = md5(implode(',', $tables));
		$handle = fopen($path . 'db-backup-' . $date . '-' . $hash . '.sql', 'w+');
		fwrite($handle, $return);
		fclose($handle);
		// Name of the file we are compressing
		$file_1 = $path . 'db-backup-' . $date . '-' . $hash . '.sql';
		// Name of the gz file we are creating
		$gzfile = $file_1 . '.gz';
		// Open the gz file (w9 is the highest compression)
		$fp = gzopen($gzfile, 'w9');
		// Compress the file
		gzwrite($fp, file_get_contents($file_1));
		// Close the gz file and we are done
		gzclose($fp);
		if (file_exists($gzfile)) {
			unlink($file_1);
		}
	}

	/**
	 *
	 */
	function downloadFile($file) {
		if (file_exists($file)) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($file));
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			ob_clean();
			flush();
			readfile($file);
			unlink($file);
			exit ;
		}
	}

	/**
	 *
	 */
	function getFreeCasesWithSuites(&$db, $tprojectId) {
		$tproject_mgr = new testproject($db);
		$freeTestCases = $tproject_mgr -> getFreeTestCases($tprojectId);
		if (!is_null($freeTestCases['items'])) {
			$tcaseSet = array_keys($freeTestCases['items']);
			$tsuites = $tproject_mgr -> tree_manager -> get_full_path_verbose($tcaseSet, array('output_format' => 'path_as_string'));
			return $tsuites;
		}
		return array();

	}

	/**
	 *
	 */
	function deleteFreeCasesByModuleName(&$db, $tprojectId, $moduleName) {
		$tcase_mgr = new sabatestcase($db);
		$tcase_tsuites = getFreeCasesWithSuites($db, $tprojectId);
		$i = 0;
		if (is_array($tcase_tsuites) and count($tcase_tsuites) > 0) {
			foreach ($tcase_tsuites as $tcase_id => $suite_path) {
				if (strpos($suite_path, $moduleName) === 0) {
					$tcase_mgr -> delete($tcase_id);
					$i++;
					echo 'Deleted Case: ' . $tcase_id . ' <br />';
				}
			}
		}
		echo 'Cases deleted: ' . $i;
	}

	/**
	 *
	 * @param userId defaulted 96 as TestLink Bot
	 */
	function moveFreeCasesToPlan(&$db, $tprojectId, $tplanId, $tsuitePartialName = null, $userId = 96) {
		$tcase_mgr = new sabatestcase($db);
		$tproject_mgr = new testproject($db);
		$tplan_mgr = new testplan($db);
		$freeTestCases = $tproject_mgr -> getFreeTestCases($tprojectId);
		if (!is_null($freeTestCases['items'])) {
			$tcaseSet = array_keys($freeTestCases['items']);
			$tsuites = $tproject_mgr -> tree_manager -> get_full_path_verbose($tcaseSet, array('output_format' => 'path_as_string'));
			foreach ($tsuites as $tcase_id => $tsuite_name) {
				$tcVerInfo = $tcase_mgr -> get_last_version_info($tcase_id);
				$link = true;
				if (!is_null($tsuitePartialName)) {
					if (strpos($tsuite_name, $tsuitePartialName) === false) {
						$link = false;
					}
				}
				if ($link) {
					$items_to_link = array();
					$items_to_link = array();
					$items_to_link['tcversion'][$tcase_id] = $tcVerInfo['id'];
					$items_to_link['platform'] = array();
					$items_to_link['items'] = array();
					$items_to_link['platform'][0] = 0;
					$items_to_link['items'][$tcase_id][0] = $tcVerInfo['id'];
					$tplan_mgr -> link_tcversions($tplanId, $items_to_link, $userId);
				}
			}
		}
	}

	/**
	 *
	 */
	function addHCToFreeCases(&$db, $tprojectId, $horizontalComponent) {
		$tcase_mgr = new sabatestcase($db);
		$tproject_mgr = new testproject($db);
		$kwMP = $tproject_mgr -> get_keywords_map($tprojectId);
		$kwMP = array_flip($kwMP);
		$kw_id = null;
		if (!array_key_exists($horizontalComponent, $kwMP)) {
			$kw_id = $tproject_mgr -> addKeyword($tprojectId, $horizontalComponent, '');
		} else {
			$kw_id = $kwMP[$horizontalComponent];
		}
		$tcase_tsuites = getFreeCasesWithSuites($db, $tprojectId);
		$i = 0;
		if (is_array($tcase_tsuites) and count($tcase_tsuites) > 0) {
			foreach ($tcase_tsuites as $tcase_id => $tsuite_name) {
				$tcase_mgr -> addKeyword($tcase_id, $kw_id);
				echo 'Added ' . $horizontalComponent . ' to case: ' . $tcase_id . '<br />';
			}
		}
	}

	/**
	 *
	 */
	function fixSECTestingPriorityToImportance(&$db) {
		$tables = tlDBObject::getDBTables();
		$query = "SELECT node_id, value FROM {$tables['cfield_design_values']} WHERE field_id = 21";
		$records = $db -> get_recordset($query);
		$priority = array(
			'Tier1' => 1,
			'Tier2' => 2,
			'Smoke' => 3
		);
		foreach ($records as $rec) {
			$value = 1;
			if (isset($priority[$rec['value']])) {
				$value = $priority[$rec['value']];
			}
			$sql = "UPDATE {$tables['tcversions']}
					SET `importance` = $value
					WHERE `id` = " . $rec['node_id'];
			$db -> exec_query($sql);
			echo "Version Id: " . $rec['node_id'] . ' changed to: ' . $priority[$rec['value']] . ' == ' . $value . '<br />';
		}
	}

	/**
	 *
	 */
	function fixAutomatedCasesAsRegression(&$db, $tproject_id) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		$tcase_mgr = new sabatestcase($db);
		$tables = $tcase_mgr -> getDBTables();
		$query = "/* $debugMsg */ SELECT NHV.id AS tcversion_id, NHC.id AS tcase_id
FROM {$tables['releases']} RL
JOIN {$tables['release_testplans']} RTP ON RL.id = RTP.release_id AND RL.testproject_id = $tproject_id
JOIN {$tables['testplan_tcversions']} TCV ON TCV.testplan_id = RTP.testplan_id
JOIN {$tables['nodes_hierarchy']} NHV ON NHV.id = TCV.tcversion_id
JOIN {$tables['cfield_design_values']} CDV ON NHV.id = CDV.node_id AND CDV.field_id = 20 AND NHV.node_type_id = 4
JOIN {$tables['nodes_hierarchy']} NHC ON NHC.id = NHV.parent_id
JOIN {$tables['testplan_tcversions']} TCV ON TCV.tcversion_id = NHV.id
WHERE CDV.value = 'Automation Done'";

		$records = $db -> get_recordset($query);

		if (!is_null($records)) {
			foreach ($records as $record) {
				$case_cfield = $tcase_mgr -> get_linked_cfields_at_design($record['tcase_id'], $record['tcversion_id']);
				$value = $case_cfield[2]['value'];
				if ($value == null) {
					$value = '';
				}
				if (strpos($value, 'Regression') === false) {
					if ($value != '') {
						$value .= '|';
					}
					$value .= 'Regression';
					$hash = array('custom_field_' . $case_cfield[2]['type'] . '_' . $case_cfield[2]['id'] => $value);
					$tcase_mgr -> cfield_mgr -> design_values_to_db($hash, $record['tcversion_id']);
					echo 'Updated - ' . json_encode($record) . '<br />';
				}
			}
		}
	}

	/**
	 * 	Synchronises build creation on every plan in active releases
	 *
	 * 	@param object $db is the database object
	 */
	function autoUpdateBuilds(&$db) {
		//$_timerOn = microtime(true);
		$tplan_mgr = new testplan($db);
		$tproject_mgr = new testproject($db);

		$testprojectIdArr1 = array();
		$buildCreate = false;
		$buildCreateNames = array();
		$buildCreateIds = array();
		if (isset($_SESSION['testprojectID']) and $_SESSION['testprojectID'] != '') {
			$testprojectIdArr1[$_SESSION['testprojectID']] = $_SESSION['testprojectID'];
		} else {
			$testprojectIdArr1 = $tproject_mgr -> get_all(array('active' => true), array('access_key' => 'id'));
		}
		foreach ($testprojectIdArr1 as $tprojectId1 => $testproject) {
			//$tprojectInfo = $tproject_mgr -> get_by_id($tprojectId1);
			$release_mgr = new release($db, $tprojectId1);
			$all_releases1 = $release_mgr -> get_all(array('active' => 1));

			foreach ($all_releases1 as $release1) {
				$global_builds1 = array();
				$all_testplans = $release_mgr -> get_linked_plans($release1['id']);
				if (!is_null($all_testplans)) {
					foreach ($all_testplans as $tplanId1 => $tplan1) {
						$all_builds = $tplan_mgr -> get_builds($tplanId1, 1, 1);
						if (!is_null($all_builds)) {
							foreach ($all_builds as $buildInfo1) {
								if (trim($buildInfo1['name']) == '') {
									continue;
								}
								$global_builds1[trim($buildInfo1['name'])] = $buildInfo1;
							}
						}
					}
				}
				if (!is_null($global_builds1) and count($global_builds1) > 0) {
					foreach ($all_testplans as $tplanId1 => $tplan1) {
						foreach ($global_builds1 as $buildName1 => $buildInfo1) {
							$build_info1 = $tplan_mgr -> get_build_by_name($tplanId1, $buildName1);
							if (is_null($build_info1)) {
								//echo "Tplan Id: $tplanId1 Build: $buildName1<br />";
								try {
									$temp_build_id = $tplan_mgr -> create_build($tplanId1, $buildName1);
								} catch(Exception $e) {

								}
								//logAuditEvent(TLS("audit_auto_build_created", $tprojectInfo['name'], $tplan1['name'], $buildName1), "CREATE", $temp_build_id, "builds");
								//echo 'Created: ' . $buildName1 . ' plan id: ' . $tplanId1 . '<br />';
							}
						}
					}
				}
			}
		}
		//echo 'Auto Build Creation Complete - ' . round(microtime(true) - $_timerOn, 2) . '<br />';
	}

	/**
	 *	Execute Results Automatically if the last status of the issue is Failed/Blocked and issues linked to them are Closed depending on the Resolution effect
	 *
	 * 	@param object $db is the database object
	 */
	function auto_execute_results(&$db) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		$tproject_mgr = new testproject($db);
		$tplan_mgr = new testplan($db);
		$tprojects = $tproject_mgr -> get_all();
		$tables = tlDBObject::getDBTables();
		$views = tlDBObject::getDBViews();
		$users = tlUser::getAll($db, " WHERE active = 1");
		$managers = array();
		$admins = array();
		foreach ($users as $user_id => $userObj) {
			if ($userObj -> globalRole -> name == 'Saba Administrator') {
				$admins[] = $userObj -> emailAddress;
			}
			if ($userObj -> globalRole -> name == 'Saba Test Manager') {
				$managers[$userObj -> defaultTestprojectID] = $userObj;
			}
			if ($userObj -> login == 'rpithwa') {
				$managers[$userObj -> defaultTestprojectID] = $userObj;
			}
		}
		$tplans = array();

		/**
		 * Load Execution Colours
		 */
		$resultCfg = config_get('results');
		$colors = $resultCfg['charts']['status_colour'];
		$resultHTMLCSS = array();
		foreach ($colors as $c => $r) {
			$resultHTMLCSS[$resultCfg['status_code'][$c]] = '<span style="font-weight: bold; color: #' . $r . '">' . lang_get($resultCfg['status_label'][$c]) . '</span>';
		}

		/**
		 * Get Global FROM email address
		 */
		global $g_from_email;

		/**
		 * Configuration all bad builds name in lowercase
		 */
		$badbuild = array(
			'na',
			'n/a',
			'latest'
		);

		/**
		 * Email-Message Template to notify tester
		 * Invalid Build on Closed Bugs
		 */
		$invalid_build_message = "Hi %NAME%,
		
		Please find the below test cases that are executed as failed/blocked have bugs closed on Jira but has invalid build name associated in Verified in Build a Jira Field.
		We request you to please check cases below and update their execution results to the latest with proper builds.
		
		" . '<table border="1"><tr><th style="width: 45%;">Test Case Name</th><th style="width: 15%">Verified In Build</th><th style="width: 15%">Build</th><th style="width: 15%">Platform</th><th style="width: 10%">Status</th></tr>' . "%CASE_LIST%</table>
		
		
		";
		$invalid_build_message = nl2br($invalid_build_message);

		/**
		 * Email-Message template to notify tester
		 * Failed/Blocked Cases with No Bugs
		 */
		$failed_cases_message = "Hi %NAME%,
		
		Please find the below test cases that are executed as failed/blocked but are missing with Jira Bug Id association.
		We request you to please check cases below and link appropriate bugs to them.
		
		" . '<table border="1"><tr><th style="width: 50%;">Test Case Name</th><th style="width: 20%">Build</th><th style="width: 15%">Platform</th><th style="width: 15%">Status</th></tr>' . "%CASE_LIST%</table>
		
		
		";
		$failed_cases_message = nl2br($failed_cases_message);

		$closed_bugs_message = "Hi %NAME%,
		
		FYI
		Please find below test cases that are executed for Closed Jira Bug Id associated with Failed/Bloced cases.
		
		" . '<table border="1"><tr><th style="width: 50%;">Test Case Name</th><th style="width: 20%">Build</th><th style="width: 15%">Platform</th><th style="width: 15%">Status</th></tr>' . "%CASE_LIST%</table>
		
		
		";
		$closed_bugs_message = nl2br($closed_bugs_message);

		/**
		 * Make up an array to email testers about their pending cases that needs an update
		 */
		$email_data = array(
			'invalid_build' => array(),
			'failed_cases_no_bugs' => array(),
			'notify_admins' => array(),
			'unlinked_cases' => array()
		);
		foreach ($tprojects as $tproject_info) {
			$it_mgr = new tlIssueTracker($db);
			$its = $it_mgr -> getInterfaceObject($tproject_info['id']);
			if (is_null($its)) {
				continue;
			}
			$release_mgr = new release($db, $tproject_info['id']);
			$releases = $release_mgr -> get_all(array('active' => 1));
			$release_ids = array();
			if (!is_null($releases)) {
				foreach ($releases as $release_info) {
					$release_plans = $release_mgr -> get_linked_plans($release_info['id']);
					if (!is_null($release_plans) and is_array($release_plans)) {
						foreach ($release_plans as $plan) {
							$tplans[$plan['id']] = $plan;
						}
					}
					$release_ids[] = $release_info['id'];
				}

				$sql = "/* $debugMsg */ SELECT E.id AS exec_id, E.build_id AS build_id, E.tester_id AS tester_id, E.execution_ts AS execution_ts, E.status AS status,
			E.testplan_id AS testplan_id, E.tcversion_id AS tcversion_id, E.tcversion_number AS tcversion_number, E.platform_id AS platform_id,
			E.execution_type AS execution_type, E.notes AS notes, NHV.name AS tcase_name, TC.tc_external_id AS external_id, BI.name AS build_name, PL.name AS platform_name
			FROM {$tables['release_testplans']} RT 
			JOIN {$tables['testplan_tcversions']} TT ON TT.testplan_id = RT.testplan_id
			JOIN {$tables['tcversions']} TC ON TC.id = TT.tcversion_id AND TC.execution_type = 1
			JOIN {$tables['nodes_hierarchy']} NH ON NH.id = TT.tcversion_id
			JOIN {$tables['nodes_hierarchy']} NHV ON NH.parent_id = NHV.id
			JOIN (
					SELECT  `tcversion_id` AS  `tcversion_id` ,  `testplan_id` AS  `testplan_id` ,  `platform_id` AS  `platform_id` , MAX(  `id` ) AS  `id` 
					FROM {$tables['executions']} GROUP BY  `tcversion_id` ,  `testplan_id` ,  `platform_id` 
			) ES ON ES.tcversion_id = TT.tcversion_id AND ES.platform_id = TT.platform_id AND ES.testplan_id = TT.testplan_id
			JOIN {$tables['executions']} E ON E.id = ES.id AND E.status <> 'p'
			JOIN {$tables['builds']} BI ON BI.id = E.build_id
			JOIN {$tables['platforms']} PL ON PL.id = E.platform_id 
			WHERE RT.release_id IN (" . implode(",", $release_ids) . ")";
				$result = $db -> exec_query($sql);
				//echo "Total Not Passed: " . $db -> num_rows($result) . "<br />";
				if ($db -> num_rows($result) > 0) {
					while ($exec_bug_row = $db -> fetch_array($result)) {
						$sql = "SELECT * FROM {$tables['execution_bugs']} WHERE execution_id = " . $exec_bug_row['exec_id'];
						$bugs = $db -> get_recordset($sql);
						$issue_details = null;
						$closed = false;
						$unlink = false;
						if (!is_null($bugs)) {
							$notes = '';
							$status = 'n';
							foreach ($bugs as $bug) {
								$issue_details = $its -> getIssue($bug['bug_id']);
								if ($issue_details -> fields -> status -> id == '6') {
									switch($issue_details -> fields -> resolution -> id) {
										case '1' :
										case '11' :
										case '5' :
										case '3' :
										case '7' :
											$notes = 'Executing the case as associated bugs are closed with Resolution as "' . $issue_details -> fields -> resolution -> name . '"';
											$closed = true;
											$status = 'p';
											break;
										case '24' :
											$closed = true;
											$unlink = true;
											$status = 'x';
											break;
										default :
											$close = false;
											break;
									}
									break;
								}
							}
							if ($unlink) {
								$item_to_unlink = array();
								$items_to_unlink['tcversion'][$exec_bug_row['tcase_id']] = $exec_bug_row['tcversion_id'];
								$items_to_unlink['platform'][$exec_bug_row['platform_id']] = $exec_bug_row['platform_id'];
								$items_to_unlink['items'][$exec_bug_row['tcase_id']][$exec_bug_row['platform_id']] = $exec_bug_row['tcversion_id'];
								$tplan_mgr -> unlink_tcversions($exec_bug_row['testplan_id'], $items_to_unlink);
								if (!isset($email_data['unlinked_cases'][$exec_bug_row['testplan_id']])) {
									$email_data['unlinked_cases'][$exec_bug_row['testplan_id']] = array();
								}
								$email_data['unlinked_cases'][$exec_bug_row['testplan_id']][] = array(
									'testplan_id' => $exec_bug_row['testplan_id'],
									'tcase_name' => $exec_bug_row['tcase_name'],
									'bug_id' => $issue_details -> key,
									'resolution' => $issue_details -> fields -> resolution -> name,
									'verifiedInBuild' => $issue_details -> fields -> customfield_10117,
									'external_id' => $exec_bug_row['external_id'],
									'status' => $exec_bug_row['status'],
									'build_name' => $exec_bug_row['build_name'],
									'verified_in_build' => $issue_details -> fields -> customfield_10117,
									'platform_name' => $exec_bug_row['platform_name'],
									'project_key' => $tproject_info['prefix'],
									'project_id' => $tproject_info['id']
								);
							} else if ($closed and !is_null($issue_details)) {
								$build_name = identifyBuildNumber($issue_details -> fields -> customfield_10117);
								if (in_array(strtolower($build_name), $badbuild)) {
									if (!isset($email_data['invalid_build'][$exec_bug_row['tester_id']])) {
										$email_data['invalid_build'][$exec_bug_row['tester_id']] = array();
									}
									if (!isset($email_data['invalid_build'][$exec_bug_row['tester_id']][$exec_bug_row['testplan_id']])) {
										$email_data['invalid_build'][$exec_bug_row['tester_id']][$exec_bug_row['testplan_id']] = array();
									}
									$email_data['invalid_build'][$exec_bug_row['tester_id']][$exec_bug_row['testplan_id']][] = array(
										'testplan_id' => $exec_bug_row['testplan_id'],
										'tcase_name' => $exec_bug_row['tcase_name'],
										'bug_id' => $issue_details -> key,
										'resolution' => $issue_details -> fields -> resolution -> name,
										'verifiedInBuild' => $issue_details -> fields -> customfield_10117,
										'external_id' => $exec_bug_row['external_id'],
										'status' => $exec_bug_row['status'],
										'build_name' => $exec_bug_row['build_name'],
										'verified_in_build' => $issue_details -> fields -> customfield_10117,
										'platform_name' => $exec_bug_row['platform_name'],
										'project_key' => $tproject_info['prefix'],
										'project_id' => $tproject_info['id']
									);
								} else {
									$build_id = $tplan_mgr -> get_build_by_name($exec_bug_row['testplan_id'], $build_name);
									if (!is_null($build_id)) {
										$build_id = $build_id['id'];
									} else {
										$build_id = $tplan_mgr -> create_build($exec_bug_row['testplan_id'], $build_name);
									}

									if (!isset($email_data['notify_admins'][$exec_bug_row['testplan_id']])) {
										$email_data['notify_admins'][$exec_bug_row['testplan_id']] = array();
									}
									$email_data['notify_admins'][$exec_bug_row['testplan_id']][] = array(
										'tcase_name' => $exec_bug_row['tcase_name'],
										'external_id' => $exec_bug_row['external_id'],
										'status' => $exec_bug_row['status'],
										'project_key' => $tproject_info['prefix'],
										'build_name' => $issue_details -> fields -> customfield_10117,
										'platform_name' => $exec_bug_row['platform_name'],
										'project_id' => $tproject_info['id']
									);

									$exec_bug_row['build_id'] = $build_id;
									$exec_bug_row['status'] = $status;
									$exec_bug_row['notes'] = 'Executed as Resolution is Fixed for the bug associated';
									unset($exec_bug_row['bug_id']);
									unset($exec_bug_row['exec_id']);
									unset($exec_bug_row['execution_ts']);
									unset($exec_bug_row['tcase_name']);
									unset($exec_bug_row['external_id']);
									unset($exec_bug_row['build_name']);
									unset($exec_bug_row['platform_name']);
									$cols = array();
									$vals = array();
									foreach ($exec_bug_row as $col => $val) {
										$cols[] = "`$col`";
										if (is_integer($val) or is_int($val) or is_numeric($val)) {
											$vals[] = $val;
										} else {
											$vals[] = "'$val'";
										}
									}
									$cols[] = "`execution_ts`";
									$vals[] = $db -> db_now();
									$sql = "INSERT INTO `" . DB_TABLE_PREFIX . "executions` (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ")";
									//echo $sql . '<br />';
									$db -> exec_query($sql);
									//echo 'Update tcversion id: ' . $exec_bug_row['tcversion_id'] . ' on Build - ' . $issue_details -> fields -> customfield_10117 . '<br />';
								}
							}
						} else {
							if (!isset($email_data['failed_cases_no_bugs'][$exec_bug_row['tester_id']])) {
								$email_data['failed_cases_no_bugs'][$exec_bug_row['tester_id']] = array();
							}
							if (!isset($email_data['failed_cases_no_bugs'][$exec_bug_row['tester_id']][$exec_bug_row['testplan_id']])) {
								$email_data['failed_cases_no_bugs'][$exec_bug_row['tester_id']][$exec_bug_row['testplan_id']] = array();
							}
							$email_data['failed_cases_no_bugs'][$exec_bug_row['tester_id']][$exec_bug_row['testplan_id']][] = array(
								'tcase_name' => $exec_bug_row['tcase_name'],
								'external_id' => $exec_bug_row['external_id'],
								'status' => $exec_bug_row['status'],
								'project_key' => $tproject_info['prefix'],
								'build_name' => $exec_bug_row['build_name'],
								'platform_name' => $exec_bug_row['platform_name'],
								'project_id' => $tproject_info['id']
							);
						}
					}
				}
			}
			unset($its);
		}
		$guestToken = getRandomGuestToken();
		$basehref = $_SESSION['basehref'];
		$exec_img = '<img style="vertical-align:middle" src="' . $basehref . TL_THEME_IMG_DIR . 'exec_icon.png" />';
		$disp_img = '<img style="vertical-align:middle" src="' . $basehref . TL_THEME_IMG_DIR . 'world_link.png" />';
		foreach ($email_data['failed_cases_no_bugs'] as $user_id => $planArr) {
			$user = $users[$user_id];
			$name = $user -> firstName . ' ' . $user -> lastName;
			$email = '';
			$project_id = 0;
			foreach ($planArr as $tplan_id => $failedCasesArr) {
				$plan_exec_url = $basehref . "lib/testcases/myTCLinkedToPlan.php?tplanID=" . $tplan_id . "&amp;myTC=false";
				$plan_report_url = $basehref . "lib/results/resultByTestPlan.php?tplanID=" . $tplan_id . "&amp;format=0&amp;guestToken=" . $guestToken;
				$email .= '<tr><td colspan="2" style="background: #eee">Plan Name: <b>' . $tplans[$tplan_id]['name'] . '</b></td>';
				$email .= '<td style="text-align: center;background: #eee"><a style="text-decoration: none" title="Execute Test ' . $tplans[$tplan_id]['name'] . '" href="' . $plan_exec_url . '">' . $exec_img . ' Execute</a></td>';
				$email .= '<td style="text-align: center;background: #eee"><a style="text-decoration: none" title="View Test Plan: ' . $tplans[$tplan_id]['name'] . '" href="' . $plan_report_url . '"> ' . $disp_img . ' Report</a></td></tr>';
				foreach ($failedCasesArr as $failed_blocked) {
					$email .= '<td><b>' . $failed_blocked['project_key'] . '-' . $failed_blocked['external_id'] . '</b> : ' . $failed_blocked['tcase_name'] . '</td>';
					$email .= '<td style="text-align: center">' . $failed_blocked['build_name'] . '</td>';
					$email .= '<td style="text-align:center">' . $failed_blocked['platform_name'] . '</td>';
					$email .= '<td style="text-align: center">' . $resultHTMLCSS[$failed_blocked['status']] . '</td></tr>';
					$project_id = $failed_blocked['project_id'];
				}
			}
			$email = str_replace("%CASE_LIST%", $email, $failed_cases_message);
			$email = str_replace("%NAME%", $name, $email);
			$emailAddress = $user -> emailAddress;
			$emailAddress = 'rsakhale@saba.com';
			$managerEmail = $managers[$project_id] -> emailAddress;
			$managerEmail = '';
			email_send($g_from_email, $emailAddress, "TestLink Failed/Blocked Cases with no Bugs", $email, $managerEmail, false, true, $name);
		}

		foreach ($email_data['invalid_build'] as $user_id => $planArr) {
			$user = $users[$user_id];
			$name = $user -> firstName . ' ' . $user -> lastName;
			$email = '';
			$project_id = 0;
			foreach ($planArr as $tplan_id => $failedCasesArr) {
				$plan_exec_url = $basehref . 'lib/testcases/myTCLinkedToPlan.php?tplanID=' . $tplan_id . "&amp;myTC=false";
				$plan_report_url = $basehref . 'lib/results/resultByTestPlan.php?tplanID=' . $tplan_id . "&amp;format=0&amp;guestToken=" . $guestToken;
				$email .= '<tr><td colspan="3" style="background: #eee">Plan Name: ' . $tplans[$tplan_id]['name'] . '<b></b></td>';
				$email .= '<td style="text-align: center;background: #eee"><a style="text-decoration: none" title="Execute Test ' . $tplans[$tplan_id]['name'] . '" href="' . $plan_exec_url . '">' . $exec_img . ' Execute</a></td>';
				$email .= '<td style="text-align: center;background: #eee"><a style="text-decoration: none" title="View Test Plan: ' . $tplans[$tplan_id]['name'] . '" href="' . $plan_report_url . '">' . $disp_img . ' Report</a></td></tr>';
				foreach ($failedCasesArr as $failed_blocked) {
					$email .= '<td><b>' . $failed_blocked['project_key'] . '-' . $failed_blocked['external_id'] . '</b> : ' . $failed_blocked['tcase_name'] . '</td>';
					$email .= '<td style="text-align: center">' . $failed_blocked['verified_in_build'] . '</td>';
					$email .= '<td style="text-align: center">' . $failed_blocked['build_name'] . '</td>';
					$email .= '<td style="text-align:center">' . $failed_blocked['platform_name'] . '</td>';
					$email .= '<td style="text-align: center">' . $resultHTMLCSS[$failed_blocked['status']] . '</td></tr>';
					$project_id = $failed_blocked['project_id'];
				}
			}
			$email = str_replace("%CASE_LIST%", $email, $invalid_build_message);
			$email = str_replace("%NAME%", $name, $email);
			$emailAddress = $user -> emailAddress;
			$managerEmail = $managers[$project_id] -> emailAddress;
			$emailAddress = 'rsakhale@saba.com';
			$managerEmail = '';
			email_send($g_from_email, $emailAddress, "TestLink Failed/Blocked Cases with closed bugs requires your attention", $email, $managerEmail, false, true, $name);
		}
		$adminEmailAddress = implode(",", $admins);
		$adminEmailAddress = 'rsakhale@saba.com';
		$name = 'Admin';
		if (count($email_data['notify_admins']) > 0) {
			$email = '';
			foreach ($email['unlinked_cases'] as $tplan_id => $unlinkedCasesArr) {
				$plan_exec_url = $basehref . 'lib/testcases/myTCLinkedToPlan.php?tplanID=' . $tplan_id . "&amp;myTC=false";
				$plan_report_url = $basehref . 'lib/results/resultByTestPlan.php?tplanID=' . $tplan_id . "&amp;format=0&amp;guestToken=" . $guestToken;
				$email .= '<tr><td colspan="2" style="background: #eee">Plan Name: ' . $tplans[$tplan_id]['name'] . '<b></b></td>';
				$email .= '<td style="text-align: center;background: #eee"><a style="text-decoration: none" title="Execute Test ' . $tplans[$tplan_id]['name'] . '" href="' . $plan_exec_url . '">' . $exec_img . ' Execute</a></td>';
				$email .= '<td style="text-align: center;background: #eee"><a style="text-decoration: none" title="View Test Plan: ' . $tplans[$tplan_id]['name'] . '" href="' . $plan_report_url . '">' . $disp_img . ' Report</a></td></tr>';
				foreach ($unlinkedCasesArr as $unlinked_case) {
					$email .= '<tr><td><b>' . $unlinked_case['project_key'] . '-' . $unlinked_case['external_id'] . ' : ' . $unlinked_case['tcase_name'] . '</b></td>';
					$email .= '<td style="text-align: center">' . $failed_blocked['build_name'] . '</td>';
					$email .= '<td style="text-align:center">' . $failed_blocked['platform_name'] . '</td></tr>';
				}
			}
			$email = str_replace("%TCASE_LIST%", $email, $unlinked_cases_message);
			$email = str_replace("%NAME%", $name, $email);
			email_send($g_from_email, $adminEmailAddress, "TestLink Unlinked Cases with closed bugs", $email, '', false, true, $name);
			$email = '';
			foreach ($email_data['notify_admins'] as $tplan_id => $failedCasesArr) {
				$plan_exec_url = $basehref . 'lib/testcases/myTCLinkedToPlan.php?tplanID=' . $tplan_id . "&amp;myTC=false";
				$plan_report_url = $basehref . 'lib/results/resultByTestPlan.php?tplanID=' . $tplan_id . "&amp;format=0&amp;guestToken=" . $guestToken;
				$email .= '<tr><td colspan="2" style="background: #eee">Plan Name: ' . $tplans[$tplan_id]['name'] . '<b></b></td>';
				$email .= '<td style="text-align: center;background: #eee"><a style="text-decoration: none" title="Execute Test ' . $tplans[$tplan_id]['name'] . '" href="' . $plan_exec_url . '">' . $exec_img . ' Execute</a></td>';
				$email .= '<td style="text-align: center;background: #eee"><a style="text-decoration: none" title="View Test Plan: ' . $tplans[$tplan_id]['name'] . '" href="' . $plan_report_url . '">' . $disp_img . ' Report</a></td></tr>';
				foreach ($failedCasesArr as $failed_blocked) {
					$email .= '<td><b>' . $failed_blocked['project_key'] . '-' . $failed_blocked['external_id'] . '</b> : ' . $failed_blocked['tcase_name'] . '</td>';
					$email .= '<td style="text-align: center">' . $failed_blocked['build_name'] . '</td>';
					$email .= '<td style="text-align:center">' . $failed_blocked['platform_name'] . '</td>';
					$email .= '<td style="text-align: center">' . $resultHTMLCSS[$failed_blocked['status']] . '</td></tr>';
				}
			}
			$email = str_replace("%CASE_LIST%", $email, $closed_bugs_message);
			$email = str_replace("%NAME%", $name, $email);
			email_send($g_from_email, $adminEmailAddress, "TestLink: Executed Failed/Blocked Cases with closed bugs", $email, '', false, true, $name);
		}
	}

	/**
	 *	Notify plan authors about the plan progress which they need to give attention
	 *
	 * 	@param object $db is the database object
	 */
	function notify_plan_authors_progress(&$db) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		global $g_from_email;
		require_once ('email_api.php');
		$tplan_mgr = new testplan($db);
		$result_cfg = config_get('results');
		$guestToken = getRandomGuestToken();
		$tproject_mgr = new testproject($db);
		$metricsMgr = new tlTestPlanMetrics($db);
		$user_mgr = new tlUser($db);
		$tprojects = $tproject_mgr -> get_all();
		$tables = tlDBObject::getDBTables();
		$views = tlDBObject::getDBViews();
		$users_list = $user_mgr -> getAll($db, " WHERE active = 1");
		$notify_users = array();

		unset($result_cfg['status_label_for_exec_ui']['not_available']);
		$codeStatusVerbose = array_flip($result_cfg['status_code']);
		foreach ($result_cfg['status_label_for_exec_ui'] as $status_code => $dummy) {
			$metrics['total'][$status_code] = 0;
		}
		foreach ($tprojects as $tproject_info) {
			$release_mgr = new release($db, $tproject_info['id']);
			$releases = $release_mgr -> get_all(array('active' => 1));
			$release_ids = array();
			if (!is_null($releases)) {
				foreach ($releases as $release_info) {
					$plan_authors = array();
					$plan_assignee = array();
					$plans2notify = array(
						'needs_more_attention' => array(),
						'needs_attention' => array()
					);
					$metrics = array(
						'testplans' => null,
						'total' => null
					);
					$metrics['total'] = array('active' => 0);
					$test_plans = $release_mgr -> get_linked_plans($release_info['id']);
					if (is_null($test_plans)) {
						continue;
					}
					/**
					 * Get Test Plan Authors List
					 */
					$sql = "/* $debugMsg */ SELECT TPV.author_id as user_id, U.first as fname, U.last as lname, U.login, NHV.name, U.email, TPV.testplan_id, TPV.creation_ts
					FROM {$tables['testplan_tcversions']} TPV 
					JOIN {$tables['nodes_hierarchy']} NHV ON NHV.id = TPV.testplan_id
					JOIN {$tables['release_testplans']} RT ON RT.testplan_id = TPV.testplan_id AND RT.release_id = {$release_info['id']}
					LEFT JOIN {$tables['users']} U ON U.id = TPV.author_id
					WHERE 1
					GROUP BY TPV.testplan_id";

					$t_plan_authors = $db -> get_recordset($sql);
					$sql = "/* $debugMsg */ SELECT UA.user_id, U.first as fname, U.last as lname, U.login, NHV.name, U.email, RT.testplan_id
					FROM {$tables['release_testplans']} RT
					JOIN {$tables['nodes_hierarchy']} NHV ON NHV.id = RT.testplan_id
					JOIN {$tables['testplan_tcversions']} TPV ON TPV.testplan_id = RT.testplan_id AND RT.release_id = {$release_info['id']}
					JOIN {$tables['user_assignments']} UA ON UA.feature_id = TPV.id
					JOIN {$tables['users']} U on U.id = UA.user_id 
					WHERE 1
					GROUP BY TPV.testplan_id, UA.user_id";
					$uaUsers = $db -> get_recordset($sql);

					$mm = &$metrics['testplans'];
					foreach ($test_plans as $key => $dummy) {
						$buildSet = $tplan_mgr -> get_builds($key);
						if (is_null($buildSet)) {
							continue;
						}
						$mm[$key]['overall'] = $metricsMgr -> getExecCountersByExecStatus($key, null, array('getOnlyActiveTCVersions' => true));

						$mm[$key]['overall']['active'] = $mm[$key]['overall']['total'];
						$metrics['total']['active'] += $mm[$key]['overall']['active'];
						// compute executed
						$mm[$key]['overall']['executed'] = 0;
						foreach ($mm[$key]['overall'] as $status_code => $qty) {
							if ($status_code != 'not_run' && $status_code != 'total' && $status_code != 'active') {
								$mm[$key]['overall']['executed'] += $qty;
							}

							if ($status_code != 'total' && $status_code != 'active') {
								if (!isset($metrics['total'][$status_code])) {
									$metrics['total'][$status_code] = 0;
								}
								$metrics['total'][$status_code] += $qty;
							}
						}
						$percent = getPercentage($mm[$key]['overall']['executed'], $mm[$key]['overall']['active']);
						if (strtotime($t_plan_authors[$key][0]['creation_ts']) < strtotime('-3 day')) {
							if ($percent < 35) {
								$plans2notify['needs_more_attention'][] = $key;
							} else if ($percent < 50) {
								$plans2notify['needs_attention'][] = $key;
							}
						}
					}
					$metrics['total']['percent']['total'] = getPercentage($metrics['total']['passed'] + $metrics['total']['failed'] + $metrics['total']['blocked'], $metrics['total']['active'], 2);
					$metrics['total']['percent']['not_run'] = getPercentage($metrics['total']['not_run'], $metrics['total']['active'], 2);
					$metrics['total']['percent']['passed'] = getPercentage($metrics['total']['passed'], $metrics['total']['active'], 2);
					$metrics['total']['percent']['failed'] = getPercentage($metrics['total']['failed'], $metrics['total']['active'], 2);
					$metrics['total']['percent']['blocked'] = getPercentage($metrics['total']['blocked'], $metrics['total']['active'], 2);
					$notify = $plans2notify['needs_more_attention'] + $plans2notify['needs_attention'];
					foreach ($notify as $tplan_id) {
						$message = 'Hi ,<br /><br />';
						$message .= $release_info['name'] . ' Progress Report<br />';
						$message .= getSingleProgressBar("Overall Progress", $metrics['total']['percent']['total'], 450);
						$message .= getSingleProgressBar("Not Run", $metrics['total']['percent']['not_run'], 450);
						$message .= getSingleProgressBar("Passed", $metrics['total']['percent']['passed'], 450);
						$message .= getSingleProgressBar("Failed", $metrics['total']['percent']['failed'], 450);
						$message .= getSingleProgressBar("Blocked", $metrics['total']['percent']['blocked'], 450);
						$message .= '<br />Following Test Plans requires your attention<br />';
						$message .= '<table style="width: 550px;" border="1"><tr><th>Name</th><th>Active Cases</th><th>Not Run</th><th>Passed</th><th>Failed</th><th>Blocked</th><th>Total</th></tr>';

						$message .= '<tr><td><a href="http://' . $_SERVER['SERVER_NAME'] . '/testlink/lib/results/resultByTestPlan.php?tplanID=' . $tplan_id . '&tprojectID=' . $tproject_info['id'] . '&guestToken=' . $guestToken . '">' . $test_plans[$tplan_id]['name'] . '</a></td>';
						$message .= '<td>' . $metrics['testplans'][$tplan_id]['overall']['active'] . '</td>';
						$message .= '<td>' . $metrics['testplans'][$tplan_id]['overall']['not_run'] . '</td>';
						$message .= '<td>' . ((isset($metrics['testplans'][$tplan_id]['overall']['passed']) and !is_null($metrics['testplans'][$tplan_id]['overall']['passed'])) ? $metrics['testplans'][$tplan_id]['overall']['passed'] : 0) . '</td>';
						$message .= '<td>' . ((isset($metrics['testplans'][$tplan_id]['overall']['failed']) and !is_null($metrics['testplans'][$tplan_id]['overall']['failed']) ? $metrics['testplans'][$tplan_id]['overall']['failed'] : 0)) . '</td>';
						$message .= '<td>' . ((isset($metrics['testplans'][$tplan_id]['overall']['blocked']) and !is_null($metrics['testplans'][$tplan_id]['overall']['blocked']) ? $metrics['testplans'][$tplan_id]['overall']['blocked'] : 0)) . '</td>';
						$message .= '<td>' . getPercentage($metrics['testplans'][$tplan_id]['overall']['executed'], $metrics['testplans'][$tplan_id]['overall']['active'], 2) . '%</td></tr>';

						$message .= '</table><br /><br />';
						$message .= 'Regards,<br />TestLink Team';
						$recipient = '';
						foreach ($plan_authors[$tplan_id] as $uid) {
							if ($recipient != '') {
								$recipient .= ',';
							}
							$recipient .= $users_list[$uid] -> emailAddress;
						}
						//$recipient = 'rsakhale@saba.com';
						$subject = 'TestPlan Execution Reminder - ' . date("d-M-Y");
						email_send($g_from_email, $recipient, $subject, $message);
					}
				}
			}

		}

	}

	/**
	 *	Get Percentage of a value
	 *	@param number $denominator
	 * 	@param number $numeator
	 * 	@param number $round_precision - default is 2
	 */
	function getPercentage($denominator, $numerator, $round_precision = 2) {
		$percentage = ($numerator > 0) ? (round(($denominator / $numerator) * 100, $round_precision)) : 0;

		return $percentage;
	}

	/**
	 *	List of known build formats
	 *
	 * 	@return array of known build formats
	 */
	function knownBuildFormat() {
		return array(
			"hotfix sys int" => "Hotfix Sys Int {number}",
			"hotfix int dev" => "Hotfix Int Dev {number}",
			"mr sys int" => "MR Sys Int {number}",
			"mr int dev" => "MR Int Dev {number}",
			"qr sys int" => "Sys Int Dev {number}",
			"qr apps dev" => "QR Apps Dev {number}",
			"qr int dev" => "QR Int Dev {number}",
			"si" => "QR Sys Int {number}",
			"banksy" => "Banksy Int Dev {number}",
			"int dev" => "MR Int Dev {number}",
			"apps" => "QR Apps Dev {number}",
			"sys" => "MR Sys Int {number}",
			"di" => "DI {number}",
			"frame" => "Framework {number}",
			"mssql" => "MSSQL-DB2 {number}",
			"patch" => "Patches {number}",
			"ptc" => "PTC {number}",
			"sm" => "SM{number}",
			"dev" => "DEV{number}",
			"webrtc" => "WebRTC {number}",
			"android" => "Android Int Dev {number}",
			"ios" => "iOS Int Dev {number}",
			"build" => "Build {number}"
		);
	}

	/**
	 * 	Get number from string, regex used here
	 *
	 * 	@param $str from which number needs to be grabbed
	 * 	@return number if found else empty string
	 */
	function getNumber($str) {
		$matches = array();
		preg_match('/\d+/', $str, $matches);
		if (count($matches) > 0) {
			return $matches[0];
		}
		return "";
	}

	/**
	 *	Identify Build from the user input string and format into well-known Build String
	 * 	Internally takes help of knownBuildFormat to generate proper build name
	 *
	 * 	@param $str user inputed build name, usually taken in consideration during Excel imports
	 */
	function identifyBuildNumber($str) {
		$availableBuilds = knownBuildFormat();
		$str = trim(str_replace("#", "", $str));
		$buildno = getNumber($str);
		foreach ($availableBuilds as $buildKey => $buildString) {
			if (contains($buildKey, strtolower($str))) {
				return str_replace("{number}", $buildno, $buildString);
			}
		}
		return $str;
	}

	/**
	 *	Method like in Java to identify if the string is contained in the other string
	 *
	 * @param $substring
	 * 				to be checked in other string
	 * @param $string
	 * 				that would be used to identify existence of other string
	 */
	function contains($substring, $string) {
		$pos = strpos($string, $substring);
		return !($pos === false);
	}

	/**
	 *	Login's in the session with the username provided
	 *
	 * 	@param &$db
	 connection reference object
	 * 	@param $username
	 * 				who should be used to loginAs
	 */
	function loginAs(&$db, $username) {
		require_once 'users.inc.php';
		$user = new tlUser();
		$user -> login = $username;
		$login_exists = ($user -> readFromDB($db, tlUser::USER_O_SEARCH_BYLOGIN) >= tl::OK);
		$auth_cookie_name = config_get('auth_cookie');
		$expireOnBrowserClose = false;
		setcookie($auth_cookie_name, $user -> getSecurityCookie(), $expireOnBrowserClose, '/');
		// Setting user's session information
		$_SESSION['currentUser'] = $user;
		$_SESSION['lastActivity'] = time();
		global $g_tlLogger;
		$g_tlLogger -> endTransaction();
		$g_tlLogger -> startTransaction();
		setUserSession($db, $user -> login, $user -> dbID, $user -> globalRoleID, $user -> emailAddress, $user -> locale, null);
		$result['status'] = tl::OK;
		logAuditEvent(TLS("audit_login_succeeded", $user -> login, $_SERVER['REMOTE_ADDR']), "LOGIN", $_SESSION['currentUser'] -> dbID, "users");
	}

	/**
	 *	Move Obselete marked Cases from a suite to another suite for review
	 * 	This method also maintains the folder hierarchy that the cases were in previously
	 * 	@param	&$db
	 * 				connection reference object
	 * 	@param	$tproject_id
	 * 				is the Test Project id whose cases need to be moved
	 *	@param	$tsuite_id
	 * 				is the destination parent suite folder for cases
	 * 	@param	$keyword
	 * 				is the keyword considered for cases to be moved
	 */
	function moveObsoleteCasesToObseleteSuiteHierarchy(&$db, $tproject_id = null, $tsuite_id, $keyword) {
		$tables = tlObjectWithDB::getDBTables();
		$tsuite_mgr = new testsuite($db);
		$tcase_mgr = new sabatestcase($db);
		$tree_manager = new tree($db);
		if (is_null($tproject_id)) {
			$tproject_id = $_SESSION['testprojectID'];
		}
		$obsoleteCases = $tcase_mgr -> getCasesByKeyword($tproject_id, $keyword, $tsuite_id);
		foreach ($obsoleteCases as $obsoleteCase) {
			$_tcase_id = $obsoleteCase['tcase_id'];
			$_tsuite_id = $obsoleteCase['tsuite_id'];
			$path = $tree_manager -> get_path($_tsuite_id);
			$obsoleteTSuiteId = null;
			$obsoleteTree = $tsuite_mgr -> get_subtree($tsuite_id, true);
			$temp_obsolete_tree = $obsoleteTree['childNodes'];
			$obsoleteParent = $tsuite_id;
			foreach ($path as $node) {
				$found = false;
				foreach ($temp_obsolete_tree as $temp_obsolete_node) {
					if ($temp_obsolete_node['name'] == $node['name']) {
						$obsoleteTSuiteId = $temp_obsolete_node['id'];
						$obsoleteParent = $temp_obsolete_node['id'];
						$temp_obsolete_tree = $temp_obsolete_node['childNodes'];
						$found = true;
						break;
					}
				}
				if (!$found) {
					$obsoleteTSuiteId = $tsuite_mgr -> create($obsoleteParent, $node['name']);
					$obsoleteTSuiteId = $obsoleteTSuiteId['id'];
					$obsoleteParent = $obsoleteTSuiteId;
					$temp_obsolete_tree = $tsuite_mgr -> get_subtree($obsoleteTSuiteId);
					$temp_obsolete_tree = $temp_obsolete_tree['childNodes'];
				}
			}

			$query = "UPDATE {$tables['nodes_hierarchy']} SET `parent_id` = $obsoleteTSuiteId WHERE `id` = $_tcase_id";
			$db -> exec_query($query);
		}
	}

	/**
	 *	Delete Cases From a Suite which are not linked to any plan
	 * 	Often this method should be run by admin only
	 *  Purpose for cleaning unwanted cases who are orphan with plans
	 *
	 * @param &$db
	 * 			database connection reference object
	 * @param $tsuite_id
	 * 			Suite containing cases not linked to any plan
	 * @param $recursive
	 * 			If true, will also check underlying suites
	 */
	function deleteCasesFromSuiteNotLinkedToPlan(&$db, $tsuite_id, $recursive = false) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		$tables = tlDBObject::getDBTables();
		if ($recursive) {
			$tsuite_mgr = new testsuite($db);
			$list = $tsuite_mgr -> get_branch($tsuite_id);
			if (!is_null($list) and $list != '') {
				$tsuite_id = $tsuite_id . ",$list";
			}
		}
		$query = "/* $debugMsg */ SELECT NH.id as `id`
FROM {$tables['nodes_hierarchy']} NH
JOIN {$tables['nodes_hierarchy']} NHTS ON NHTS.id = NH.parent_id AND NHTS.node_type_id = 2 AND NH.node_type_id = 3 
JOIN {$tables['nodes_hierarchy']} NHTV ON NHTV.parent_id = NH.id AND NHTV.node_type_id = 4
LEFT JOIN {$tables['testplan_tcversions']} TTCV ON TTCV.tcversion_id = NHTV.id
WHERE TTCV.id IS NULL
AND NHTS.id IN ($tsuite_id)";
		$result = $db -> get_recordset($query);
		foreach ($result as $row) {
			$tcase_mgr -> delete($row['id']);
		}
	}

	/**
	 *	Fix TestPlan Type based on the data found in name
	 * 		If plan name contains
	 * 			'Regression' => Type is Regression
	 * 			'Locale' => Type is Locale
	 * 			'CFR' => Type is CFRPart11
	 * 	@param &$db
	 * 				database connection reference object
	 * 	@param $tproject_id
	 * 				testproject id whose TestPlan Type should be fixed
	 *
	 */
	function fixTestPlanTypeBasedOnName(&$db, $tproject_id) {
		$debugMsg = 'Method: ' . __FUNCTION__;
		$tables = tlDBObject::getDBTables();
		$tproject_mgr = new testproject($db);
		$cf_map = $tproject_mgr -> get_linked_custom_fields($tproject_id);
		$testplanType = null;
		foreach ($cf_map as $cf) {
			if ($cf['name'] === 'TestPlanType') {
				$testplanType = $cf;
			}
		}
		if (!is_null($testplanType)) {
			$sql = "/* $debugMsg */ SELECT NHV.name AS plan_name, RTL.testplan_id AS testplan_id
		FROM {$tables['release_testplans']} RTL
		JOIN {$tables['releases']} RL ON RL.id = RTL.release_id AND RL.testproject_id = $tproject_id
		JOIN {$tables['nodes_hierarchy']} NHV ON NHV.id = RTL.testplan_id AND NHV.node_type_id = 5";
			$records = $db -> get_recordset($sql);
			foreach ($records as $value) {
				$sql = "SELECT CDV.*
			FROM {$tables['testplans']} TP
			JOIN {$tables['cfield_design_values']} CDV ON TP.id = CDV.node_id AND CDV.field_id = " . $testplanType['id'] . "
			WHERE TP.id = " . $value['testplan_id'];

				$r = $db -> get_recordset($sql);
				$testplanTypeValue = 'Story';
				if (strpos(strtolower($value['plan_name']), 'regression') !== false) {
					$testplanTypeValue = 'Regression';
				} else if (strpos(strtolower($value['plan_name']), 'regression') !== false) {
					$testplanTypeValue = 'Locale';
				}
				if (!is_null($r) and is_array($r) and count($r) > 0) {
					$sql = "UPDATE {$tables['cfield_design_values']} SET value = '" . $db -> prepare_string($testplanTypeValue) . "'
					WHERE node_id = {$r['node_id']} AND field_id = {$r['field_id']}";
					$db -> exec_query($sql);
					echo "Updated<br />";
				} else {
					$sql = "INSERT INTO {$tables['cfield_design_values']} (field_id, node_id, value) VALUES (" . $testplanType['id'] . "," . $value['testplan_id'] . ",'" . $db -> prepare_string($testplanTypeValue) . "')";
					$db -> exec_query($sql);
					echo "Inserted<br />";
				}
			}
		}
	}

	/**
	 *	Fix the Summary based on Title which had exceeded in length
	 *	This removes Warning message typed in summary for old cases
	 *
	 * 	@param &$db
	 * 				database connection reference object
	 */
	function fixTitleSummaryExceededLength(&$db) {
		$tproject_id = $_SESSION['testprojectID'];
		$tcase_mgr = new sabatestcase($db);
		$tcases_list = $tcase_mgr -> get_all();
		foreach ($tcases_list as $tcase) {
			$tcaseInfo = $tcase_mgr -> get_by_id($tcase['id']);
			$tcaseInfo = $tcaseInfo[0];
			$tcaseInfo['name'] = str_replace("&quot;", "\"", $tcaseInfo['name']);
			if (substr_count($tcaseInfo['summary'], $tcaseInfo['name']) > 1 and strpos($tcaseInfo['summary'], '---- Warning ----') !== false) {
				if (strpos($tcaseInfo['summary'], "---- Warning ----") !== false) {
					$tcaseInfo['summary'] = trim(preg_replace("/---- Warning ----(.*)----\n/s", "", $tcaseInfo['summary']));
				}
				//$tcase_mgr -> update($tcaseInfo['testcase_id'], $tcaseInfo['id'], $tcaseInfo['name'], $tcaseInfo['summary'], $tcaseInfo['preconditions'], null, 96);

			}
		}
	}
	/**
	 *	Get the associated bug with test case 
	 *	DG
	 *
	 * 	@param $tplanId, $tcversionId, $platformId)
	 * 				
	 */
	
	function getBugsAssociated($db,$tplanId, $tcversionId, $platformId)
	{
		
		$sql="SELECT bug_id from execution_bugs where execution_id 
		in (SELECT id FROM  executions WHERE  testplan_id =$tplanId AND tcversion_id =$tcversionId AND platform_id =$platformId)";
		$result = $db -> get_recordset($sql);
		
		return $result;
		//return($result[0]['bug_id']);

	}
     /**
	 *	Get the status of the  bug id and check whether it's valid to execute.
	 *	DG
	 *
	 * 	@param $status,$id,&$allow_execute,$db,$tplanId, $tcversionId, $platformId,$bug,&$error_code
	 * 				
	 */
	function validtoexecute($status,$id,&$allow_execute,$db,$tplanId, $tcversionId, $platformId,$bug,&$error_code)
	{
	
			 if (($status== 'p' and $id !== '6' )) { 
				$allow_execute = false;
				$error_code= array (
					'status' => 'NOT_OK',
					'msg' => $error_msg . 'To mark test case passed it\'s associted bug '. $bug.' status should be CLOSED' );
					  
			 }
			 else if ((in_array($status, array('f','b'))) and $id == '6' ) {
			 	
				//below sql check where prior execution status backlog then skip to set $allow_execute
			 $sql="SELECT status FROM  executions WHERE  testplan_id =$tplanId AND tcversion_id =$tcversionId AND platform_id =$platformId ORDER BY `executions`.`execution_ts` DESC
				   LIMIT 1";
		      $result = $db -> get_recordset($sql);
			
					  $allow_execute = false;
					  $error_code=  array(
							'status' => 'NOT_OK',
							'msg' => $error_msg . 'To mark test case failed it\'s associted bug '. $bug .' status should NOT be CLOSED' );
				
				if($result[0]['status'] =='b')
			     $allow_execute = true;
			}
		
	}
	 
	/**
	 *	Get Single Progress Bar returns div block depending on the params passed
	 *
	 * 	@param	$title
	 * 				Title the div block should display
	 * 	@param	$progress
	 * 				Progress out of 100% to be displayed
	 * 	@param	$progress_max_width = 200 (default in px)
	 * 				Progress bar div block width
	 * 	@param	$backcolor = #eee (default to light grey)<br >
	 * 				Color can be anything that works with HTML format<br >
	 * 				Hex code should start with '#'<br >
	 * 				Verbal colors like red, gree, blue would work<br >
	 * 				RGB color should be rgb(255,255,0)<br >
	 *
	 */
	function getSingleProgressBar($title, $progress, $progress_max_width = 200, $backcolor = '#eee', $backbordercolor = '#ccc', $progresscolor = '#888', $progressbordercolor = '#888') {
		return '<div>
<span style="width: ' . $progress_max_width . 'px;border: 1px solid ' . $backbordercolor . ';height: 25px;display: inline-block;background: ' . $backcolor . '">
<span style="position: absolute;padding-top:2px;padding-left: 10px;color: #000;text-shadow: 0 0 1px #fff;font-weight: bold">' . $title . ' ' . $progress . '%</span>
<span style="width: ' . $progress . '%;border: 1px solid ' . $progressbordercolor . ';height: 23px;display:inline-block;background: ' . $progresscolor . ';"></span>
</span>
</div>';

	}

	/**
	 *
	 */
	function cleanVal($str) {
		$filter = array(
			"&" => '&amp;',
			'—' => '-',
			'–' => '-',
			'`' => '',
			'‘' => "'",
			'’' => "'",
			'"' => '&quot;',
			"'" => '',
			"“" => "'",
			"”" => "'",
			"  " => ' ',
			"!" => '',
			"<" => '&lt;',
			">" => '&gt;'
		);

		foreach ($filter as $k => $v) {
			$str = str_replace($k, $v, $str);
		}
		$str = trim($str);
		//reject overly long 2 byte sequences,
		//as well as characters above U+10000
		//and replace with ?
		$str = preg_replace('/' . '[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' . '|[\x00-\x7F][\x80-\xBF]+' . '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' . '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' . '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|' . '(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})' . '/S', '?', $str);
		//reject overly long 3 byte sequences
		//and UTF-16 surrogates and replace with ?
		$str = preg_replace('/' . '\xE0[\x80-\x9F][\x80-\xBF]' . '|\xED[\xA0-\xBF][\x80-\xBF]' . '/S', '?', $str);
		return $str;
	}
?>