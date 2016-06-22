<?php
	/**
	 * TestLink Open Source Project - http://testlink.sourceforge.net/
	 * This script is distributed under the GNU General Public License 2 or later.
	 *
	 * @filesource 	testcase.class.php
	 * @package 	TestLink
	 * @author 		Francisco Mancardi (francisco.mancardi@gmail.com)
	 * @copyright 	2005-2011, TestLink community
	 * @link 		http://www.teamst.org/index.php
	 *
	 * @internal revisions
	 * @since 1.9.4
	 * 20120831 - franciscom - TICKET 5133: Test cases - possibility to have step expected result reuse,
	 * as exists for step action
	 * 20120822 - franciscom - TICKET 5159: importing duplicate test suites
	 * 20120819 - franciscom - TICKET 4937: Test Cases EXTERNAL ID is Auto genarated, no matter is
	 * provided on XML
	 *						   create() changed
	 *
	 * 20111106 - franciscom - TICKET 4797: Test case step reuse - renderGhostSteps()
	 * 20110817 - franciscom - TICKET 4708: When adding testcases to test plan, filtering by execution
	 * type does not work.
	 *
	 */

	/** related functionality */
	require_once (dirname(__FILE__) . '/requirement_mgr.class.php');
	require_once (dirname(__FILE__) . '/assignment_mgr.class.php');
	require_once (dirname(__FILE__) . '/attachments.inc.php');
	require_once (dirname(__FILE__) . '/users.inc.php');
	require_once (dirname(__FILE__) . '/testcase.class.php');

	/** list of supported format for Test case import/export */
	$g_tcFormatStrings = array("XML" => lang_get('the_format_tc_xml_import'));

	/**
	 * class for Test case CRUD
	 * @package 	TestLink
	 */
	class sabatestcase extends testcase {

		/**
		 * testplan class constructor
		 *
		 * @param resource &$db reference to database handler
		 */
		function __construct(&$db) {
			parent::__construct($db);
		}

		/**
		 *
		 */
		public function getAuthorByTCVersionId($tcversion_id) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
			$query = "/* $debugMsg */ SELECT author_id from {$this->tables['tcversions']} WHERE `id` = $tcversion_id";
			$result = $this -> db -> get_recordset($query);
			if ($result) {
				return $result[0]['author_id'];
			}
			return 0;
		}

		/**
		 *
		 */
		public function getCasesByAutomatedSuiteName($suiteName, $releaseId, $tprojectId) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
			$query = "/* $debugMsg */ SELECT TCV.`id` as tcversion_id, NHV.`parent_id` as tcase_id, NHVC.`name` as tcase_name,
			 TTCV.`testplan_id` as tplan_id, CDV.`value` as automationSuiteName, TTCV.`author_id` as tester_id,
			 TCV.`version` as tcase_version_number, TTCV.`platform_id` as platform_id
			 FROM {$this->tables['tcversions']} TCV
			 JOIN {$this->tables['testplan_tcversions']} TTCV ON TCV.id = TTCV.tcversion_id
			 JOIN {$this->tables['release_testplans']} RT ON RT.testplan_id = TTCV.testplan_id
			 JOIN {$this->tables['nodes_hierarchy']} NHV ON TCV.id = NHV.id
			 JOIN {$this->tables['nodes_hierarchy']} NHVC ON NHV.parent_id = NHVC.id
			 JOIN {$this->tables['cfield_design_values']} CDV ON CDV.node_id = TCV.id
			 JOIN {$this->tables['testplans']} TP ON TP.id = TTCV.testplan_id
			 WHERE TCV.execution_type = 2 AND CDV.`field_id` = 1
			 AND CDV.`value` = '$suiteName'
			 AND RT.release_id = $releaseId
			 AND TP.testproject_id = $tprojectId";
			//echo $query;die();
			return $this -> db -> fetchRowsIntoMap($query, 'tcversion_id', 'platform_id');
		}

		/**
		 *	@param $tproject_id
		 * 	@param $keyword - by which keyword we want the cases
		 * 	@param $tsuite_id - incase any suite to be ignored
		 */
		function getCasesByKeyword($tproject_id, $keyword, $tsuite_id = null) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
			$ignore_suites = null;
			if (!is_null($tsuite_id)) {
				$tsuite_mgr = new testsuite($this -> db);
				$suite_subtree = $tsuite_mgr -> get_subtree($tsuite_id, TRUE);
				$ignore_suites = array($tsuite_id);
				$this -> getChildNodesArray($suite_subtree, $ignore_suites);
			}

			$query = "/* $debugMsg */ SELECT NH.name AS tcase_name, NH.id AS tcase_id, KW.keyword as keyword, KW.id as keyword_id, NHS.id as tsuite_id, NHS.name as tsuite_name
			FROM {$this->tables['nodes_hierarchy']} NH
			JOIN {$this->tables['nodes_hierarchy']} NHS ON NHS.id = NH.parent_id
			JOIN {$this->tables['testcase_keywords']} TKW ON TKW.testcase_id = NH.id AND NH.node_type_id = 3
			JOIN {$this->tables['keywords']} KW ON TKW.keyword_id = KW.id AND KW.testproject_id = $tproject_id
			WHERE KW.keyword = '$keyword'";
			if (!is_null($ignore_suites)) {
				$query .= " AND NHS.id NOT IN (" . implode(",", $ignore_suites) . ")";
			}
			return $this -> db -> get_recordset($query);
		}

		/**
		 *
		 */
		function getChildNodesArray($node, &$id_list) {
			if (isset($node['childNodes']) and is_array($node['childNodes']) and count($node['childNodes']) > 0) {
				foreach ($node['childNodes'] as $child) {
					if (isset($child['node_type_id']) and $child['node_type_id'] == '2') {
						$id_list[] = $child['id'];
						if (isset($child['childNodes'])) {
							$this -> getChildNodesArray($child, $id_list);
						}
					}
				}
			}
		}

		/**
		 *
		 */

		public function getLastExecution($testplan_id, $tcversion_id, $platform_id = null, $build_id = null) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
			$query = "/* $debugMsg */ SELECT LE.tcversion_id as tcversion_id, LE.testplan_id as testplan_id, LE.platform_id as platform_id,
			P.name as platform_name, B.name as build_name, LE.build_id as build_id,
			  LE.id as execution_id, E.status as status, U.login as uname,
			E.execution_type as execution_type, E.execution_ts, E.notes as notes
			FROM {$this->tables['testplan_tcversions']} TTCV
			JOIN (
				SELECT  `tcversion_id` AS  `tcversion_id` ,  `testplan_id` AS  `testplan_id` ,  `platform_id` AS  `platform_id` , MAX(  `id` ) AS  `id` 
				FROM {$this->tables['executions']} WHERE testplan_id =$testplan_id AND tcversion_id = $tcversion_id GROUP BY  `tcversion_id` ,  `testplan_id` ,  `platform_id` 
			) E ON TTCV.testplan_id = E.testplan_id AND TTCV.tcversion_id = E.tcversion_id AND TTCV.testplan_id = $testplan_id
			JOIN {$this->tables['platforms']} P ON E.platform_id = P.id	
			JOIN {$this->tables['builds']} B ON B.id = E.build_id
			JOIN {$this->tables['users']} U ON U.id = E.tester_id
			 WHERE LE.`tcversion_id` = $tcversion_id";
			if (!is_null($platform_id)) {
				if (is_array($platform_id)) {
					$query .= " AND LE.`platform_id` IN (" . implode(",", $platform_id) . ")";
				} else {
					$query .= " AND LE.`platform_id` = $platform_id";
				}
			}
			if (!is_null($build_id)) {
				$query .= " AND LE.`build_id` = $build_id";
			}
			if (!is_null($testplan_id)) {
				$query .= " AND LE.`testplan_id` = $testplan_id";
			}
			$query .= " ORDER BY E.execution_ts ASC";
			$result = $this -> db -> get_recordset($query);
			var_dump($result);
			$resultArr = array();
			if (!is_null($result)) {
				foreach ($result as $resultInfo) {
					$resultArr[$resultInfo['tcversion_id'] . '_' . $resultInfo['platform_id']] = $resultInfo;
				}
			}
			return $resultArr;
		}

		/**
		 * Show Test Case logic
		 *
		 * @param object $smarty reference to smarty object (controls viewer).
		 * @param integer $id Test case unique identifier
		 * @param integer $version_id (optional) you can work on ONE test case version,
		 * 				or on ALL; default: ALL
		 *
		 * @internal

		 [viewer_args]: map with keys
		 action
		 msg_result
		 refresh_tree: controls if tree view is refreshed after every operation.
		 default: yes
		 user_feedback
		 disable_edit: used to overwrite user rights
		 default: 0 -> no

		 returns:

		 rev :
		 20090215 - franciscom - added info about links to test plans

		 20081114 - franciscom -
		 added arguments and options that are useful when this method is
		 used to display test case search results.
		 path_info: map: key: testcase id
		 value: array with path to test case, where:
		 element 0 -> test project name
		 other elements test suites name

		 new options on viewer_args: hilite_testcase_name,show_match_count

		 20070930 - franciscom - REQ - BUGID 1078
		 added disable_edit argument

		 */
		function show(&$smarty, $guiObj, $template_dir, $id, $version_id = self::ALL_VERSIONS, $viewer_args = null, $path_info = null, $mode = null, &$its = null) {
			$status_ok = 1;
			$gui = is_null($guiObj) ? new stdClass() : $guiObj;
			$gui -> parentTestSuiteName = '';
			$gui -> path_info = $path_info;
			$gui -> tprojectName = '';
			$gui -> linked_versions = null;
			$gui -> tc_current_version = array();
			$gui -> bodyOnLoad = "";
			$gui -> bodyOnUnload = "storeWindowSize('TCEditPopup')";
			$gui -> submitCode = "";
			$gui -> dialogName = '';
			$gui -> platforms = null;
			$gui -> tableColspan = 5;
			$gui -> linked_jiras = null;
			// sorry magic related to table to display steps
			$gui -> opt_requirements = false;
			$gui -> its = $its;
			$its_mgr = new jiraIntegration($this -> db, $its);

			$gui_cfg = config_get('gui');
			$the_tpl = config_get('tpl');
			$my_template = isset($the_tpl['tcView']) ? $the_tpl['tcView'] : 'tcView.tpl';

			$tcase_cfg = config_get('testcase_cfg');

			$tc_other_versions = array();
			$status_quo_map = array();
			$keywords_map = array();
			$userid_array = array();

			// 20090718 - franciscom
			$cf_smarty = null;
			$formatOptions = null;
			$cfPlaces = $this -> buildCFLocationMap();
			if (!is_null($mode) && $mode == 'editOnExec') {
				// refers to two javascript functions present in testlink_library.js
				// and logic used to refresh both frames when user call this
				// method to edit a test case while executing it.
				$gui -> dialogName = 'tcview_dialog';
				$gui -> bodyOnLoad = "dialog_onLoad($gui->dialogName)";
				$gui -> bodyOnUnload = "dialog_onUnload($gui->dialogName)";
				$gui -> submitCode = "return dialog_onSubmit($gui->dialogName)";
			}

			$viewer_defaults = array(
				'title' => lang_get('title_test_case'),
				'show_title' => 'no',
				'action' => '',
				'msg_result' => '',
				'user_feedback' => '',
				'refreshTree' => 1,
				'disable_edit' => 0,
				'display_testproject' => 0,
				'display_parent_testsuite' => 0,
				'hilite_testcase_name' => 0,
				'show_match_count' => 0
			);

			if (!is_null($viewer_args) && is_array($viewer_args)) {
				foreach ($viewer_defaults as $key => $value) {
					if (isset($viewer_args[$key])) {
						$viewer_defaults[$key] = $viewer_args[$key];
					}
				}
			}

			$gui -> show_title = $viewer_defaults['show_title'];
			$gui -> display_testcase_path = !is_null($path_info);
			$gui -> hilite_testcase_name = $viewer_defaults['hilite_testcase_name'];
			$gui -> pageTitle = $viewer_defaults['title'];
			$gui -> show_match_count = $viewer_defaults['show_match_count'];
			if ($gui -> show_match_count && $gui -> display_testcase_path) {
				$gui -> match_count = count($path_info);
			}

			if ($viewer_defaults['disable_edit'] == 1 || has_rights($this -> db, "mgt_modify_tc") == false) {
				$mode = 'editDisabled';
			}
			$gui -> show_mode = $mode;
			$gui -> can_do = $this -> getShowViewerActions($mode);

			if (is_array($id)) {
				$a_id = $id;
			} else {
				$status_ok = $id > 0 ? 1 : 0;
				$a_id = array($id);
			}
			if ($status_ok) {
				$path2root = $this -> tree_manager -> get_path($a_id[0]);
				$tproject_id = $path2root[0]['parent_id'];
				$info = $this -> tproject_mgr -> get_by_id($tproject_id);

				$platformMgr = new tlPlatform($this -> db, $tproject_id);
				$gui -> platforms = $platformMgr -> getAllAsMap();

				$testplans = $this -> tproject_mgr -> get_all_testplans($tproject_id, array('plan_status' => 1));
				$gui -> has_testplans = !is_null($testplans) && count($testplans) > 0 ? 1 : 0;

				if ($viewer_defaults['display_testproject']) {
					$gui -> tprojectName = $info['name'];
				}

				if ($viewer_defaults['display_parent_testsuite']) {
					$parent_idx = count($path2root) - 2;
					$gui -> parentTestSuiteName = $path2root[$parent_idx]['name'];
				}

				$tcasePrefix = $this -> tproject_mgr -> getTestCasePrefix($tproject_id);
				if (trim($tcasePrefix) != "") {
					// Add To Testplan button will be disabled if the testcase doesn't belong to the current selected
					// testproject
					// $gui->can_do->add2tplan = 'no';
					//DG: Adding and condition ,when test case is deactive no able to add test case to test plan.
					if ($_SESSION['testprojectPrefix'] == $tcasePrefix and $viewer_args['action']!='deactivate_this_version' ) {
						$gui -> can_do -> add2tplan = $gui -> can_do -> add2tplan == 'yes' ? has_rights($this -> db, "testplan_planning") : 'no';
					} else {
						$gui -> can_do -> add2tplan = 'no';
					}

					$tcasePrefix .= $tcase_cfg -> glue_character;
				}
			}
			$tc_other_versions_linked_jiras = null;
			if ($status_ok && sizeof($a_id)) {
				$cfx = 0;
				$cf_current_version = null;
				$cf_other_versions = null;
				$allTCKeywords = $this -> getKeywords($a_id, null, 'testcase_id', ' ORDER BY keyword ASC ');
				foreach ($a_id as $key => $tc_id) {
					$tc_array = $this -> get_by_id($tc_id, $version_id);

					if (!$tc_array) {
						continue;
					}

					$tc_array[0]['tc_external_id'] = $tcasePrefix . $tc_array[0]['tc_external_id'];

					// get the status quo of execution and links of tc versions
					$status_quo_map[] = $this -> get_versions_status_quo($tc_id);

					$gui -> linked_versions[] = $this -> get_linked_versions($tc_id);
					// Jira Linked Versions
					$linked_jiras = $its_mgr -> get_linked_jiras($tc_id, $tc_array[0]['id']);

					if ($linked_jiras) {
						$gui -> linked_jiras[$tc_array[0]['id']] = $linked_jiras;
					}

					$keywords_map[] = isset($allTCKeywords[$tc_id]) ? $allTCKeywords[$tc_id] : null;
					$tc_current = $tc_array[0];
					$tcversion_id_current = $tc_array[0]['id'];
					$gui -> tc_current_version[] = array($tc_current);

					//Get UserID and Updater ID for current Version
					$userid_array[$tc_current['author_id']] = null;
					$userid_array[$tc_current['updater_id']] = null;

					foreach ($cfPlaces as $locationKey => $locationFilter) {
						$cf_current_version[$cfx][$locationKey] = $this -> html_table_of_custom_field_values($tc_id, 'design', $locationFilter, null, null, $tproject_id, null, $tcversion_id_current);
					}

					// Other versions (if exists)
					if (count($tc_array) > 1) {
						$tc_other_versions[] = array_slice($tc_array, 1);
						//$tc_other_versions_linked_jiras[]
						$target_idx = count($tc_other_versions) - 1;
						$loop2do = count($tc_other_versions[$target_idx]);
						for ($qdx = 0; $qdx < $loop2do; $qdx++) {
							$linked_jiras = $its_mgr -> get_linked_jiras($tc_other_versions[$target_idx][$qdx]['testcase_id'], $tc_other_versions[$target_idx][$qdx]['id']);
							if ($linked_jiras) {
								$gui -> linked_jiras[$tc_other_versions[$target_idx][$qdx]['id']] = $linked_jiras;
							}
							$target_tcversion = $tc_other_versions[$target_idx][$qdx]['id'];
							foreach ($cfPlaces as $locationKey => $locationFilter) {
								$cf_other_versions[$cfx][$qdx][$locationKey] = $this -> html_table_of_custom_field_values($tc_id, 'design', $locationFilter, null, null, $tproject_id, null, $target_tcversion);
							}
						}
					} else {
						$tc_other_versions[] = null;
						$cf_other_versions[$cfx] = null;
					}
					$cfx++;
					// Get author and updater id for each version
					if ($tc_other_versions[0]) {
						foreach ($tc_other_versions[0] as $key => $version) {
							$userid_array[$version['author_id']] = null;
							$userid_array[$version['updater_id']] = null;
						}
					}

				} // foreach($a_id as $key => $tc_id)
			}
			$testplans_ids = array();
			foreach ($gui -> linked_versions as $v) {
				if (is_null($v)) {
					break;
				}
				foreach ($v as $tc) {
					foreach ($tc as $v_i) {
						foreach ($v_i as $p) {
							if (isset($p['testplan_id'])) {

								$testplans_ids[] = $p['testplan_id'];
							}
						}
					}
				}
			}
			if (!is_null($tproject_id) and $tproject_id != '') {
				$release_mgr = new release($this -> db, $tproject_id);
				$gui -> jira_plans = $release_mgr -> get_jira_linked_for_plans($testplans_ids, true);
			}

			// if (sizeof($a_id))
			// var_dump($gui -> linked _versions[0][154994][154992][0]);die();
			// Removing duplicate and NULL id's
			unset($userid_array['']);
			$passeduserarray = array_keys($userid_array);

			$gui -> cf = null;
			$gui -> cf_current_version = $cf_current_version;
			$gui -> cf_other_versions = $cf_other_versions;
			$gui -> refreshTree = isset($gui -> refreshTree) ? $gui -> refreshTree : $viewer_defaults['refreshTree'];
			$gui -> sqlResult = $viewer_defaults['msg_result'];
			$gui -> action = $viewer_defaults['action'];
			$gui -> user_feedback = $viewer_defaults['user_feedback'];
			$gui -> execution_types = $this -> execution_types;
			$gui -> tcase_cfg = $tcase_cfg;
			$gui -> users = tlUser::getByIDs($this -> db, $passeduserarray, 'id');
			$gui -> status_quo = $status_quo_map;
			$gui -> testcase_other_versions = $tc_other_versions;
			$gui -> view_req_rights = has_rights($this -> db, "mgt_view_req");
			$gui -> canEdit = has_rights($this -> db, "mgt_modify_tc");
			$gui -> keywords_map = $keywords_map;
			$smarty -> assign('gui', $gui);
			$smarty -> display($template_dir . $my_template);
		}

		/*

		 rev:
		 20100107 - franciscom - Multiple Test Case Step Feature
		 20081015 - franciscom - added check to avoid bug due to no children

		 */
		function delete($id, $version_id = self::ALL_VERSIONS) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
			$children = null;
			$do_it = true;

			// I'm trying to speedup the next deletes
			$sql = "/* $debugMsg */ SELECT NH_TCV.id AS tcversion_id, NH_TCSTEPS.id AS step_id 
			FROM {$this->tables['nodes_hierarchy']} NH_TCV 
			LEFT OUTER JOIN {$this->tables['nodes_hierarchy']} NH_TCSTEPS 
			ON NH_TCSTEPS.parent_id = NH_TCV.id ";

			if ($version_id == self::ALL_VERSIONS) {
				if (is_array($id)) {
					$sql .= " WHERE NH_TCV.parent_id IN (" . implode(',', $id) . ") ";
				} else {
					$sql .= " WHERE NH_TCV.parent_id={$id} ";
				}
			} else {
				$sql .= " WHERE NH_TCV.parent_id={$id} AND NH_TCV.id = {$version_id}";
			}

			$children_rs = $this -> db -> get_recordset($sql);
			$do_it = !is_null($children_rs);

			// Delete linked Jiras with Test Case
			// Ignore Version
			// Rohan Sakhale

			$it_mgr = null;
			$tcase = $this -> get_by_id($id);
			$tprojectID = $this -> getTestProjectFromTestCase($id, $tcase[0]['testsuite_id']);
			unset($tcase);
			$info = $this -> tproject_mgr -> get_by_id($tprojectID);

			if ($info['issue_tracker_enabled']) {
				$it_mgr = new tlIssueTracker($this -> db);
				$it_mgr = new jiraIntegration($this -> db, $it_mgr);
			}
			if ($it_mgr != null) {
				if ($version_id == self::ALL_VERSIONS) {
					if (is_array($id)) {
						foreach ($id as $i) {
							$it_mgr -> delete_linked_jira($i, null, null, $tprojectID);
						}
					} else {
						$it_mgr -> delete_linked_jira($id, null, null, $tprojectID);
					}
				} else {
					$it_mgr -> delete_linked_jira($id, $version_id, null, $tprojectID);
				}
			}

			if ($do_it) {
				foreach ($children_rs as $value) {
					$children['tcversion'][] = $value['tcversion_id'];
					$children['step'][] = $value['step_id'];
				}
				$this -> _execution_delete($id, $version_id, $children);
				$this -> _blind_delete($id, $version_id, $children);
			}

			return 1;
		}

		/**
		 *	Overriding base class method to add additional filters to capture Cases assigned to a user
		 * 	RSakhale
		 */
		function get_assigned_to_user($user_id, $tproject_id, $tplan_id = null, $options = null, $filters = null) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;

			$my['filters'] = array(
				'tplan_status' => 'all',
				'release_id' => $_SESSION['releaseID']
			);
			$my['filters'] = array_merge($my['filters'], (array)$filters);

			// to load assignments for all users OR one given user
			$user_sql = ($user_id != TL_USER_ANYBODY) ? " AND UA.user_id = {$user_id} " : "";

			$filters = "";

			$has_options = !is_null($options);
			$access_key = array(
				'testplan_id',
				'testcase_id'
			);

			$sql = "/* $debugMsg */ SELECT TPROJ.id as testproject_id,TPTCV.testplan_id,TPTCV.tcversion_id, 
