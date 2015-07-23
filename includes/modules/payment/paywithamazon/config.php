<?php
include_once(DIR_FS_CATALOG . 'includes/modules/payment/paywithamazon/db_helper.php');
/**
 * Class paywithamazon_config
 */
class paywithamazon_config
{
    /**
     * Get the config value for a specific key
     *
     * @param $config_key
     * @return mixed
     */
    static function get_config_value_query($config_key)
    {
        return tep_db_query('SELECT configuration_value FROM ' . TABLE_CONFIGURATION . ' WHERE configuration_key = "' . $config_key . '"');
    }

    /**
     * Check if a config key is set.
     *
     * @param $config_key
     * @return bool
     */
    static function is_set_config_value($config_key)
    {
        return (bool)tep_db_num_rows(self::get_config_value_query($config_key));
    }

    /**
     * Set a config value.
     *
     * @param $config_key
     * @param $config_value
     */
    static function set_config_value($config_key, $config_value)
    {
        $query = 'UPDATE ' . TABLE_CONFIGURATION . ' SET configuration_value = :configValue: WHERE configuration_key = :configKey:';
        $query = bind_vars($query, ':configValue:', $config_value);
        $query = bind_vars($query, ':configKey:', $config_key);
        tep_db_query($query);
    }

    /**
     * Create a new config field.
     *
     * @param $config_key
     * @param $config_title
     * @param $config_value
     * @param $config_description
     * @param $config_group_id
     * @param $config_sort_order
     * @param $config_use_function
     * @param $config_set_function
     */
    static function create_config_field($config_key, $config_title, $config_value, $config_description, $config_group_id, $config_sort_order, $config_use_function, $config_set_function)
    {
        $query = 'INSERT INTO ' . TABLE_CONFIGURATION . ' (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values (:configTitle:, :configKey:, :configValue:, :configDescription:, :configGroupID:, :configSortOrder:, :configUseFunction:, :configSetFunction:, now())';
        $query = bind_vars($query, ':configTitle:', $config_title);
        $query = bind_vars($query, ':configKey:', $config_key);
        $query = bind_vars($query, ':configValue:', $config_value);
        $query = bind_vars($query, ':configDescription:', $config_description);
        $query = bind_vars($query, ':configGroupID:', $config_group_id);
        $query = bind_vars($query, ':configSortOrder:', $config_sort_order);
        $query = bind_vars($query, ':configUseFunction:', $config_use_function);
        $query = bind_vars($query, ':configSetFunction:', $config_set_function);
        tep_db_query($query);
    }
}