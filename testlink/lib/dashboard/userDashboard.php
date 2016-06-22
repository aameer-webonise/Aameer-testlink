<?php
	/*
	 *
	 */

	require_once ('../../config.inc.php');
	require_once ('common.php');
	require_once ("exttable.class.php");
	$timerOn = microtime(true);
	testlinkInitPage($db, TRUE);

	$results_config = config_get('results');
	unset($results_config['status_label_for_exec_ui']['not_available']);
	$chart_cfg = $results_config['charts']['dimensions']['overallPieChart'];

	$smarty = new TLSmarty();
	$tproject_mgr = new testproject($db);
	$tcase_mgr = new sabatestcase($db);
	$tplan_mgr = new sabatestplan($db);

	$tables = $tcase_mgr::getDBTables();
	$views = $tcase_mgr::getDBViews();

	// Round precision config
	$round_precision = config_get('dashboard_precision');

	// Limit Testcases Pending for user
	$limit_testcases = 8;

	$currentUser = $_SESSION['currentUser'];
	$userID = $currentUser -> dbID;
	$args = init_args($db);
	$release_mgr = new release($db, $args -> tproject_id);
	$gui = new stdClass();
	$tplan_mgr -> platform_mgr -> setTestProjectID($args -> tproject_id);
	$projectPlatforms = $tplan_mgr -> platform_mgr -> getAll();
	$args -> platformReport = array();
	$status_labels = array();
	foreach ($results_config['status_label_for_exec_ui'] as $l_key => $status_label) {
		$status_labels[$l_key] = lang_get($status_label);
		$status_labels[substr($l_key, 0, 1)] = lang_get($status_label);
	}

	foreach ($projectPlatforms as $pltfrm) {
		$args -> platformReport[$pltfrm['id']] = array(
			'platform_name' => $pltfrm['name'],
			'total' => 0,
			'executed' => 0,
			'not_run' => 0,
			'passed' => 0,
			'blocked' => 0,
			'failed' => 0
		);
	}
	$args -> platformReport[0] = array(
		'platform_name' => 'N/A',
		'total' => 0,
		'executed' => 0,
		'not_run' => 0,
		'passed' => 0,
		'blocked' => 0,
		'failed' => 0
	);
	$gui -> release_id = $args -> release_id;
	$gui -> projectPrefix = $tproject_mgr -> getTestCasePrefix($args -> tproject_id);
	$cfields = $tproject_mgr -> get_linked_custom_fields($args -> tproject_id, null, 'name');
	$cfields_to_load = array(
		"AutomationStatus" => 'automationStatusCF',
		$gui -> projectPrefix . 'AutomationStatus' => 'automationStatusCF',
		$gui -> projectPrefix . 'ProductArea' => 'productAreaCF',
		'Sprint' => 'sprintCf',
		'TestPlanType' => 'testplanTypeCf'
	);

	foreach ($cfields_to_load as $cfname => $cfobj_name) {
		${$cfobj_name} = null;
		$v = "show_" . $cfobj_name;
		$$v = false;
		if (isset($cfields[$cfname])) {
			$$cfobj_name = $cfields[$cfname];
			$v = "show_" . $cfobj_name;
			$$v = !is_null($$cfobj_name);
		}
	}
	/**
	 * DG add for automation metrics portlet
	*/
	 
	$cfields_tp = $tplan_mgr -> cfield_mgr -> get_linked_to_testproject($args -> tproject_id);
	$cf_to_load_tp = array(
		'AutomationStatus' => 'automationStatus',
		$args -> projectPrefix . 'ProductArea' => 'productAreas'
	);
		
	foreach ($cf_to_load_tp as $cfKeyName_tp) {
		$args -> $cfKeyName_tp = null;
	}
	
	foreach ($cfields_tp as $project_cf_tp) {
		if (in_array($project_cf_tp['name'], array_keys($cf_to_load_tp))) {
			$args -> {$cf_to_load_tp[$project_cf_tp['name']]} = $project_cf_tp;
		}
	}
	/* DG end  */
	
	
	$showMyTestPlans = true;
	if ($currentUser -> globalRole -> name == 'Saba Tester') {
		$showMyTestPlans = true;
	}
	$dashboardObj = array();
	$accPlans = null;
	if (!is_null($args -> release_id) and $args -> release_id > 0) {
		$accPlans = $release_mgr -> get_linked_plans($args -> release_id);
		$releaseInfo = $release_mgr -> get_by_id($args -> release_id);
		$gui -> release_name = $releaseInfo['name'];
		$gui -> release_active = ($releaseInfo['is_active'] == 1) ? true : false;
		$gui -> release_show_execution_portlet = ($releaseInfo['is_execution_portlet_visible'] == 1) ? true : false;
	} else {
		$releaseInfo = $release_mgr -> get_accessible_for_user();
		$releaseInfo = $releaseInfo[0];
		if ($releaseInfo == null) {
			$accPlans = $currentUser -> getAccessibleTestPlans($db, $args -> tproject_id, null, array(
				'output' => 'map',
				'active' => 1
			));
		} else {
			$accPlans = $release_mgr -> get_linked_plans($releaseInfo['id']);
			$gui -> release_name = $releaseInfo['name'];
			$gui -> release_active = ($releaseInfo['is_active'] == 1) ? true : false;
			$gui -> release_show_execution_portlet = ($releaseInfo['is_execution_portlet_visible'] == 1) ? true : false;
		}

	}
	if ($accPlans == null) {
		$accPlans = array();
	}

	$gui -> testplanAuthors = $tplan_mgr -> get_plan_authors(array_keys($accPlans));

	// if this test plan is present on $arrPlans
	//	  OK we will set it on $arrPlans as selected one.
	// else
	//    need to set test plan on session
	// Automation dashboard
	$index = 0;
	$gui -> regressionMetrics = getRegressionMetrics($db, $args -> release_id, $results_config,$productAreaCF);
	//var_dump($gui -> regressionMetrics);
	$gui -> buildReport = getBuildMetrics($db, array_keys($accPlans), $results_config);
	$gui -> showAutomationStatusPortlet = false;
	$automationStatusCF =$args -> automationStatus;
	if (!is_null($automationStatusCF)) {
		
		$gui -> automationStatusReport = getAutomationStatusMetrics($db, $args -> release_id, $automationStatusCF, $productAreaCF);
		$gui -> showAutomationStatusPortlet = count($gui -> automationStatusReport) > 0;
		if ($gui -> showAutomationStatusPortlet) {
			foreach ($gui -> automationStatusReport as $asr_key => $asr) {
				$total = 0;
				foreach ($asr as $key_a => $val_a) {
					if (is_numeric($val_a)) {
						$total += $val_a;
					}
				}
			//dg	$gui -> automationStatusReport[$asr_key]['Total'] = $total;
			}
			$gui -> automationStatusColumns = array_keys($gui -> automationStatusReport[0]);
			
		}
	}
  //  var_dump($gui -> automationStatusReport);die();
	$gui -> grants = array();
	$gui -> myTestPlans = array();
	$gui -> myTestPlansCount = 0;
	// User has test project rights
	$gui -> grants['project_edit'] = $currentUser -> hasRight($db, 'mgt_modify_product');
	$gui -> grants['keywords_view'] = $currentUser -> hasRight($db, "mgt_view_key");
	$gui -> grants['keywords_edit'] = $currentUser -> hasRight($db, "mgt_modify_key");
	$gui -> grants['platform_management'] = $currentUser -> hasRight($db, "platform_management");
	$gui -> grants['issuetracker_management'] = $currentUser -> hasRight($db, "issuetracker_management");
	$gui -> grants['view_tc'] = $currentUser -> hasRight($db, "mgt_view_tc");
	$gui -> grants['modify_tc'] = null;
	$gui -> hasTestCases = false;

	if ($gui -> grants['view_tc']) {
		$gui -> grants['modify_tc'] = $currentUser -> hasRight($db, "mgt_modify_tc");
		$gui -> hasTestCases = $tproject_mgr -> count_testcases($args -> tproject_id) > 0 ? 1 : 0;
	}
	$gui -> grants['system_health'] = $currentUser -> hasRight($db, "system_health");
	$rights2check = array(
		'testplan_execute',
		'testplan_create_build',
		'testplan_metrics',
		'testplan_planning',
		'testplan_user_role_assignment',
		'mgt_testplan_create',
		'mgt_release_view',
		'mgt_cfr_view',
		'mgt_users',
		'cfield_view',
		'cfield_management',
		'mgt_cfr_view',
		"mgt_config_view"
	);
	foreach ($rights2check as $key => $the_right) {
		// trying to remove Evil global coupling
		// $gui->grants[$the_right] = $currentUser->hasRight($db,$the_right);
		$gui -> grants[$the_right] = $currentUser -> hasRight($db, $the_right, $args -> tproject_id);
	}

	$filters = array('plan_status' => ACTIVE);
	$gui -> num_active_tplans = sizeof($tproject_mgr -> get_all_testplans($args -> tproject_id, $filters));

	// get Test Plans available for the user

	$history_img = TL_THEME_IMG_DIR . "history_small.png";
	$exec_img = TL_THEME_IMG_DIR . "exec_icon.png";
	$edit_img = TL_THEME_IMG_DIR . "edit_icon.png";
	$l18n = init_labels(array(
		'tcversion_indicator' => null,
		'goto_testspec' => null,
		'version' => null,
		'testplan' => null,
		'assigned_tc_overview' => null,
		'testcases_assigned_to_user' => null,
		'design' => null,
		'execution' => null,
		'execution_history' => null
	));
	$map_status_code = $results_config['status_code'];
	$map_code_status = $results_config['code_status'];
	$map_status_label = $results_config['status_label'];
	$map_statuscode_css = array();
	foreach ($map_code_status as $code => $status) {
		if (isset($map_status_label[$status])) {
			if (in_array($status, array(
				'all',
				'unknown',
				'not_available'
			))) {
				continue;
			}
			$label = $map_status_label[$status];
			$map_statuscode_css[$code] = array();
			$map_statuscode_css[$code]['translation'] = $status_labels[$status];
			$map_statuscode_css[$code]['css_class'] = $map_code_status[$code] . '_text';
		}
	}

	$gui -> accPlans = $accPlans;
	$gui -> countPlans = count($gui -> accPlans);
	$gui -> launcher = "lib/general/frmWorkArea.php";
	$gui -> resultSet = null;
	if ($showMyTestPlans) {
		// Testcases Assigned to me Portlet
		$filters = array(
			"tplan_status" => "active",
			"build_status" => "open"
		);
		$options = new stdClass;
		$options -> mode = 'full_path';
		$gui -> resultSet = $tcase_mgr -> get_assigned_to_user($userID, $args -> tproject_id, null, $options, $filters);
		//var_dump(array_pop($gui -> resultSet));die();
		$doIt = !is_null($gui -> resultSet);
		if ($doIt) {
			$tables = tlObjectWithDB::getDBTables(array('nodes_hierarchy'));

			$tplanSet = array_keys($gui -> resultSet);
			$sql = "SELECT name,id FROM {$tables['nodes_hierarchy']} " . "WHERE id IN (" . implode(',', $tplanSet) . ")";
			$gui -> tplanNames = $db -> fetchRowsIntoMap($sql, 'id');

			$optColumns = array(
				'user' => false,
				'priority' => false
			);

			foreach ($gui->resultSet as $tplan_id => $tcase_set) {
				$getOpt = array('outputFormat' => 'map');
				$platforms = $tplan_mgr -> getPlatforms($tplan_id, $getOpt);
				$show_platforms = !is_null($platforms);
				//$show_platforms = false;
				list($columns, $sortByColumn) = getColumnsDefinition($optColumns, $show_platforms, $platforms);
				$rows = array();
				$count = 0;
				$totalTestCase = count($tcase_set);
				$passed = 0;
				$do_not_skip = true;
				foreach ($tcase_set as $tcase_platform) {
					if ($count >= $limit_testcases) {
						break;
					}
					foreach ($tcase_platform as $tcase) {
						if (count($tplan_mgr -> get_same_status_for_build_set($tplan_id, array($tcase['build_id']), 'p', $tcase['platform_id'])) == $totalTestCase) {
							$do_not_skip = false;
							break;
						}
						$current_row = array();
						$tcase_id = $tcase['testcase_id'];
						$tcversion_id = $tcase['tcversion_id'];

						//if ($args -> show_user_column) {
						//$current_row[] = htmlspecialchars($names[$tcase['user_id']]['login']);
						//}

						$current_row[] = htmlspecialchars($tcase['build_name']);
						//$current_row[] = htmlspecialchars($tcase['tcase_full_path']);

						// create linked icons

						$exec_history_link = "<a href=\"javascript:openExecHistoryWindow({$tcase_id});\">" . "<img title=\"{$l18n['execution_history']}\" src=\"{$history_img}\" /></a> ";
						$exec_link = '';
						if ($args -> grants -> execute_testplan == 'yes' && $gui -> release_active) {
							$exec_link = "<a href=\"javascript:openExecutionWindow(" . "{$tcase_id},{$tcversion_id},{$tcase['build_id']}," . "{$tcase['testplan_id']},{$tcase['platform_id']});\">" . "<img title=\"{$l18n['execution']}\" src=\"{$exec_img}\" /></a> ";
						}
						$edit_link = '';
						if ($args -> grants -> edit_testcase == 'yes') {
							$edit_link = "<a href=\"javascript:openTCEditWindow({$tcase_id});\">" . "<img title=\"{$l18n['design']}\" src=\"{$edit_img}\" /></a> ";
						}
						$current_row[] = "<!-- " . sprintf("%010d", $tcase['tc_external_id']) . " -->" . $exec_history_link . $exec_link . $edit_link . htmlspecialchars($tcase['prefix']) . "-" . $tcase['tc_external_id'] . " : " . htmlspecialchars($tcase['name']) . sprintf($l18n['tcversion_indicator'], $tcase['version']);

						if ($show_platforms) {
							$current_row[] = htmlspecialchars($tcase['platform_name']);
						}

						$last_execution = $tcase_mgr -> get_last_execution($tcase_id, $tcversion_id, $tplan_id, $tcase['build_id'], $tcase['platform_id']);
						$status = $last_execution[$tcversion_id]['status'];

						if ($status == 'p') {
							$passed++;
							continue;
						}
						if (!$status) {
							$status = $map_status_code['not_run'];
						}
						$current_row[] = array(
							"value" => $status,
							"text" => $map_statuscode_css[$status]['translation'],
							"cssClass" => $map_statuscode_css[$status]['css_class']
						);

						// add this row to the others
						$rows[] = $current_row;
						$count++;
						if ($count >= $limit_testcases) {
							break;
						}
					}
				}

				/* different table id for different reports:
				 * - Assignment Overview if $args->show_all_users is set
				 * - Test Cases assigned to user if $args->build_id > 0
				 * - Test Cases assigned to me else
				 */
				$table_id = "tl_table_tc_assigned_to_me_for_tplan_";

				// add test plan id to table id
				$table_id .= $tplan_id;
				//
				$matrix = new tlExtTable($columns, $rows, $table_id);
				$matrix -> title = lang_get('testplan') . (($gui -> release_active) ? ": <a title='Execute Test cases assigned to you in " . $accPlans[$tplan_id]['name'] . "' href='lib/testcases/myTCLinkedToPlan.php?tprojectID=" . $args -> tproject_id . "&tplanID=" . $tplan_id . "'><img src='" . TL_THEME_IMG_DIR . "exec_icon.png' /></a> " : " ") . htmlspecialchars($gui -> tplanNames[$tplan_id]['name']) . " ($passed / $totalTestCase) - " . round(($passed * 100 / $totalTestCase), $round_precision) . "% Completed";
				$gui -> myTestPlans[] = $gui -> tplanNames[$tplan_id]['name'];
				$gui -> myTestPlansCount++;
				// default grouping by first column, which is user for overview, build otherwise
				$matrix -> setGroupByColumnName(lang_get($columns[0]['title_key']));

				// make table collapsible if more than 1 table is shown and surround by frame
				if (count($tplanSet) > 1) {
					$matrix -> collapsible = true;
					$matrix -> frame = true;
				}

				// define toolbar
				$matrix -> showToolbar = false;
				$matrix -> toolbarExpandCollapseGroupsButton = false;
				$matrix -> toolbarShowAllColumnsButton = true;

				$matrix -> setSortByColumnName($sortByColumn);
				$matrix -> sortDirection = 'DESC';
				if ($do_not_skip) {
					$gui -> tableSet[$tplan_id] = $matrix;
				}
			}
		}
	}

	// Metrics Dashboard
	$show_all_status_details =          config_get('metrics_dashboard') -> show_test_plan_status;
	
	$gui -> tplan_metrics = array();
	$gui -> show_platforms = false;
	list($gui -> tplan_metrics, $gui -> show_platforms) = getMetrics($db, $_SESSION['currentUser'], $args, $results_config, $accPlans);
	//var_dump($gui->tplan_metrics);
	$gui -> platformReport = $args -> platformReport;

	if ($gui -> tplan_metrics == null) {
		$gui -> tplan_metrics = array();
	}
	if (!isset($gui -> tplan_metrics['testplans']) || $gui -> tplan_metrics['testplans'] == null) {
		$gui -> tplan_metrics['testplans'] = array();
	}
	$gui -> count_tplan_metrics = count($gui -> tplan_metrics);
	//var_dump($gui -> tplan_metrics);
	$gui -> custom_testplan_metrics = array();
	
	if ($gui -> count_tplan_metrics > 0) {
		$statusSetForDisplay = $results_config['status_label_for_exec_ui'];
		$gui -> warning_msg = '';

		$matrixData = array();
		$productAreas = array();
		$sprintList = array();
		$testplanTypeList = array();
		if (!is_null($productAreaCF)) {
			$productAreas = explode("|", $productAreaCF['possible_values']);
		}
		if ($show_sprintCf) {
			$sprintList = explode("|", $sprintCf['possible_values']);
		}
		if ($show_testplanTypeCf) {
			$testplanTypeList = explode("|", $testplanTypeCf['possible_values']);
		}
		foreach ($productAreas as $pa) {
			$gui -> custom_testplan_metrics[$pa] = array('total' => 0);
			foreach ($statusSetForDisplay as $status_verbose => $status_label) {
				$gui -> custom_testplan_metrics[$pa][$status_verbose] = 0;
			}
		}
		$testplan_jiras = $release_mgr -> get_jira_linked_for_plans(array_keys($gui -> tplan_metrics['testplans']), true);
		//$testplan_jiras = array();
		//var_dump(round(microtime(true) - $timerOn,2));
		foreach ($gui->tplan_metrics['testplans'] as $tplan_id => $tplan_metrics) {
			if (!isset($tplan_metrics['platforms'])) {
				continue;
			}
			/*
			 $custom_linked_val = $tplan_mgr -> get_linked_cfields_at_design($tplan_id, $args -> tproject_id);
			 $productArea = '';
			 $sprint = '';
			 foreach ($custom_linked_val as $cfLinkedVal) {
			 if ($show_productAreaCF and $gui -> projectPrefix . 'ProductArea' == $cfLinkedVal['name']) {
			 $productArea = $cfLinkedVal['value'];
			 } else if ($show_sprintCf and $gui -> projectPrefix . 'Sprint' == $cfLinkedVal['name']) {
			 $sprint = $cfLinkedVal['value'];
			 }
			 }
			 */
			$pa_stats = &$gui -> custom_testplan_metrics[$accPlans[$tplan_id]['product_area']];
			
			
			//var_dump($pa_stats);
			unset($custom_linked_val);
			//$timerOn = microtime(true);
	
			foreach ($tplan_metrics['platforms'] as $key => $platform_metric) {
				$rowData = array();
				
				$progress = getPercentage($platform_metric['executed'], $platform_metric['active'], $round_precision);
				$pa_stats['tplan_name'] = $platform_metric['tplan_name'];
				$pa_stats['name'] = $accPlans[$tplan_id]['product_area'];
				if (!isset($pa_stats['total'])) {
					$pa_stats['total'] = 0;
				}
				$pa_stats['total'] += $platform_metric['total'];
                
				// if test plan does not use platforms a overall status is not necessary
				$tplan_string_img = '';
				if ($args -> grants -> execute_testplan == 'yes' && $gui -> release_active) {
					$tplan_string_img .= "<a title='Execute Test Plan: " . $accPlans[$tplan_id]['name'] . "' href='lib/testcases/myTCLinkedToPlan.php?tprojectID=" . $args -> tproject_id . "&tplanID=" . $tplan_id . "&myTC=false'><img src='" . TL_THEME_IMG_DIR . "exec_icon.png' /></a>";
				}

				$tplan_string_img .= "<a title='View Test Plan: " . $accPlans[$tplan_id]['name'] . "' href='lib/results/resultByTestPlan.php?tplanID=" . $tplan_id . "&tprojectID=" . $args -> tproject_id . "&format=0&guestToken=" . $args -> guestToken . "'><img src='" . TL_THEME_IMG_DIR . "world_link.png' width='12px' /></a> ";
				if ($progress <= 30) {
					$tplan_string_img = "<img src='" . TL_THEME_IMG_DIR . "warning.png' style='width:12px' title='Plan " . $accPlans[$tplan_id]['name'] . " Needs Your Attention' alt='Plan " . $accPlans[$tplan_id]['name'] . " Needs Your Attention' /> " . $tplan_string_img;
				} else if ($progress > 30 and $progress < 85) {
					$tplan_string_img = "<img src='" . TL_THEME_IMG_DIR . "ok_attention.png' style='width:12px' title='Plan " . $accPlans[$tplan_id]['name'] . " Needs Your Attention' alt='Plan " . $accPlans[$tplan_id]['name'] . " Needs Your Attention' /> " . $tplan_string_img;
				} else {
					$tplan_string_img = "<img src='" . TL_THEME_IMG_DIR . "good.png' style='width:12px' title='Plan " . $accPlans[$tplan_id]['name'] . " is at Good State' alt='Plan " . $accPlans[$tplan_id]['name'] . " is at Good State' /> " . $tplan_string_img;
				}
				$tplan_string = $platform_metric['tplan_name'];

				if ($show_all_status_details) {
					// add information for all exec statuses
					$tplan_string .= "<br>";
					foreach ($statusSetForDisplay as $status_verbose => &$status_label) {
						$tplan_string .= $status_labels[$status_label] . ": [" . getPercentage($tplan_metrics['overall'][$status_verbose], $tplan_metrics['overall']['active'], $round_precision) . "%], ";
					}
				} else {
					$tplan_string .= " - ";
				}
                
				$tplan_string .= $progress . "%";
				$rowData[] = $tplan_string_img;
				$rowData[] = $accPlans[$tplan_id]['product_area'];
				if ($show_sprintCf) {
					$rowData[] = $accPlans[$tplan_id]['sprint'];
				}
				$jira_plan_str = '';
				if (isset($testplan_jiras[$tplan_id])) {
					$jira_count = 0;
					foreach ($testplan_jiras[$tplan_id] as $key1 => $jira1) {
						if ($key1 < 2) {
							$jira_plan_str .= $jira1 . ' ';
						}
						$jira_count++;
					}
					if ($jira_count > 2) {
						$jira_plan_str .= '<span title="' . ($jira_count - 2) . ' more Jira Stories linked to this plan"> ...</span>';
					}
				}
				$rowData[] = $jira_plan_str;
				if ($show_testplanTypeCf) {
					$rowData[] = $accPlans[$tplan_id]['testplanType'];
				}
				$rowData[] = $tplan_string;
				$authors = array();

				if (isset($gui -> testplanAuthors[$tplan_id]) and is_array($gui -> testplanAuthors[$tplan_id])) {
					foreach ($gui -> testplanAuthors[$tplan_id] as $user) {
						$authors[] = $user['first'] . ' ' . $user['last'];
					}
				}
				$rowData[] = '<span title="' . implode(", ", $authors) . '">' . implode(", ", $authors) . '</span>';
				if ($gui -> show_platforms) {
					$rowData[] = $platform_metric['platform_name'];
				}

				$rowData[] = $platform_metric['total'];
				foreach ($statusSetForDisplay as $status_verbose => $status_label) {
					if (isset($platform_metric[$status_verbose])) {
						if (!isset($pa_stats[$status_verbose])) {
							$pa_stats[$status_verbose] = 0;
						}
						$rowData[] = $platform_metric[$status_verbose];
						$pa_stats[$status_verbose] += $platform_metric[$status_verbose];
						//$rowData[] = getPercentage($platform_metric[$status_verbose], $platform_metric['active'], $round_precision);
					} else {
						$rowData[] = 0;
						//$rowData[] = 0;
					}
				}

				$rowData[] = $progress;
				$matrixData[] = $rowData;
				unset($rowData);
				unset($progress);
				
			}
			//var_dump(round(microtime(true) - $timerOn,2));var_dump($gui -> custom_testplan_metrics);die();
		}
		foreach ($gui -> custom_testplan_metrics as $pi => $pa) {
			if (!isset($pa['name']) or $pa['name'] == '') {
				unset($gui -> custom_testplan_metrics[$pi]);
				
			}
			
			//Dg // Last execution matrics set here .
		}
        //var_dump($gui -> custom_testplan_metrics);die();
		$columns = getColumnsDefinitionForTestPlanProgress($gui -> show_platforms, $statusSetForDisplay, array(), $productAreas, $sprintList, $testplanTypeList);
		$table = new tlExtTable($columns, $matrixData, 'tl_table_metrics_dashboard_' + $args -> tproject_id);

		// if platforms are to be shown -> group by test plan
		// if no platforms are to be shown -> no grouping

		//$table -> setGroupByColumnName(lang_get('test_plan'));

		//$table -> setSortByColumnName(lang_get('progress'));
		$table -> sortDirection = 'DESC';
		$table -> showToolbar = true;
		$table -> toolbarExpandCollapseGroupsButton = false;
		$table -> toolbarShowAllColumnsButton = true;
		$table -> toolbarResetFiltersButton = true;
		$table -> title = lang_get('href_metrics_dashboard');
		$table -> frame = true;
		$table -> toolbarShowAllColumnsButton = true;
		$table -> showGroupItemsCount = true;
		$gui -> tableSet['tl_table_metrics_dashboard'] = $table;

		// get overall progress, collect test project metrics
		//$gui -> project_metrics = collectTestProjectMetrics($gui -> tplan_metrics,
		// array('statusSetForDisplay' => $results_config['status_label_for_exec_ui'], 'round_precision' =>
		// $round_precision));
		//var_dump($gui -> project_metrics);
	}
	if ($gui -> count_tplan_metrics > 0) {
		$gui -> project_metrics = collectTestProjectMetrics($gui -> tplan_metrics, array(
			'statusSetForDisplay' => $results_config['status_label_for_exec_ui'],
			'round_precision' => $round_precision
		));
	}
   
	$pie_metrics = array();
	foreach ($gui -> project_metrics as $key => $value) {
		$pie_metrics[$key] = $value['value'];
	}
	unset($pie_metrics['executed']);
	$gui -> pieUrl = $args -> basehref . "lib/general/getDashboardPie.php?data=" . urlencode(json_encode($pie_metrics));
	//var_dump($gui->pieUrl);
	//die();

	//$gui -> welcome = sprintf(lang_get('dashboard_welcome'), $currentUser -> firstName . ' ' .
	// $currentUser -> lastName);
 
	$gui -> tproject_id = $args -> tproject_id;
	$smarty -> assign('gui', $gui);
	$smarty -> display('userDashboard.tpl');
	//echo 'Time Taken: ' .  round(microtime(true) - $timerOn,2);

	function getColumnsDefinition($optionalColumns, $show_platforms, $platforms) {

		$colDef = array();
		// sort by test suite per default
		$sortByCol = init_labels('testsuite');

		$colDef[] = array(
			'title_key' => 'build',
			'width' => 250
		);
		//$colDef[] = array('title_key' => 'testsuite', 'width' => 70);
		$colDef[] = array(
			'title_key' => 'testcase',
			'width' => 80
		);
		if ($show_platforms) {
			$colDef[] = array(
				'title_key' => 'platform',
				'width' => 30,
				'filter' => 'list',
				'filterOptions' => $platforms
			);
		}

		$colDef[] = array(
			'title_key' => 'status',
			'width' => 30,
			'type' => 'status'
		);
		return array(
			$colDef,
			$sortByCol
		);
	}

	function collectTestProjectMetrics($tplanMetrics, $cfg) {
		
		$mm = array();
		$mm['executed']['value'] = getPercentage($tplanMetrics['total']['executed'], $tplanMetrics['total']['active'], $cfg['round_precision']);
		$mm['executed']['label_key'] = 'progress_absolute';

		foreach ($cfg['statusSetForDisplay'] as $status_verbose => $label_key) {
			$mm[$status_verbose]['value'] = getPercentage($tplanMetrics['total'][$status_verbose], $tplanMetrics['total']['active'], $cfg['round_precision']);
			$mm[$status_verbose]['label_key'] = $label_key;
		}
		return $mm;
	}

	/**
	 *
	 */
	function getMetrics(&$db, $userObj, $args, $result_cfg, $test_plans) {

		// TICKET 5212: removed debug output "getMetrics" that was shown above the normal page header
		//echo '<h1>' . __FUNCTION__ . '</h1>';
		//$chronos[] = microtime(true); $tnow = end($chronos);

		$user_id = $args -> currentUserID;
		$tproject_id = $args -> tproject_id;
		$linked_tcversions = array();
		$metrics = array();
		$tplan_mgr = new testplan($db);

		// get all tesplans accessibles  for user, for $tproject_id
		$options = array('output' => 'map');
		$options['active'] = $args -> show_only_active ? ACTIVE : TP_ALL_STATUS;

		//$test_plans = $userObj -> getAccessibleTestPlans($db, $tproject_id, null, $options);
		//var_dump($test_plans);
		//die();
		if ($test_plans == null) {
			$test_plans = array();
		}
		// Get count of testcases linked to every testplan
		// Hmm Count active and inactive ?

		$metricsMgr = new tlTestPlanMetrics($db);

		$metrics = array(
			'testplans' => null,
			'total' => null
		);
		$mm = &$metrics['testplans'];
		$metrics['total'] = array(
			'active' => 0,
			'total' => 0,
			'executed' => 0
		);
		foreach ($result_cfg['status_label_for_exec_ui'] as $status_code => &$dummy) {
			$metrics['total'][$status_code] = 0;
		}
		///var_dump($metricsMgr);die();
		//$metrics['overall']['not_available'] = 0;
		//$metrics['total']['not_available'] = 0;
		$codeStatusVerbose = array_flip($result_cfg['status_code']);
		$showPlatforms = false;
	
		foreach ($test_plans as $key => &$dummy) {

			// We need to know if test plan has builds, if not we can not call any method
			// that try to get exec info, because you can only execute if you have builds.
			//var_dump($dummy['name']);
			/*$flag=1; dg added temp code for testing
			if (strpos(strtolower($dummy['name']),'regression') !== false or $dummy['testplanType'] == 'Regression')
			{
			 	$dg++;
				$flag=1;
			}*/
			
			
			
			
			$buildSet = $tplan_mgr -> get_builds($key);
			if (is_null($buildSet)) {
				continue;
			}

			$platformSet = $tplan_mgr -> getPlatforms($key);
			//$showPlatforms = !is_null($platformSet);

			if (!is_null($platformSet)) {

				$neurus = $metricsMgr -> getExecCountersByPlatformExecStatus($key, null, array(
					'getPlatformSet' => true,
					'getOnlyActiveTCVersions' => true
				));

				//$mm[$key]['overall']['active'] = $mm[$key]['overall']['executed'] = 0;
				foreach ($neurus['with_tester'] as $platform_id => &$pinfo) {
					/*
					 $xd = &$mm[$key]['platforms'][$platform_id];
					 $xd['tplan_name'] = $dummy['name'];
					 $xd['platform_name'] = $neurus['platforms'][$platform_id];
					 $xd['total'] = $xd['active'] = $neurus['total'][$platform_id]['qty'];
					 $xd['executed'] = 0;
					 **/
					$args -> platformReport[$platform_id]['total'] += $neurus['total'][$platform_id]['qty'];
					foreach ($pinfo as $code => &$elem) {
						if ($code == 'x' or $code == '') {
							continue;
						}
						/*
						 $xd[$codeStatusVerbose[$code]] = $elem['exec_qty'];
						 if ($codeStatusVerbose[$code] != 'not_run') {
						 $xd['executed'] += $elem['exec_qty'];
						 }
						 if (!isset($mm[$key]['overall'][$codeStatusVerbose[$code]])) {
						 $mm[$key]['overall'][$codeStatusVerbose[$code]] = 0;
						 }
						 $mm[$key]['overall'][$codeStatusVerbose[$code]] += $elem['exec_qty'];
						 $metrics['total'][$codeStatusVerbose[$code]] += $elem['exec_qty'];
						 */
						$args -> platformReport[$platform_id][$codeStatusVerbose[$code]] += $elem['exec_qty'];
					}
					/*
					 $mm[$key]['overall']['executed'] += $xd['executed'];
					 $mm[$key]['overall']['active'] += $xd['active'];
					 */
				}
				unset($neurus);
				//$mm[$key]['overall']['total'] = $mm[$key]['overall']['active'];
				//$metrics['total']['executed'] += $mm[$key]['overall']['executed'];
				//$metrics['total']['active'] += $mm[$key]['overall']['active'];
			} else {
				$mm[$key]['overall'] = $metricsMgr -> getExecCountersByExecStatus($key, null, array('getOnlyActiveTCVersions' => true));
				//$mm[$key]['overall']['active'] = $mm[$key]['overall']['total'];
				$args -> platformReport[0]['total'] += $mm[$key]['overall']['total'];
				// compute executed

				//$mm[$key]['overall']['executed'] = 0;
				foreach ($mm[$key]['overall'] as $status_code => $qty) {
					/*
					 if ($status_code != 'not_run' && $status_code != 'total' && $status_code != 'active') {
					 $mm[$key]['overall']['executed'] += $qty;
					 }
					 */
					if ($status_code != 'total' && $status_code != 'active') {
						/*
						 if (!isset($metrics['total'][$status_code])) {
						 $metrics['total'][$status_code] = 0;
						 }
						 $metrics['total'][$status_code] += $qty;
						 */
						$args -> platformReport[0][$status_code] += $qty;
					}
				}
				/*
				 $metrics['total']['executed'] += $mm[$key]['overall']['executed'];
				 $metrics['total']['active'] += $mm[$key]['overall']['active'];

				 $mm[$key]['platforms'][0] = $mm[$key]['overall'];
				 $mm[$key]['platforms'][0]['tplan_name'] = $dummy['name'];
				 $mm[$key]['platforms'][0]['platform_name'] = 'N/A';
				 */
			}

			$mm[$key]['overall'] = $metricsMgr -> getExecCountersByExecStatus($key, null, array(
				'getOnlyActiveTCVersions' => true,
				'getPlatformSet' => true
			));

			$mm[$key]['overall']['active'] = $mm[$key]['overall']['total'];

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
			$metrics['total']['executed'] += $mm[$key]['overall']['executed'];
			$metrics['total']['active'] += $mm[$key]['overall']['active'];

			$mm[$key]['platforms'][0] = $mm[$key]['overall'];
			$mm[$key]['platforms'][0]['tplan_name'] = $dummy['name'];
			$mm[$key]['platforms'][0]['tplan_id'] = $dummy['id'];
			$mm[$key]['platforms'][0]['platform_name'] = lang_get('not_aplicable');

		}
		
       // echo "count of dg: $dg ";
		return array(
			$metrics,
			$showPlatforms
		);
	}

	/**
	 * get Columns definition for table to display
	 *
	 */
	function getColumnsDefinitionForTestPlanProgress($showPlatforms, $statusLbl, $platforms, $productAreas = array(), $sprintList = array(), $testplanTypeList = array()) {
		$colDef = array();
		$colDef[] = array(
			'title_key' => 'space',
			'width' => 10,
			'type' => 'text',
			'sortType' => 'asText',
			'filter' => 'string'
		);
		$colDef[] = array(
			'title_key' => 'test_plan_product_area',
			'width' => 15,
			'type' => 'text',
			'sortType' => 'asText',
			'filter' => 'list',
			'filterOptions' => $productAreas
		);
		if (count($sprintList) > 0) {
			$colDef[] = array(
				'title_key' => 'test_plan_sprint',
				'width' => 15,
				'type' => 'text',
				'sortType' => 'asText',
				'filter' => 'list',
				'filterOptions' => $sprintList
			);
		}
		$colDef[] = array(
			'title_key' => 'title_jira_ids',
			'width' => 15,
			'type' => 'text',
			'sortType' => 'asText',
			'filter' => 'string'
		);
		if (!is_null($testplanTypeList) and is_array($testplanTypeList) and count($testplanTypeList) > 0) {
			$colDef[] = array(
				'title_key' => 'testplan_type',
				'width' => 15,
				'type' => 'text',
				'sortType' => 'asText',
				'filter' => 'list',
				'filterOptions' => $testplanTypeList
			);
		}
		$colDef[] = array(
			'title_key' => 'test_plan',
			'width' => 90,
			'type' => 'text',
			'sortType' => 'asText',
			'filter' => 'string'
		);
		$colDef[] = array(
			'title_key' => 'testplan_authors',
			'width' => 20,
			'type' => 'text',
			'sortType' => 'asText',
			'filter' => 'string'
		);
		//$colDef[] = array('title_key' => 'test_plan_product_area', 'width' => 75, 'type' => 'text',
		// 'sortType' => 'asText', 'filter' => 'string');

		if ($showPlatforms) {
			$colDef[] = array(
				'title_key' => 'platform',
				'width' => 20,
				'sortType' => 'asText',
				'filter' => 'list',
				'filterOptions' => $platforms
			);
		}

		$colDef[] = array(
			'title_key' => 'th_active_tc',
			'width' => 15,
			'sortType' => 'asInt',
			'filter' => 'numeric'
		);

		// create 2 columns for each defined status
		foreach ($statusLbl as $lbl) {
			$colDef[] = array(
				'title_key' => $lbl,
				'width' => 15,
				'type' => 'int',
				'sortType' => 'asInt',
				'filter' => 'numeric'
			);
			/*
			 $colDef[] = array(
			 'title' => lang_get($lbl) . " " . lang_get('in_percent'),
			 'width' => 20,
			 'hidden' => true,
			 'col_id' => 'id_' . $lbl . '_percent',
			 'type' => 'float',
			 'sortType' => 'asFloat',
			 'filter' => 'numeric'
			 );
			 */
		}

		$colDef[] = array(
			'title_key' => 'progress',
			'width' => 15,
			'sortType' => 'asFloat',
			'filter' => 'numeric'
		);

		return $colDef;

	}

	function init_args(&$db) {
		$args = new stdClass();
		$args -> tproject_id = isset($_SESSION['testprojectID']) ? intval($_SESSION['testprojectID']) : 0;
		$args -> tproject_name = isset($_SESSION['testprojectName']) ? $_SESSION['testprojectName'] : null;
		$args -> release_id = isset($_REQUEST['release_id']) ? $_REQUEST['release_id'] : null;
		$args -> currentUser = $_SESSION['currentUser'];
		$args -> currentUserID = $args -> currentUser -> dbID;
		$args -> basehref = $_SESSION['basehref'];
		$show_only_active = isset($_REQUEST['show_only_active']) ? true : false;
		$show_only_active_hidden = isset($_REQUEST['show_only_active_hidden']) ? true : false;
		if ($show_only_active) {
			$selection = true;
		} else if ($show_only_active_hidden) {
			$selection = false;
		} else if (isset($_SESSION['show_only_active'])) {
			$selection = $_SESSION['show_only_active'];
		} else {
			$selection = true;
		}
		if (is_null($args -> release_id)) {
			$args -> release_id = isset($_SESSION['releaseID']) ? $_SESSION['releaseID'] : 0;
		}
		
		$args -> show_only_active = $_SESSION['show_only_active'] = $selection;
		$args -> guestToken = isset($_REQUEST['guestToken']) ? $_REQUEST['guestToken'] : getRandomGuestToken();
		/**
		 * Check for various rights
		 *  - execute test plan
		 *  - edit test case
		 */
		$args -> grants = new stdClass;
		$args -> grants -> execute_testplan = $args -> currentUser -> hasRight($db, 'testplan_execute', $args -> tproject_id);
		$args -> grants -> edit_testcase = $args -> currentUser -> hasRight($db, 'mgt_modify_tc', $args -> tproject_id);
		
		return $args;
	}

	function getRandomGuestToken(){
		return rand(1, 1000);
	}
	
	function getAutomationStatusMetrics(&$db, $release_id, $automationStatusCF, $productAreaCF) {
		global $tables, $views;
		$sql = "SELECT PA.value as 'Product Area'";
		$possibleValues = explode("|", $automationStatusCF['possible_values']);
		foreach ($possibleValues as $possVal) {
			$sql .= ", coalesce(sum(AST.value='$possVal'),0) as '$possVal'";
		}
		$sql .= ",(sum(IFNULL(AST.value,1)))as 'Automation Not Marked'"; //dg added
		//DG adde and LOWER(NH.name) NOT LIKE '%regression%' and sum column
		$sql .= ",((";
		foreach ($possibleValues as $possVal) {
			$sql .= "coalesce(sum(AST.value='$possVal'),0)  + "; //DG Added for total cases
		}
		$sql .= " (sum(IFNULL(AST.value,1))) + "; //dg added
		$sql .= "0 )) as TotalCases"; //dg added 
		$sql .= " FROM `{$tables['release_testplans']}` RT 
JOIN `{$tables['nodes_hierarchy']}` NH ON NH.id = RT.testplan_id and LOWER(NH.name) NOT LIKE '%regression%' 
JOIN `{$tables['cfield_design_values']}` PA ON PA.node_id = NH.id AND PA.field_id = {$productAreaCF['id']}
LEFT JOIN (SELECT distinct (tcversion_id),testplan_id,id FROM `{$tables['testplan_tcversions']}` group by tcversion_id )TTCV ON TTCV.testplan_id = NH.id  
LEFT JOIN `{$tables['cfield_design_values']}` AST ON AST.node_id = TTCV.tcversion_id AND AST.field_id = {$automationStatusCF['id']}
WHERE RT.release_id = $release_id
GROUP BY PA.value";
		//echo $sql;die();
		return $db -> get_recordset($sql);
	}

	function getRegressionMetrics(&$db, $release_id, $results_config,$productAreaCF) {
		global $tables;
		$statusCode = $results_config['status_label_for_exec_ui'];
		unset($statusCode['not_run']);
		$sql = "SELECT NHV.id AS testplan_id
		FROM {$tables['releases']} RL
		JOIN {$tables['release_testplans']} RT ON RT.release_id = RL.id AND RL.id = $release_id
		JOIN {$tables['nodes_hierarchy']} NHV ON NHV.id = RT.testplan_id AND LOWER(NHV.name) LIKE '%regression%'";
		$result = $db -> get_recordset($sql);
		if (!$result) {
			return null;
		}

		$testplan_ids = array();
		foreach ($result as $v) {
			$testplan_ids[] = $v['testplan_id'];
		}
		$sql = "SELECT CDV.value AS product_area";
		//DG added for to display all modules 
	/*	$productAreas = explode("|", $productAreaCF['possible_values']);
		foreach ($productAreas as $possVal) {
			$sql .= ", coalesce(sum(CDV.value='$possVal'),0) as '$possVal'";
		}*/
		foreach ($statusCode as $status => $label) {
			$sql .= ", coalesce(sum(status='" . $results_config['status_code'][$status] . "'),0) AS $status";
		}
		$sql .= ", coalesce(sum(E.status is NULL),0) AS not_run FROM testplan_tcversions TTCV
JOIN cfield_design_values CDV ON CDV.node_id = TTCV.testplan_id AND CDV.field_id = 6
LEFT JOIN (
	SELECT  `tcversion_id` AS  `tcversion_id` ,  `testplan_id` AS  `testplan_id` ,  `platform_id` AS  `platform_id` , MAX(  `id` ) AS  `id` 
	FROM executions WHERE testplan_id IN (" . implode(",", $testplan_ids) . ") GROUP BY  `testplan_id` , `tcversion_id`  ,  `platform_id` 
		) ES ON ES.tcversion_id = TTCV.tcversion_id AND ES.testplan_id = TTCV.testplan_id AND ES.platform_id  = TTCV.platform_id
LEFT JOIN executions E ON E.id = ES.id AND E.testplan_id = TTCV.testplan_id
WHERE TTCV.testplan_id in (" . implode(",", $testplan_ids) . ")
GROUP BY CDV.value";
		$result = $db -> get_recordset($sql);
		//echo $sql;
		$regressionMetrics = array();
		if (!is_null($result)) {
			foreach ($result as $v) {
				if (!isset($v['total'])) {
					$v['total'] = 0;
				}
				foreach ($statusCode as $status => $label) {
					$v['total'] += $v[$status];
				}
				$v['total'] += $v['not_run'];
				$regressionMetrics[$v['product_area']] = $v;
			}
		}
		return $regressionMetrics;
	}

	function getBuildMetrics(&$db, $testplan_id = array(), $results_config) {
		global $tables, $views;
		$statusCode = $results_config['status_label_for_exec_ui'];
		unset($statusCode['not_run']);
		$buildResult = array();
		if (!is_array($testplan_id)) {
			$testplan_id = array($testplan_id);
		}

		if (count($testplan_id) > 0) {
			$sql = "SELECT ";
			foreach ($statusCode as $status => $label) {
				$sql .= "coalesce(sum(status='" . $results_config['status_code'][$status] . "'),0) as $status,";
			}
			$sql .= "(select name from {$tables['builds']} where id = build_id) as build_name, build_id,
	testplan_id FROM {$tables['executions']}
	WHERE testplan_id IN (" . implode(",", $testplan_id) . ")";
			//AND build_id IN (SELECT id from builds WHERE active = 1)
			$sql .= "GROUP by build_name
	ORDER BY build_name DESC" . " LIMIT 0, 11";
	       
			$result = $db -> fetchArrayRowsIntoMap($sql, 'build_id');
			if ($result == null) {
				$result = array();
			}
			foreach ($result as $buildRow) {
				$buildRow = array_pop($buildRow);
				if (isset($buildResult[$buildRow['build_name']])) {
					foreach ($statusCode as $status => $label) {
						$buildResult[$buildRow['build_name']][$status] += $buildRow[$status];
					}
				} else {
					$buildResult[$buildRow['build_name']] = $buildRow;
					$buildResult[$buildRow['build_name']]['total'] = 0;
				}
				foreach ($statusCode as $status => $label) {
					$buildResult[$buildRow['build_name']]['total'] += $buildResult[$buildRow['build_name']][$status];
				}
			}
		}
		return $buildResult;
	}
?>