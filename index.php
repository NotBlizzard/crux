<?php
require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

$app = new Silex\Application();
$array = Yaml::parse(file_get_contents('database.yaml'));

$app->register(new Silex\Provider\TwigServiceProvider(), [
  'twig.path' => __DIR__.'/views',
]);
try {
  $db = new PDO("pgsql:dbname=$_SERVER['DATABASE_NAME'];host=$_SERVER['DATABASE_HOST']", $_SERVER['DATABASE_USERNAME'], $_SERVER['DATABASE_PASSWORD']);
} catch (PDOException $e) {
  echo $e->getMessage();
}
$app['debug'] = true;

$app->get('/', function() use ($app) {
 return $app['twig']->render('hello.html.twig');
});

$app->get("/{id}", function($id) use ($app, $db) {
  $st = $db->prepare("SELECT content FROM pastes WHERE id=(:id) LIMIT 1");
  $st->execute([':id' => "$id"]);
  $row = $st->fetch();
  return $app['twig']->render('show.html.twig', [
    'content' => $row['content']
  ]);
});

$app->post('/', function(Request $request) use ($app, $db) {
  $content = $request->get('content');
  if (trim($content) === "") {
    return $app['twig']->render('hello.html.twig', ['error' => 'Your paste was blank']);
  }
  $st = $db->prepare("INSERT INTO pastes (content) VALUES(:content) RETURNING id");
  $st->execute([':content' => $content]);

  $id = $st->fetch()[0];
  return $app->redirect("/".$id);
});

$app->run();