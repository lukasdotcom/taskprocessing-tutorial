<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\TaskprocessingTutorial\AppInfo\Application::APP_ID, OCA\TaskprocessingTutorial\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\TaskprocessingTutorial\AppInfo\Application::APP_ID, OCA\TaskprocessingTutorial\AppInfo\Application::APP_ID . '-main');

?>

<div id="taskprocessing-tutorial"></div>