TCV.version,TCV.tc_external_id, NHTC.id AS testcase_id, NHTC.name, TPROJ.prefix, 
UA.creation_ts ,UA.deadline_ts, UA.user_id as user_id, 
COALESCE(PLAT.name,'') AS platform_name, COALESCE(PLAT.id,0) AS platform_id, 
(TPTCV.urgency * TCV.importance) AS priority, BUILDS.name as build_name, 
BUILDS.id as build_id 
FROM {$this->tables['user_assignments']} UA 
JOIN {$this->tables['testplan_tcversions']} TPTCV ON TPTCV.id = UA.feature_id 
JOIN {$this->tables['tcversions']} TCV ON TCV.id=TPTCV.tcversion_id 
JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.id = TCV.id 
JOIN {$this->tables['nodes_hierarchy']} NHTC ON NHTC.id = NHTCV.parent_id 
JOIN {$this->tables['nodes_hierarchy']} NHTPLAN ON  NHTPLAN.id=TPTCV.testplan_id 
JOIN {$this->tables['testprojects']} TPROJ ON  TPROJ.id = NHTPLAN.parent_id 
JOIN {$this->tables['testplans']} TPLAN ON  TPLAN.id = TPTCV.testplan_id 
JOIN {$this->tables['release_testplans']} RT ON RT.testplan_id = TPLAN.id ";
			$sql .= " JOIN {$this->tables['builds']} BUILDS ON  BUILDS.id = UA.build_id
