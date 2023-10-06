<?php

use plugin\nat\app\controller\AppController;
use Webman\Route;

Route::any('/app/nat/apps', [AppController::class, 'index']);