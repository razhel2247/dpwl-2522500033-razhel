<?php
class Router
{
private array $routes;
private string $defaultController;
public function __construct(array $routes)
{
$this->routes = $routes;
$this->defaultController = $routes['default_controller'] ?? 'home';
}
public function dispatch(string $uri): void
{
$uri = trim($uri, '/');
$uri = $uri === '' ? $this->defaultController : $uri;
$uri = $this->applyRoutes($uri);
$segments = array_values(array_filter(explode('/', $uri), 'strlen'));
$controllerSegment = $segments[0] ?? $this->defaultController;
$method = $segments[1] ?? 'index';
$params = array_map('rawurldecode', array_slice($segments, 2));

if (
!$this->isSafeSegment($controllerSegment) ||
!$this->isSafeSegment($method)
) {
$this->show404('Route tidak valid.');
}
$controller = ucfirst(strtolower($controllerSegment));
$controllerFile = APPPATH . 'controllers/' . $controller . '.php';
if (!is_file($controllerFile)) {
$this->show404('Controller tidak ditemukan.');
}
require_once $controllerFile;
if (!class_exists($controller)) {
$this->show404('Class controller tidak ditemukan.');
}
$instance = new $controller();
if (
str_starts_with($method, '_') ||
!is_callable([$instance, $method])
) {
$this->show404('Method tidak ditemukan.');
}
call_user_func_array([$instance, $method], $params);
}
private function applyRoutes(string $uri): string
{
foreach ($this->routes as $pattern => $target) {
if ($pattern === 'default_controller') {
continue;
}
$regex = str_replace(
['(:any)', '(:num)'],
['([^/]+)', '([0-9]+)'],
$pattern
);
if (preg_match('#^' . $regex . '$#', $uri, $matches)) {
    array_shift($matches);
foreach ($matches as $index => $value) {
$target = str_replace(
'$' . ($index + 1),
$value,
$target
);
}
return $target;
}
}
return $uri;
}
private function isSafeSegment(string $segment): bool
{
return preg_match(
'/^[A-Za-z][A-Za-z0-9_]*$/',
$segment
) === 1;
}
private function show404(string $message): never
{
http_response_code(404);
exit($message);
}
}