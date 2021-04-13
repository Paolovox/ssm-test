<?php

// Slim framework
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy as RouteCollectorProxy;
use Slim\Exception\HttpNotFoundException as HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Http\UploadedFile;


use \ottimis\phplibs\Logger;
use \ottimis\phplibs\Utils;
use \ottimis\phplibs\dataBase;
use \ottimis\phplibs\Auth;

define( "ATTACH_BASE_PATH", "attach" );
define( "ATTACH_BASE_URL", "https://admin-be-prod.specializzazionemedica.it" );

$app->get('/attach/{path}/cors/{filename}', function(Request $request, Response $response, $args) {
  $url = sprintf("attach/%s/%s", $args['path'], $args['filename']);
  $response->getBody()->write(file_get_contents($url));
  return $response
    ->withStatus(200)
    ->withHeader('Content-Type', 'image/*');
});

$app->post('/upload', function(Request $request, Response $response, $args) use ($authMW) {
  $log = new Logger();
  $Utils = new Utils();

  //$p = json_decode($request->getBody(), true);
  $p = $request->getParsedBody();

  //$log->log( "p:" . json_encode( $p ) . " - pb: " . json_encode( $p2 ) );

  $user = $request->getAttribute('user');

  /*
  $log->log( "ARGS:" . json_encode( $args ) );
  $log->log( "FILES: " . json_encode( $_FILES ) );
  $log->log( "remote: " . $_SERVER['HTTP_HOST'] );
  */

	// inserisce il record nella tabella delle immagini
	$ar = array( "original_file_name" => $_FILES['file']['name'] );
	$retAttach = $Utils->dbSql( true, "attach", $ar, "", "" );
	if( $retAttach['success'] != 1 ) {
    $response->getBody()->write( "Errore aggiornamento" );
    return $response
      ->withStatus(400)
      ->withHeader('Content-Type', 'text/plain');
	}

  $part = intval( $retAttach['id']/1000) + 1;
	$attach_dir = sprintf( "%06d", $part );

  // verifica l'esistenza della directory
	$path = sprintf( "%s", ATTACH_BASE_PATH );
	if( !is_dir($path) ) {
    $log->log( "crea directory ". $path );
		mkdir($path, 0777);
	}

  // verifica l'esistenza della directory
	$path = sprintf( "%s/%s", ATTACH_BASE_PATH, $user['idateneo'] );
	if( !is_dir($path) ) {
    $log->log( "crea directory ". $path );
		mkdir($path, 0777);
	}

	// verifica l'esistenza della directory
	$path = sprintf( "%s/%s/%s", ATTACH_BASE_PATH, $user['idateneo'], $attach_dir );
	if( !is_dir($path) ) {
    $log->log( "crea directory ". $path );
		mkdir($path, 0777);
	}

	$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
	$filepath = sprintf( "%s/%s.%s", $path, $retAttach['id'], $ext );
  $log->log( "file: " . $filepath );

  // muove il file
  $log->log( "file_exists: " . file_exists( $_FILES['file']['tmp_name'] ) );

	if( !is_writeable( $filepath ) ) {
    unlink( $filepath );
		$log->log( "NOT WRITEABLE - " . $filepath );
  }

	$log->log( "scrive su " . filepath );
	if( move_uploaded_file($_FILES['file']['tmp_name'], $filepath) ) {
		$ar['original_file_name'] = $_FILES['file']['name'];
		$ar['path'] = $attach_dir;
		$ar['file_name'] = $retAttach['id'] . "." . $ext;
		$retAttach2 = $Utils->dbSql( false, "attach", $ar, "id", $retAttach['id'] );
		$log->log( "retAttach: " . json_encode( $retAttach2 ) );
	} else {
    $log->log( $_FILES['file']['error'] );
		$log->log( "Errore nel move del file" );
    $response->getBody()->write( "Errore aggiornamento" );
    return $response
      ->withStatus(400)
      ->withHeader('Content-Type', 'text/plain');
		return 0;
	}

  $urlBase  = sprintf( "https://%s", $_SERVER['HTTP_HOST'] );
  $ret['url'] = sprintf( "%s/%s/%s",          $urlBase, $path, $ar['file_name'] );
  $ret['urlCors'] = sprintf("%s/%s/cors/%s",  $urlBase, $path, $ar['file_name']);
	$ret['url_thumb'] = sprintf( "%s/%s/%s",    $urlBase, $path, $ar['file_name'] );
  $ret['attach_name'] = $ar['original_file_name'];
  $ret['path'] = $ar['path'];
  $ret['file_name'] = $ar['file_name'];

  $ret['id'] = $retAttach['id'];

  if( $p['type'] == "profile" ) {
    $log->log( "Aggiorna foto profile - " . json_encode( $arUpdate ) . " - " . json_encode( $user ) );

    $arUpdate['profile_picture'] = json_encode( $ret );
    $retUpdate = $Utils->dbSql( false, "users", $arUpdate, "id", $user['id'] );
    $log->log( "retUpdate - " . json_encode( $retUpdate ) );
  }

	$log->log( "result attach - " . json_encode( $ret ) );

  $response->getBody()->write( json_encode( $ret ) );
  return $response
    ->withStatus(200)
    ->withHeader('Content-Type', 'application/json');

})->add( $authMW );




$app->post('/upload/crop', function(Request $request, Response $response, $args) {
  $log = new Logger();
  $Utils = new Utils();

  $log->log( "CROP" );

  $p = json_decode($request->getBody(), true);

  $original_url = sprintf( "%s/%s/%s", ATTACH_BASE_PATH, $p['path'], $p['file_name'] );
  $pinfo = pathinfo( $original_url );
  $original_path = sprintf(  "%s/%s/%s_original.%s", ATTACH_BASE_PATH, $p['path'], $pinfo['filename'], $pinfo['extension'] );
  $log->log( "rinomina " . $original_url . " su " . $original_path );
  rename( $original_url, $original_path );


  list( $type, $p['cropped']) = explode(';', $p['cropped'] );
  list(, $p['cropped']) = explode(',', $p['cropped']);
  file_put_contents( $original_url, base64_decode( $p['cropped'] ) );

  unset( $p['cropped'] );

  $response->getBody()->write( json_encode( $p ) );
  return $response
    ->withStatus(200)
    ->withHeader('Content-Type', 'application/json');

});



$app->delete('/upload', function(Request $request, Response $response, $args) {
  $db = new dataBase();
  $log = new Logger();
  $Utils = new Utils();

  $user = $request->getAttribute('user');

  $p = $request->getQueryParams();
  $log->log( "DELETE -> " . json_encode( $p ) );

  $sql = sprintf( "SELECT * FROM attach WHERE id='%d'", $db->real_escape_string( $p['id'] ) );
  $log->log( "SQL DELETE: " . $sql );

	$db->query( $sql );
	$rec = $db->fetchassoc();
  $s = sprintf( "%s/%s/%s/%s", ATTACH_BASE_PATH, $user['idateneo'], $rec['path'], $rec['file_name'] );
  unlink( $s );


  $sql = sprintf( "DELETE  FROM attach WHERE id='%d'", $db->real_escape_string( $p['id'] ) );
  $log->log( "SQL DELETE: " . $sql );


  if( $p['type'] == "profile" ) {
    $ar = array( "profile_picture" => json_encode( [] ) );
    $Utils->dbSql( false, "users", $ar, "id", $user['id'] );
  }


  return $response
  ->withStatus(200)
  ->withHeader('Content-Type', 'test/html');

})->add( $authMW );