LEFT OUTER JOIN {$this->tables['platforms']} PLAT ON  PLAT.id = TPTCV.platform_id 
WHERE UA.type={$this->assignment_types['testcase_execution']['id']} " .
			//" AND UA.user_id = {$user_id} " .
			" {$user_sql} " . " AND TPROJ.id IN (" . implode(',', array($tproject_id)) . ") ";

			if (!is_null($tplan_id)) {
				$filters .= " AND TPTCV.testplan_id IN (" . implode(',', $tplan_id) . ") ";
			}

			// BUGID 3647
			if (isset($my['filters']['build_id'])) {
				$filters .= " AND UA.build_id = {$my['filters']['build_id']} ";
			}

			switch($my['filters']['tplan_status']) {
				case 'all' :
					break;

				case 'active' :
					$filters .= " AND TPLAN.active = 1 ";
					break;

				case 'inactive' :
					$filters .= " AND TPLAN.active = 0 ";
					break;
			}

			// BUGID 3749
			if (isset($my['filters']['build_status'])) {
				switch($my['filters']['build_status']) {
					case 'open' :
						$filters .= " AND BUILDS.is_open = 1 ";
						break;

					case 'closed' :
						$filters .= " AND BUILDS.is_open = 0 ";
						break;

					case 'all' :
					default :
						break;
				}
			}
			if (isset($my['filters']['release_id'])) {
				$filters .= " AND RT.release_id = " . $my['filters']['release_id'] . " ";
			}

			$sql .= $filters;

			if ($has_options && isset($options -> access_keys)) {
				switch($options->access_keys) {
					case 'testplan_testcase' :
						break;

					case 'testcase_testplan' :
						$access_key = array(
							'testcase_id',
							'testplan_id'
						);
						break;
				}
			}

			//echo $sql ."<br />";
			$rs = $this -> db -> fetchMapRowsIntoMap($sql, $access_key[0], $access_key[1], database::CUMULATIVE);
			if ($has_options && !is_null($rs)) {
				if (isset($options -> mode)) {
					switch($options->mode) {
						case 'full_path' :
							if (!isset($options -> access_keys) || (is_null($options -> access_keys) || $options -> access_keys = 'testplan_testcase')) {
								$tcaseSet = null;
								$main_keys = array_keys($rs);
								foreach ($main_keys as $maccess_key) {
									$sec_keys = array_keys($rs[$maccess_key]);
									foreach ($sec_keys as $saccess_key) {
										// is enough I process first element
										$item = $rs[$maccess_key][$saccess_key][0];
										if (!isset($tcaseSet[$item['testcase_id']])) {
											$tcaseSet[$item['testcase_id']] = $item['testcase_id'];
										}
									}
								}

								$path_info = $this -> tree_manager -> get_full_path_verbose($tcaseSet);

								// Remove test project piece and convert to string
								$flat_path = null;
								foreach ($path_info as $tcase_id => $pieces) {
									unset($pieces[0]);
									// 20100813 - asimon - deactivated last slash on path
									// to remove it from test suite name in "tc assigned to user" tables
									$flat_path[$tcase_id] = implode('/', $pieces);
								}
								$main_keys = array_keys($rs);

								foreach ($main_keys as $idx) {
									$sec_keys = array_keys($rs[$idx]);
									foreach ($sec_keys as $jdx) {
										$third_keys = array_keys($rs[$idx][$jdx]);
										foreach ($third_keys as $tdx) {
											$fdx = $rs[$idx][$jdx][$tdx]['testcase_id'];
											$rs[$idx][$jdx][$tdx]['tcase_full_path'] = $flat_path[$fdx];
										}
									}
								}
							}
							break;
					}
				}
			}
			return $rs;
		}

		/*
		 function: exportTestCaseDataToXML

		 args :

		 $tcversion_id: can be testcase::LATEST_VERSION

		 returns:

		 rev:
		 20101009	 - franciscom - better checks on $optExport
		 20101009 - franciscom - BUGID 3868: Importing exported XML results - custom fields have unexpected
		 NEW LINES
		 20100926 - franciscom - manage tcase_id not present, to allow export using
		 tcversion id as target

		 20100908 - franciscom - testcase::LATEST_VERSION has problems
		 20100315 - amitkhullar - Added options for Requirements and CFields for Export.
		 20100105 - franciscom - added execution_type, importance
		 20090204 - franciscom - added export of node_order
		 20080206 - franciscom - added externalid

		 */
		function exportTestCaseDataToXML($tcase_id, $tcversion_id, $tproject_id = null, $bNoXMLHeader = false, $optExport = array(), $it_mgr = null) {
			static $reqMgr;
			static $keywordMgr;
			static $cfieldMgr;

			if (is_null($reqMgr)) {
				$reqMgr = new requirement_mgr($this -> db);
				$keywordMgr = new tlKeyword();
				$cfieldMgr = new cfield_mgr($this -> db);
			}

			// Useful when you need to get info but do not have tcase id
			$tcase_id = intval((int)($tcase_id));
			$tcversion_id = intval((int)($tcversion_id));
			if ($tcase_id <= 0 && $tcversion_id > 0) {
				$info = $this -> tree_manager -> get_node_hierarchy_info($tcversion_id);
				$tcase_id = $info['parent_id'];
			}

			$tc_data = $this -> get_by_id($tcase_id, $tcversion_id);
			$testCaseVersionID = $tc_data[0]['id'];

			if (!$tproject_id) {
				$tproject_id = $this -> getTestProjectFromTestCase($tcase_id);
			}

			// Get Export XML String for Linked Jiras
			$linked_jiras = null;
			if (isset($optExport['JIRAREQUIREMENT']) && $optExport['JIRAREQUIREMENT']) {
				if ($it_mgr != null) {
					$linked_jiras = $it_mgr -> get_xml_export_linked_jiras($tcase_id, $testCaseVersionID);
				}			}			// Get Custom Field Data
			if (isset($optExport['CFIELDS']) && $optExport['CFIELDS']) {
				// BUGID 3431
				$cfMap = $this -> get_linked_cfields_at_design($tcase_id, $testCaseVersionID, null, null, $tproject_id);

				// ||yyy||-> tags,  {{xxx}} -> attribute
				// tags and attributes receive different treatment on exportDataToXML()
				//
				// each UPPER CASE word in this map KEY, MUST HAVE AN OCCURENCE on $elemTpl
				// value is a key inside $tc_data[0]
				//
				if (!is_null($cfMap) && count($cfMap) > 0) {
					// BUGID 3868
					// $cfRootElem = "<custom_fields>{{XMLCODE}}</custom_fields>";
					// $cfElemTemplate = "\t" . "<custom_field>\n" .
					//                   "\t<name><![CDATA[||NAME||]]></name>\n" .
					//                   "\t<value><![CDATA[||VALUE||]]></value>\n</custom_field>\n";
					// $cfDecode = array ("||NAME||" => "name","||VALUE||" => "value");
					// $tc_data[0]['xmlcustomfields'] =
					// $cfieldMgr->exportDataToXML($cfMap,$cfRootElem,$cfElemTemplate,$cfDecode,true);
					$tc_data[0]['xmlcustomfields'] = $cfieldMgr -> exportValueAsXML($cfMap);
				}
			}

			// Get Keywords
			if (isset($optExport['KEYWORDS']) && $optExport['KEYWORDS']) {
				$keywords = $this -> getKeywords($tcase_id);
				if (!is_null($keywords)) {
					$xmlKW = "<keywords>" . $keywordMgr -> toXMLString($keywords, true) . "</keywords>";
					$tc_data[0]['xmlkeywords'] = $xmlKW;
				}
			}

			// Get Requirements
			if (isset($optExport['REQS']) && $optExport['REQS']) {
				$requirements = $reqMgr -> get_all_for_tcase($tcase_id);
				if (!is_null($requirements) && count($requirements) > 0) {
					$reqRootElem = "\t<requirements>\n{{XMLCODE}}\t</requirements>\n";
					$reqElemTemplate = "\t\t<requirement>\n" . "\t\t\t<req_spec_title><![CDATA[||REQ_SPEC_TITLE||]]></req_spec_title>\n" . "\t\t\t<doc_id><![CDATA[||REQ_DOC_ID||]]></doc_id>\n" . "\t\t\t<title><![CDATA[||REQ_TITLE||]]></title>\n" . "\t\t</requirement>\n";

					$reqDecode = array(
						"||REQ_SPEC_TITLE||" => "req_spec_title",
						"||REQ_DOC_ID||" => "req_doc_id",
						"||REQ_TITLE||" => "title"
					);
					$tc_data[0]['xmlrequirements'] = exportDataToXML($requirements, $reqRootElem, $reqElemTemplate, $reqDecode, true);
				}
			}
			// ------------------------------------------------------------------------------------
			// BUGID 3695 - missing execution_type
			// Multiple Test Case Steps Feature
			$stepRootElem = "<steps>{{XMLCODE}}</steps>";
			$stepTemplate = "\n" . '<step>' . "\n" . "\t<step_number><![CDATA[||STEP_NUMBER||]]></step_number>\n" . "\t<actions><![CDATA[||ACTIONS||]]></actions>\n" . "\t<expectedresults><![CDATA[||EXPECTEDRESULTS||]]></expectedresults>\n" . "\t<execution_type><![CDATA[||EXECUTIONTYPE||]]></execution_type>\n" . "</step>\n";
			$stepInfo = array(
				"||STEP_NUMBER||" => "step_number",
				"||ACTIONS||" => "actions",
				"||EXPECTEDRESULTS||" => "expected_results",
				"||EXECUTIONTYPE||" => "execution_type"
			);

			$stepSet = $tc_data[0]['steps'];
			$xmlsteps = exportDataToXML($stepSet, $stepRootElem, $stepTemplate, $stepInfo, true);
			$tc_data[0]['xmlsteps'] = $xmlsteps;
			// ------------------------------------------------------------------------------------

			$rootElem = "{{XMLCODE}}";
			if (isset($optExport['ROOTELEM'])) {
				$rootElem = $optExport['ROOTELEM'];
			}
			$elemTpl = "\n" . '<testcase internalid="{{TESTCASE_ID}}" name="{{NAME}}">' . "\n" . "\t<node_order><![CDATA[||NODE_ORDER||]]></node_order>\n" . "\t<externalid><![CDATA[||EXTERNALID||]]></externalid>\n" . "\t<version><![CDATA[||VERSION||]]></version>\n" . "\t<summary><![CDATA[||SUMMARY||]]></summary>\n" . "\t<preconditions><![CDATA[||PRECONDITIONS||]]></preconditions>\n" . "\t<execution_type><![CDATA[||EXECUTIONTYPE||]]></execution_type>\n" . "\t<importance><![CDATA[||IMPORTANCE||]]></importance>\n" . (($linked_jiras != null) ? $linked_jiras : '') . "||STEPS||\n" . "||KEYWORDS||||CUSTOMFIELDS||||REQUIREMENTS||</testcase>\n";

			// ||yyy||-> tags,  {{xxx}} -> attribute
			// tags and attributes receive different treatment on exportDataToXML()
			//
			// each UPPER CASE word in this map KEY, MUST HAVE AN OCCURENCE on $elemTpl
			// value is a key inside $tc_data[0]
			//
			$info = array(
				"{{TESTCASE_ID}}" => "testcase_id",
				"{{NAME}}" => "name",
				"||NODE_ORDER||" => "node_order",
				"||EXTERNALID||" => "tc_external_id",
				"||VERSION||" => "version",
				"||SUMMARY||" => "summary",
				"||PRECONDITIONS||" => "preconditions",
				"||EXECUTIONTYPE||" => "execution_type",
				"||IMPORTANCE||" => "importance",
				"||STEPS||" => "xmlsteps",
				"||KEYWORDS||" => "xmlkeywords",
				"||CUSTOMFIELDS||" => "xmlcustomfields",
				"||REQUIREMENTS||" => "xmlrequirements"
			);

			$xmlTC = exportDataToXML($tc_data, $rootElem, $elemTpl, $info, $bNoXMLHeader);
			return $xmlTC;
		}

		/*
		 * function: linkedWithTestplan
		 * Get all testcases linked with test plan and under a test suite

		 function linkedWithTestplanNSuite($tplan_id, $tsuite_id, $opt = array()) {
		 $fields2get = "TPROJ.id as testproject_id,TPTCV.testplan_id,TPTCV.tcversion_id as tcversion_id,
		 TCV.version as version, TCV.tc_external_id, NHTC.id AS testcase_id, NHTC.name as name, TPROJ.prefix,
		 TCV.summary as summary, TCV.preconditions as preconditions, TCV.importance  as importance, TCV.execution_type as execution_type,
		 COALESCE(PLAT.name,'') AS platform_name, COALESCE(PLAT.id,0) AS platform_id, E.status, E.platform_id execution_platform_id,
		 E.build_id, BI.name as build_name, E.id AS execution_id, EU.login as tester, E.notes as notes";

		 $sql = "SELECT NH.parent_id AS id, TCV.id AS tcversion_id ,TCV.version AS version_number,
		 TCV.tc_external_id AS ext_id,NHV.name AS name, TCV.summary AS summary
		 FROM {$this->tables['testplan_tcversions']} TPTCV
		 JOIN {$this->tables['release_testplans']} RTP ON TPTCV.testplan_id = RTP.testplan_id AND RTP.testplan_id = $tplan_id
		 JOIN {$this->tables['releases']} RL ON RL.id = RTP.release_id
		 JOIN {$this->tables['nodes_hierarchy']} TPROJ ON TPROJ.id = RL.testproject_id
		 JOIN {$this->tables['tcversions']} TCV ON TPTCV.tcversion_id = TCV.id
		 JOIN {$this->tables['nodes_hierarchy']} NH ON NH.id = TCV.id
		 JOIN {$this->tables['nodes_hierarchy']} NHV ON NHV.id = NH.parent_id
		 WHERE NHV.parent_id  = $tsuite_id";

		 return $this -> db -> fetchRowsIntoMap($sql, 'id');
		 }
		 * Commented out as using getTestCasesLinkedToTestPlan to fetch Suite Level Cases for a Plan
		 */

		/*
		 * function:
		 */
		function getTestCasesLinkedToPlanExecBugs($tplan_id, $cfields = null) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
			$fields2get = "TS.id AS suite_id, NH.parent_id AS testcase_id, E.id AS execution_id, 
