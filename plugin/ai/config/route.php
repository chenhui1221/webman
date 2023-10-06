<?php

use plugin\ai\app\controller\RoleController;
use Webman\Route;

Route::any('/app/ai/roles', [RoleController::class, 'index']);
