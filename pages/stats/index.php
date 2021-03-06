<?php

/**
 * @defgroup pages_stats Statistics Pages
 */

/**
 * @file pages/stats/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_stats
 * @brief Handle requests for statistics pages.
 *
 */

import('lib.pkp.pages.stats.PKPStatsHandler');
define('HANDLER_CLASS', 'PKPStatsHandler');
