<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../autoload.php';

use Core\Router;

$router = new Router();

$router->add('POST', '/api/login', 'AuthController@login');
$router->add('POST', '/api/register', 'AuthController@register');
$router->add('POST', '/api/logout', 'AuthController@logout');

$router->add('GET', '/api/profile', 'ProfileController@getProfil');
$router->add('GET', '/api/profile/schedule', 'ProfileController@getSchedule');
$router->add('POST', '/api/profile/schedule', 'ProfileController@updateSchedule');

$router->add('GET', '/api/users', 'UserController@getTotiUtilizatorii');
$router->add('GET', '/api/specialists', 'UserController@getSpecialisti');
$router->add('POST', '/api/users/role', 'UserController@updateRol');
$router->add('POST', '/api/users/specialization', 'UserController@updateSpecializare');

$router->add('GET', '/api/subscriptions', 'SubscriptionController@getAbonamente');
$router->add('POST', '/api/subscriptions', 'SubscriptionController@creareAbonament');
$router->add('GET', '/api/subscriptions/history', 'SubscriptionController@getHistoryForUser');
$router->add('POST', '/api/subscriptions/status', 'SubscriptionController@updateStatus');

$router->add('GET', '/api/resources', 'SessionController@getResurse');
$router->add('GET', '/api/sessions', 'SessionController@getSesiuni');
$router->add('GET', '/api/sessions/detail', 'SessionController@getSesiuneById');
$router->add('POST', '/api/sessions', 'SessionController@creareSesiune');
$router->add('POST', '/api/sessions/cancel', 'SessionController@anulareSesiune');
$router->add('POST', '/api/sessions/update', 'SessionController@modificareSesiune');
$router->add('POST', '/api/bookings', 'SessionController@rezerva');

$router->add('GET', '/api/requests', 'PrivateRequestController@getRequests');
$router->add('POST', '/api/requests', 'PrivateRequestController@createRequest');
$router->add('POST', '/api/requests/accept', 'PrivateRequestController@acceptRequest');
$router->add('POST', '/api/requests/deny', 'PrivateRequestController@denyRequest');

$router->add('GET', '/api/admin/resources', 'ResourceController@getRoomsAndEquipment');
$router->add('POST', '/api/admin/rooms', 'ResourceController@saveRoom');
$router->add('POST', '/api/admin/rooms/delete', 'ResourceController@deleteRoom');
$router->add('POST', '/api/admin/equipment', 'ResourceController@saveEquipment');
$router->add('POST', '/api/admin/equipment/delete', 'ResourceController@deleteEquipment');
$router->add('POST', '/api/admin/import/trainers', 'ResourceController@importTrainers');

$router->add('GET', '/api/export/csv', 'ExportController@exportCsv');
$router->add('GET', '/api/export/xml', 'ExportController@exportXml');

$router->add('GET', '/api/stats/json', 'StatsController@getJsonStats');
$router->add('GET', '/api/stats/export/csv', 'StatsController@exportStatsCsv');
$router->add('GET', '/api/stats/export/xml', 'StatsController@exportStatsXml');

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

$router->dispatch($requestUri, $requestMethod);
