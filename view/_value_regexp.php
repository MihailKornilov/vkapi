<?php
define('REGEXP_NUMERIC',    '/^[0-9]{1,20}$/i');
define('REGEXP_INTEGER',    '/^-?[0-9]{1,20}$/i');
define('REGEXP_CENA',       '/^[0-9]{1,10}(.[0-9]{1,2})?(,[0-9]{1,2})?$/i');
define('REGEXP_CENA_MINUS', '/^-?[0-9]{1,10}(.[0-9]{1,2})?(,[0-9]{1,2})?$/i');
define('REGEXP_BOOL',       '/^[0-1]$/');
define('REGEXP_DATE',       '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/');
define('REGEXP_YEAR',       '/^[0-9]{4}$/');
define('REGEXP_YEARMONTH',  '/^[0-9]{4}-[0-9]{2}$/');
define('REGEXP_WORD',       '/^[a-z0-9]{1,20}$/i');
define('REGEXP_MYSQLTABLE', '/^[a-z0-9_]{1,30}$/i');
define('REGEXP_WORDFIND',   '/^[a-zA-Zà-ÿÀ-ß0-9,\.; ]{1,}$/i');
