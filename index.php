<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require "vendor/autoload.php";

$app = new Slim\App;
$container = $app->getContainer();
$container['view'] = function($container) {
	$templates = __DIR__ . "/templates/";
	$view = new Slim\Views\Twig($templates);
	return $view;
};

$app->get('/', function (Request $request, Response $response) {
	$wines = getWines();
	return $this->view->render($response, 'wine.twig', ['wines' => $wines]);
});

$app->get('/{id}', function (Request $request, Response $response) {
	$id = $request->getAttribute('id');
	$wines = getWines();
	$wine = getWine($id);
	return $this->view->render($response, 'wine.twig', ['wines' => $wines, 'wine' => $wine]);
});

$app->post('/', function (Request $request, Response $response) {
	$wines = getWines();
	return $this->view->render($response, 'wine.twig', ['wines' => $wines]);
});

$app->post('/add', function (Request $request,Response $response) {
	$item = $request->getParsedBody();
	if ($item["id"]) {
		updateWine($item);
	} else {
		$item["id"] = addWine($item);
	}
	$wines = getWines();
	return $this->view->render($response, 'wine.twig', ['wines' => $wines, 'wine' => $item]);
});

$app->delete('/{id}', function (Request $request, Response $response, $args) {
	$id = $request->getAttribute('id');
	deleteWine($id);
	$wines = getWines();
	return $this->view->render($response, 'wine.twig', ['wines' => $wines]);
});

$app->post('/search', function (Request $request,Response $response) {
	$query = $request->getParsedBody();
	$wine = searchWine($query["search"]);
	$wines = getWines();
	return $this->view->render($response, 'wine.twig', ['wines' => $wines, 'wine' => $wine]);
});

$app->run();

function getWines() {
	$sql = "SELECT * FROM wine ORDER BY name";
	$db = getConnection();
	$stmt = $db->query($sql);
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$db = null;
	return $result;
}

function getConnection() {
	$host = "localhost";
	$user = "root";
	$pass = "";
	$dbname = "wines";
	$pdo = new PDO("mysql:host=$host;dbname=$dbname",
	       $user, $pass);
	return $pdo;
}

function getWine($id) {
	$sql = "SELECT * FROM wine WHERE id = ?";
	$db = getConnection();
	$stmt = $db->prepare($sql);
	$stmt->execute([$id]);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	$db = null;
	return $result;
}

function deleteWine($id) {
	$sql = "DELETE FROM wine WHERE id=?";
	$db = getConnection();
	$stmt = $db->prepare($sql);
	$stmt->execute([$id]);
	$db = null;
}

function addWine($wine) {
	$sql = "INSERT INTO wine(name, grapes, country, region, year, description) VALUES (?, ?, ?, ?, ?, ?)";
	$db = getConnection();
	$stmt = $db->prepare($sql);
	$stmt->execute([$wine['name'], $wine['grapes'], $wine['country'], $wine['region'], $wine['year'], $wine['description']]);
	$id = $db->lastInsertId();
	$db = null;
	return $id;
}

function updateWine($wine) {
	$sql = "UPDATE wine SET name=?, grapes=?, country=?, region=?, year=?, description=? WHERE id=?";
	$db = getConnection();
	$stmt = $db->prepare($sql);
	$stmt->execute([$wine['name'], $wine['grapes'], $wine['country'], $wine['region'], $wine['year'], $wine['description'], $wine['id']]);
	$db = null;
}

function searchWine($name) {
	$name = strtoupper($name);
	$sql = "SELECT * FROM wine WHERE name LIKE '%" . $name . "%'";
	$db = getConnection();
	$stmt = $db->query($sql);
	$result = $stmt->fetch();
	$db = null;
	return $result;
}
