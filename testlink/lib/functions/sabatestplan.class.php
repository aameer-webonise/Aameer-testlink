<?php
	/**
	 *
	 * @author Rohan Sakhale
	 */

	/** related functionality */
	require_once (dirname(__FILE__) . '/tree.class.php');
	require_once (dirname(__FILE__) . '/assignment_mgr.class.php');
	require_once (dirname(__FILE__) . '/attachments.inc.php');
	require_once ('sabaexec.inc.php');

	class sabatestplan extends testplan {
		public function get_plan_authors($tplan_ids = array()) {
			$debugMsg = "Class: " . __CLASS__ . " Function: " . __FUNCTION__;
			if (!is_null($tplan_ids) and count($tplan_ids) > 0) {
				$sql = "/* $debugMsg */ SELECT DISTINCT TTCV.testplan_id, NV.name, U.first, U.last, U.login, U.email, U.id
			FROM {$this->tables['testplan_tcversions']} TTCV
			JOIN {$this->tables['nodes_hierarchy']} NV ON NV.id = TTCV.testplan_id
			JOIN {$this->tables['users']} U ON U.id = TTCV.author_id
			 WHERE `testplan_id` IN (" . implode(",", $tplan_ids) . ")";
				$planAuthors = array();
				$result = $this -> db -> get_recordset($sql);
				foreach ($result as $key => $value) {
					if (!isset($planAuthors[$value['testplan_id']])) {
						$planAuthors[$value['testplan_id']] = array();
					}
					$planAuthors[$value['testplan_id']][] = $value;
				}
				return $planAuthors;
			}
			return array();
		}

	}
?>