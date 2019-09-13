<?php
/**
* @author Marv Blackwell
*/
declare(strict_types=1);

namespace BAPC;

use function xdebug_set_filter;

xdebug_set_filter(
	\XDEBUG_FILTER_CODE_COVERAGE,
	\XDEBUG_PATH_WHITELIST,
	[
		realpath(__DIR__ . '/src/'),
	]
);