TCV.tc_external_id AS external_id, TCV.importance AS importance, TCV.execution_type AS execution_type, TT.tcversion_id AS tcversion_id, 
NHT.name AS name, E.status AS status, BI.name AS build_name, PT.name AS platform, E.notes AS notes, 
TT.testplan_id AS testplan_id, TCJ.jira_id AS jira_id, JI.component AS component, E.execution_ts AS execution_ts, E.tester_id AS tester_id";
			if (!is_null($cfields -> productArea)) {
				$fields2get .= ", PA.value AS product_area";
			}
			if (!is_null($cfields -> automationSuiteName)) {
				$fields2get .= ", ASN.value AS automationSuiteName";
			}
			if (!is_null($cfields -> automationStatus)) {
				$fields2get .= ", AST.value AS automationStatus";
			}
			if (!is_null($cfields -> testingType)) {
				$fields2get .= ", TET.value AS testingType";
			}
			if (!is_null($cfields -> testingPriority)) {
				$fields2get .= ", TPY.value AS testingPriority";
			}
			if (!is_null($cfields -> sprint)) {
				$fields2get .= ", SP.value AS sprint";
			}
			if (!is_null($cfields -> components)) {
				$fields2get .= ", CO.value AS component";
			}
			$sql = "/* $debugMsg */ SELECT $fields2get
