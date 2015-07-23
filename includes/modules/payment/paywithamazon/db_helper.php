<?php
function bind_vars($query, $find, $replace) {
    return str_replace($find, '"' . tep_db_input($replace) . '"', $query);
}