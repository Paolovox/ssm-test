<?php

// Slim framework
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy as RouteCollectorProxy;
use Slim\Exception\HttpNotFoundException as HttpNotFoundException;
use Slim\Factory\AppFactory;

use \ottimis\phplibs\Logger;
use \ottimis\phplibs\Utils;
use \ottimis\phplibs\dataBase;
use \ottimis\phplibs\Auth;
use \ottimis\phplibs\UUID;




$app->group('/sospensive', function (RouteCollectorProxy $groupTurni) use ($auth) {

  $data = [
    "log" => true,
    "table" => "ssm_utenti_sospensive",
    "id" => "id",
    "sort" => "data_inizio",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [
      "ssm_utenti_sospensive.*",
      "ssm_sospensive_tipi.tipo_sospensiva",
      "DATE_FORMAT(data_inizio, '%d-%m-%Y') as data_inizio",
      "DATE_FORMAT(data_fine, '%d-%m-%Y') as data_fine"
    ],
    "list_join" => [
      [
          "ssm_sospensive_tipi",
          "ssm_sospensive_tipi.id=ssm_utenti_sospensive.idtipo "
      ]
    ]
  ];

  $crud = new CRUD( $data );

  // list
  $groupTurni->get('/{idutente}', function (Request $request, Response $response, $args) use ($auth, $crud) {
    $p = $request->getQueryParams();
    $p['_ssm_utenti_sospensive.idstatus'] = "1";
    $p['_ssm_utenti_sospensive.idutente'] = $args['idutente'];

    $res = $crud->record_list( $p );
    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  // get
  $groupTurni->get('/{idutente}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $res = $crud->record_get( $args['id'] )['data'][0];
      if( $res == "" )
        $res = [];


      $ar = array( "table" => "ssm_sospensive_tipi", "value" => "id", "text" => "tipo_sospensiva", "order" => "tipo_sospensiva" );
      $res['tipi_list'] = $Utils->_combo_list( $ar, true, "" );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupTurni->put('/{idutente}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $p['data_inizio'] = substr( $p['data_inizio'], 0, 10 );
    $p['data_fine'] = substr( $p['data_fine'], 0, 10 );
    $p['idutente'] = $args['idutente'];

    $log->log( "/sospensiva - " . json_encode( $p ) );

    $res = $crud->record_new( $p );
    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }

    return $response;
  });


  //update
  $groupTurni->post('/{idutente}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "update sospensiva - " . $args['id'] . " - " . json_encode( $p ) );

    $p['data_inizio'] = substr( $p['data_inizio'], 0, 10 );
    $p['data_fine'] = substr( $p['data_fine'], 0, 10 );

    $res = $crud->record_update( $args['id'], $p );
    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }
  });

  // delete
  $groupTurni->delete('/{idutente}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $res = $crud->record_delete( $args['id'] );
    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }
  });


});




?>