FROM {$this->tables['testplan_tcversions']} TT
JOIN {$this->tables['tcversions']} TCV ON TCV.id = TT.tcversion_id  AND TT.testplan_id = $tplan_id
JOIN {$this->tables['nodes_hierarchy']} NH ON NH.id = TT.tcversion_id
JOIN {$this->tables['nodes_hierarchy']} NHT ON NHT.id = NH.parent_id
JOIN {$this->tables['nodes_hierarchy']} TS ON TS.id = NHT.parent_id";
			if (!is_null($cfields -> productArea)) {
				$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} PA ON PA.node_id = TT.testplan_id AND PA.field_id = " . $cfields -> productArea['id'];
			}
			if (!is_null($cfields -> automationSuiteName)) {
				$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} ASN ON ASN.node_id = TCV.id AND ASN.field_id = " . $cfields -> automationSuiteName['id'];
			}
			if (!is_null($cfields -> automationStatus)) {
				$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} AST ON AST.node_id = TCV.id AND AST.field_id = " . $cfields -> automationStatus['id'];
			}
			if (!is_null($cfields -> testingType)) {
				$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} TET ON TET.node_id = TCV.id AND TET.field_id = " . $cfields -> testingType['id'];
			}
			if (!is_null($cfields -> testingPriority)) {
				$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} TPY ON TPY.node_id = TCV.id AND TPY.field_id = " . $cfields -> testingPriority['id'];
			}
			if (!is_null($cfields -> sprint)) {
				$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} SP ON SP.node_id = TT.testplan_id AND SP.field_id = " . $cfields -> sprint['id'];
			}
			if (!is_null($cfields -> components)) {
				$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} CO ON CO.node_id = TCV.id AND CO.field_id = " . $cfields -> components['id'];
			}
			$sql .= " LEFT JOIN (
	SELECT  `tcversion_id` AS  `tcversion_id` ,  `testplan_id` AS  `testplan_id` ,  `platform_id` AS  `platform_id` , MAX(  `id` ) AS  `id` 
	FROM {$this->tables['executions']} WHERE testplan_id =$tplan_id GROUP BY  `tcversion_id` ,  `testplan_id` ,  `platform_id` 
		) ES ON ES.tcversion_id = TT.tcversion_id AND ES.platform_id = TT.platform_id
