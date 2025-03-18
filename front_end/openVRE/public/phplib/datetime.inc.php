<?php

function getDateTimeFormat(): IntlDateFormatter {
    return datefmt_create(locale: null, timezone: $GLOBALS['timezone'], pattern: $GLOBALS['datetime']); 
}

function getLogsDateTimeFormat(): IntlDateFormatter {
    return datefmt_create(locale: null, timezone: $GLOBALS['timezone'], pattern: $GLOBALS['logs_datetime']); 
}
