<?php
/**
 * Amazon Login - Login for osCommerce
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2015 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */
function bind_vars($query, $find, $replace) {
    return str_replace($find, '"' . tep_db_input($replace) . '"', $query);
}