LEFT JOIN {$this->tables['executions']} E ON E.id = ES.id
LEFT JOIN {$this->tables['tcjira']} TCJ ON TCJ.tcversion = TT.tcversion_id
LEFT JOIN {$this->tables['jiraissues']} JI ON JI.id = TCJ.jira_id
LEFT JOIN {$this->tables['platforms']} PT ON PT.id = TT.platform_id
LEFT JOIN {$this->tables['builds']} BI ON BI.id = E.build_id";
			
			$result = $this -> db -> get_recordset($sql);
			
			$resultArr = array();
			if (!is_null($result) and is_array($result)) {
				foreach ($result as $row) {
					if ($row['status'] == 'n') {
						$row['status'] = null;
					}
					$resultArr[$row['tcversion_id'] . '_' . $row['platform']] = $row;
				}
			}
			return $resultArr;
		}

		/**
		 *
		 */
		function getInfoByExecutionId($exec_id) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
			$sql = "/* $debugMsg */ SELECT NHTP.name as testplan_name, BI.name as build_name, PL.name as platform_name, NHTC.name as testcase_name,
			NHTC.id as tcase_id, TCV.id as tcversion_id, E.status as status, E.build_id as build_id, E.platform_id as platform_id,
			E.execution_ts, TCV.creation_ts, TCV.summary as tcase_summary, JI.id as jira_id, JI.summary as jira_summary , 
			JI.component as jira_component, JI.fixVersionID as jira_fixversion_id, JI.fixVersionName as jira_fixversion_name
			FROM {$this->tables['executions']} E
			JOIN {$this->tables['nodes_hierarchy']} NHTP ON NHTP.id = E.testplan_id
			JOIN {$this->tables['tcversions']} TCV ON TCV.id = E.tcversion_id			
			JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.id = TCV.id
			JOIN {$this->tables['nodes_hierarchy']} NHTC ON NHTC.id = NHTCV.parent_id
			LEFT JOIN {$this->tables['tcjira']} TCJ ON TCJ.tcversion  = TCV.id
			LEFT JOIN {$this->tables['jiraissues']} JI ON JI.id = TCJ.jira_id 
			JOIN {$this->tables['builds']} BI ON BI.id = E.build_id
			LEFT JOIN {$this->tables['platforms']} PL ON PL.id = E.platform_id
			WHERE E.id = $exec_id";
			return $this -> db -> get_recordset($sql);
		}

		/**
		 *
		 */
		function getTestCasesLinkedToSuite($suite_id, $cfields = null, $opt = array()) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
			if (!isset($opt['projectPrefix'])) {
				$opt['projectPrefix'] = $_SESSION['testprojectPrefix'];
			}
			$fields2get = "NHC.id AS id, NH.id AS parent_id, NHC.node_type_id AS node_type_id,NHC.node_order AS node_order, '{$this->tables['tcversions']}' AS node_table, NHC.name AS name,
			CONCAT('{$opt['projectPrefix']}-',TCV.tc_external_id) AS external_id, TCV.version AS version, TCV.tc_external_id AS tc_external_id";
			if (!is_null($cfields)) {
				if (property_exists($cfields, 'automationSuiteName') and !is_null($cfields -> automationSuiteName)) {
					$fields2get .= ", ASN.value as automationSuiteName";
				}
				if (property_exists($cfields, 'automationStatus') and !is_null($cfields -> automationStatus)) {
					$fields2get .= ", AST.value as automationStatus";
				}
				if (property_exists($cfields, 'testingType') and !is_null($cfields -> testingType)) {
					$fields2get .= ", TET.value as testingType";
				}
				if (property_exists($cfields, 'testingPriority') and !is_null($cfields -> testingPriority)) {
					$fields2get .= ", TPY.value as testingPriority";
				}
				if (property_exists($cfields, 'cfr') and !is_null($cfields -> cfr)) {
					$fields2get .= ", CFR.value as cfr";
				}
			}
			if (isset($opt['keywords']) and !is_null($opt['keywords'])) {
				$fields2get .= ", KW.keyword as keyword";
			}
			$sql = "/* $debugMsg */ SELECT $fields2get
			FROM {$this->tables['nodes_hierarchy']} NH
			JOIN {$this->tables['nodes_hierarchy']} NHC ON NHC.parent_id = NH.id AND NHC.node_type_id = 3
			JOIN {$this->tables['nodes_hierarchy']} NHV ON NHV.parent_id = NHC.id
			JOIN {$this->tables['tcversions']} TCV ON TCV.id = NHV.id";
			if (!is_null($cfields)) {
				if (property_exists($cfields, 'automationSuiteName') and !is_null($cfields -> automationSuiteName)) {
					$cfields -> automationSuiteName['tableName'] = 'ASN';
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} ASN ON ASN.node_id = TCV.id AND ASN.field_id = " . $cfields -> automationSuiteName['id'];
				}
				if (property_exists($cfields, 'automationStatus') and !is_null($cfields -> automationStatus)) {
					$cfields -> automationStatus['tableName'] = 'AST';
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} AST ON AST.node_id = TCV.id AND AST.field_id = " . $cfields -> automationStatus['id'];
				}
				if (property_exists($cfields, 'testingType') and !is_null($cfields -> testingType)) {
					$cfields -> testingType['tableName'] = 'TET';
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} TET ON TET.node_id = TCV.id AND TET.field_id = " . $cfields -> testingType['id'];
				}
				if (property_exists($cfields, 'testingPriority') and !is_null($cfields -> testingPriority)) {
					$cfields -> testingPriority['tableName'] = 'TPY';
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} TPY ON TPY.node_id = TCV.id AND TPY.field_id = " . $cfields -> testingPriority['id'];
				}
				if (property_exists($cfields, 'cfr') and !is_null($cfields -> cfr)) {
					$cfields -> cfr['tableName'] = 'CFR';
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} CFR ON CFR.node_id = TCV.id AND CFR.field_id = " . $cfields -> cfr['id'];
				}
			}
			if (isset($opt['keywords']) and !is_null($opt['keywords'])) {
				$sql .= " LEFT JOIN {$this->tables['testcase_keywords']} TTK ON TTK.testcase_id  = NHC.id
				LEFT JOIN {$this->tables['keywords']} KW ON KW.id = TTK.keyword_id";
			}
			$sql .= " WHERE NH.id = $suite_id";
			if (isset($opt['keywords']) and !is_null($opt['keywords'])) {
				if (is_array($opt['keywords'])) {
					if (isset($opt['keywords_type']) and !is_null($opt['keywords_type'])) {
						$keywordWhere = '';
						foreach ($opt['keywords'] as $kw) {
							if ($keywordWhere != '') {
								$keywordWhere .= " " . strtoupper($opt['keywords_type']) . " ";
							}
							$keywordWhere .= "KW.id = $kw";
						}
						$sql .= " AND $keywordWhere";
					} else {
						$sql .= " AND KW.id IN (" . implode(",", $opt['keywords']) . ")";
					}
				} else {
					$sql .= " AND KW.id = " . $opt['keywords'];
				}
			}
			if (isset($opt['custom_fields']) and is_array($opt['custom_fields'])) {
				foreach ($opt['custom_fields'] as $custom_field_id => $custom_field_value) {
					foreach ($cfields as $cfieldKey => $cfieldValue) {
						if (property_exists($cfields, $cfieldKey) and !is_null($cfieldValue) and count($cfieldValue) > 0 and $cfieldValue['id'] == $custom_field_id and isset($cfieldValue['tableName'])) {
							$sql .= " AND ";
							if (is_array($custom_field_value)) {
								$arraySql = '';
								foreach ($custom_field_value as $v) {
									if ($arraySql != '') {
										$arraySql .= ' AND ';
									}
									$sql .= $cfieldValue['tableName'] . ".value LIKE '%$v%'";
								}
								$sql .= $arraySql;

							} else {
								$sql .= $cfieldValue['tableName'] . ".value = '$custom_field_value'";
							}
						}
					}
				}
			}
			return $this -> db -> get_recordset($sql);
		}

		/**
		 *
		 */
		function getBugsByTestCaseID($tcversion_id, $tplanId = null) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
			$query = "/* $debugMsg */ SELECT EB.bug_id as bug_id, JI.status as status FROM `{$this->tables['execution_bugs']}` EB
				JOIN `{$this->tables['executions']}` E ON EB.execution_id = E.id
				JOIN `{$this->tables['jiraissues']}` JI ON EB.bug_id = JI.id
				WHERE E.tcversion_id = " . $tcversion_id;
			if (!is_null($tplanId) and $tplanId != '') {
				$query .= " AND E.testplan_id = " . $tplanId;
			}
			$query .= " GROUP BY EB.bug_id";
			//echo $query;die();
			return $this -> db -> get_recordset($query);
		}

		/**
		 *
		 */
		function getTestCasesLinkedToTestPlan($tplan_id, $cfields = null, $opt = array()) {
			$debugMsg = 'Class:' . __CLASS__ . ' - Method: ' . __FUNCTION__;
			$fields2get = "TPROJ.id as testproject_id,TPTCV.testplan_id,TPTCV.tcversion_id as tcversion_id, 
	          TCV.version as version, TCV.tc_external_id, NHTC.id AS testcase_id, NHTC.name as name, TPROJ.prefix, 
	          TCV.summary as summary, TCV.preconditions as preconditions, TCV.importance  as importance, TCV.execution_type as execution_type,        
	          COALESCE(PLAT.name,'') AS platform_name, COALESCE(PLAT.id,0) AS platform_id, E.status, E.platform_id execution_platform_id, 
	          E.build_id, BI.name as build_name, E.id AS execution_id, EU.login as tester, E.notes as notes";
			if (!is_null($cfields)) {
				if (property_exists($cfields, 'productArea') and !is_null($cfields -> productArea)) {
					$fields2get .= ", PA.value as product_area";
				}
				if (property_exists($cfields, 'automationSuiteName') and !is_null($cfields -> automationSuiteName)) {
					$fields2get .= ", ASN.value as automationSuiteName";
				}
				if (property_exists($cfields, 'automationStatus') and !is_null($cfields -> automationStatus)) {
					$fields2get .= ", AST.value as automationStatus";
				}
				if (property_exists($cfields, 'testingType') and !is_null($cfields -> testingType)) {
					$fields2get .= ", TET.value as testingType";
				}
				if (property_exists($cfields, 'testingPriority') and !is_null($cfields -> testingPriority)) {
					$fields2get .= ", TPY.value as testingPriority";
				}
				if (property_exists($cfields, 'sprint') and !is_null($cfields -> sprint)) {
					$fields2get .= ", SP.value as sprint";
				}
			}
			if (isset($opt['tsuiteId']) and !is_null($opt['tsuiteId']) and $opt['tsuiteId'] != '') {
				$fields2get .= ", TS.name as suite_name";
			}

			$sql = "/* $debugMsg */ SELECT  $fields2get 
	          FROM {$this->tables['testplan_tcversions']} TPTCV
	          JOIN {$this->tables['tcversions']} TCV ON TCV.id=TPTCV.tcversion_id";
			if (!is_null($cfields)) {
				if (property_exists($cfields, 'productArea') and !is_null($cfields -> productArea)) {
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} PA ON PA.node_id = TPTCV.testplan_id AND PA.field_id = " . $cfields -> productArea['id'];
				}
				if (property_exists($cfields, 'automationSuiteName') and !is_null($cfields -> automationSuiteName)) {
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} ASN ON ASN.node_id = TCV.id AND ASN.field_id = " . $cfields -> automationSuiteName['id'];
				}
				if (property_exists($cfields, 'automationStatus') and !is_null($cfields -> automationStatus)) {
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} AST ON AST.node_id = TCV.id AND AST.field_id = " . $cfields -> automationStatus['id'];
				}
				if (property_exists($cfields, 'testingType') and !is_null($cfields -> testingType)) {
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} TET ON TET.node_id = TCV.id AND TET.field_id = " . $cfields -> testingType['id'];
				}
				if (property_exists($cfields, 'testingPriority') and !is_null($cfields -> testingPriority)) {
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} TPY ON TPY.node_id = TCV.id AND TPY.field_id = " . $cfields -> testingPriority['id'];
				}
				if (property_exists($cfields, 'sprint') and !is_null($cfields -> sprint)) {
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} SP ON SP.node_id = TPTCV.testplan_id AND SP.field_id = " . $cfields -> sprint['id'];
				}
				if (property_exists($cfields, 'cfr') and !is_null($cfields -> cfr)) {
					$sql .= " LEFT JOIN {$this->tables['cfield_design_values']} CFR ON CFR.node_id = TCV.id AND CFR.field_id = " . $cfields -> cfr['id'];
				}
			}
			$sql .= " JOIN {$this->tables['nodes_hierarchy']} NHTCV ON NHTCV.id = TCV.id 
	          JOIN {$this->tables['nodes_hierarchy']} NHTC ON NHTC.id = NHTCV.parent_id";
			if (isset($opt['tsuiteId']) and !is_null($opt['tsuiteId']) and $opt['tsuiteId'] != '') {
				$sql .= " JOIN {$this->tables['nodes_hierarchy']} TS ON TS.id = NHTC.parent_id AND TS.id = " . $opt['tsuiteId'];
			}
			if (isset($opt['userId']) and !is_null($opt['userId']) and $opt['userId'] != '') {
				$sql .= " JOIN {$this->tables['user_assignments']} UA ON UA.feature_id = TPTCV.id AND UA.user_id = " . $opt['userId'];
			}
			$sql .= " JOIN {$this->tables['nodes_hierarchy']} NHTPLAN ON  NHTPLAN.id=TPTCV.testplan_id 
	          JOIN {$this->tables['testprojects']} TPROJ ON  TPROJ.id = NHTPLAN.parent_id 
	          JOIN {$this->tables['testplans']} TPLAN ON  TPLAN.id = TPTCV.testplan_id AND TPLAN.id = $tplan_id
	          LEFT JOIN (
					SELECT  `tcversion_id` AS  `tcversion_id` ,  `testplan_id` AS  `testplan_id` ,  `platform_id` AS  `platform_id` , MAX(  `id` ) AS  `id`, `notes` AS `notes` 
					FROM {$this->tables['executions']} WHERE testplan_id =$tplan_id GROUP BY  `tcversion_id` ,  `testplan_id` ,  `platform_id` 
				) ES ON ES.tcversion_id = TPTCV.tcversion_id AND ES.platform_id = TPTCV.platform_id
				LEFT JOIN {$this->tables['executions']} E ON E.id = ES.id
				LEFT JOIN {$this->tables['users']} EU ON EU.id = E.tester_id
				LEFT JOIN {$this->tables['platforms']} PLAT ON  PLAT.id = TPTCV.platform_id 
				LEFT JOIN {$this->tables['builds']} BI ON BI.id = E.build_id";

			if (isset($opt['has_bugs']) and !is_null($opt['has_bugs']) and $opt['has_bugs'] !== '0' and in_array($opt['has_bugs'], array(
				false,
				true
			))) {
				if ($opt['has_bugs'] == false) {
					$sql .= " LEFT ";
				}
				$sql .= " JOIN {$this->tables['execution_bugs']} EB ON E.id = EB.execution_id";
				if (isset($opt['bugs_state']) and !is_null($opt['bugs_state']) and $opt['bugs_state'] != '') {
					if ($opt['has_bugs'] == false) {
						$sql .= " LEFT ";
					}
					$sql .= " JOIN {$this->tables['jiraissues']} JI ON EB.bug_id = JI.id AND JI.status = " . $this -> db -> prepare_int($opt['bugs_state']);
				}
			}
			$where = array();
			if (isset($opt['platform_filter']) and !is_null($opt['platform_filter']) and $opt['platform_filter'] != '') {
				$where[] = "TPTCV.platform_id = " . $this -> db -> prepare_int($opt['platform_filter']);
			}
			if (isset($opt['execution_type']) and !is_null($opt['execution_type']) and $opt['execution_type'] != '' and $opt['execution_type'] != 0) {
				$where[] = "TCV.execution_type = " . $this -> db -> prepare_int($opt['execution_type']);
			}
			if (isset($opt['automation_suite_name']) and !is_null($opt['automation_suite_name']) and $opt['automation_suite_name'] != '' and property_exists($cfields, 'automationSuiteName') and !is_null($cfields -> automationSuiteName)) {
				$where[] = "ASN.value LIKE '%" . $this -> db -> prepare_string($opt['automation_suite_name']) . "%'";
			}

			if (isset($opt['has_bugs']) and !is_null($opt['has_bugs']) and $opt['has_bugs'] !== '0' and $opt['has_bugs'] == false) {
				$where[] = "EB.bug_id IS NULL";
			}
			if (isset($opt['last_execution']) and !is_null($opt['last_execution']) and $opt['last_execution'] != '') {
				if (in_array($opt['last_execution'], array(
					"p",
					"f",
					"b"
				))) {
					$where[] = "E.status = '" . $this -> db -> prepare_string($opt['last_execution']) . "'";
				} else if ($opt['last_execution'] == 'n') {
					$where[] = "(ES.id IS NULL OR E.status = 'n')";
				}
			}

			if (count($where) > 0) {
				$sql .= " WHERE " . implode(" AND ", $where);
			}
			if (isset($opt['group_by']) and $opt['group_by'] != '') {
				$sql .= " GROUP BY " . $opt['group_by'];
			}
			if (isset($opt['order_by']) and $opt['order_by'] != '') {
				$sql .= " ORDER BY " . $opt['order_by'];
			}
			
			$rs = $this -> db -> fetchMapRowsIntoMap($sql, 'testplan_id', 'testcase_id', database::CUMULATIVE);			
			return $rs;
		}

	} // end class
?>
