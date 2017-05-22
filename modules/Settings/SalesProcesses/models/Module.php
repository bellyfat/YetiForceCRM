<?php

/**
 * Settings SalesProcesses module model class
 * @package YetiForce.Model
 * @copyright YetiForce Sp. z o.o.
 * @license YetiForce Public License 2.0 (licenses/License.html or yetiforce.com)
 */
class Settings_SalesProcesses_Module_Model extends Vtiger_Base_Model
{

	public static function getCleanInstance()
	{
		$instance = new self();
		return $instance;
	}

	public static function getConfig($type = false)
	{

		\App\Log::trace('Start ' . __METHOD__ . " | Type: $type");
		$cache = Vtiger_Cache::get('SalesProcesses', $type === false ? 'all' : $type);
		if ($cache) {
			\App\Log::trace('End ' . __METHOD__);
			return $cache;
		}
		$db = PearDatabase::getInstance();
		$params = [];
		$returnArrayForFields = ['groups', 'status', 'calculationsstatus', 'salesstage', 'salesstage', 'assetstatus', 'statuses_close'];
		$sql = 'SELECT * FROM yetiforce_proc_sales';
		if ($type) {
			$sql .= ' WHERE type = ?';
			$params[] = $type;
		}

		$result = $db->pquery($sql, $params);
		if ($db->num_rows($result) == 0) {
			return [];
		}
		$config = [];
		$numRowsCount = $db->num_rows($result);
		for ($i = 0; $i < $numRowsCount; ++$i) {
			$param = $db->query_result_raw($result, $i, 'param');
			$value = $db->query_result_raw($result, $i, 'value');
			if (in_array($param, $returnArrayForFields)) {
				$value = $value == '' ? [] : explode(',', $value);
			}
			if ($type) {
				$config[$param] = $value;
			} else {
				$config[$db->query_result_raw($result, $i, 'type')][$param] = $value;
			}
		}
		Vtiger_Cache::set('SalesProcesses', $type === false ? 'all' : $type, $config);
		\App\Log::trace('End ' . __METHOD__);
		return $config;
	}

	public static function setConfig($param)
	{
		\App\Log::trace('Start ' . __METHOD__);
		$value = $param['val'];
		if (is_array($value)) {
			$value = implode(',', $value);
		}
		App\Db::getInstance()->createCommand()
			->update('yetiforce_proc_sales', ['value' => $value], ['type' => $param['type'], 'param' => $param['param']])
			->execute();
		\App\Log::trace('End ' . __METHOD__);
		return true;
	}

	/**
	 * Checks if products are set to be narrowed to only those related to Opportunity
	 * @return - true or false
	 */
	public static function checkRelatedToPotentialsLimit($moduleName)
	{
		if (!self::isLimitForModule($moduleName)) {
			return false;
		}
		$popup = self::getConfig('popup');
		if ($popup['limit_product_service'] == 'true') {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if limit can be applied to this module
	 * @return - true or false
	 */
	public static function isLimitForModule($moduleName)
	{
		$validModules = array('SQuotes', 'SCalculations', 'SQuoteEnquiries', 'SRequirementsCards', 'SSingleOrders', 'SRecurringOrders');
		return in_array($moduleName, $validModules);
	}
}
