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


$app->group('/pds_registrazioni_filtri', function (RouteCollectorProxy $groupFiltro) use ($auth) {

  $data = [
    "log" => true,
    "table" => "ssm.ssm_pds_registrazioni_filtri",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_pds_registrazioni_filtri.*",
      "concat(ut.nome, ' ', ut.cognome) as specializzando_nome" ],
    "list_join" => [
      [
          "ssm_utenti ut",
          " ut.id=ssm.ssm_pds_registrazioni_filtri.idspecializzando "
      ],
    ]
  ];

  $crud = new CRUD( $data );

  // list
  $groupFiltro->get('/{idscuola}/{idcoorte}/{idcontatore}', function (Request $request, Response $response, $args) use ($auth, $crud) {
    $log = new Logger();
    $db = new dataBase();

    $p = $request->getQueryParams();

    if( $p['srt'] == "nome" )
      $p['srt'] = "ut.nome";

    if( $p['s'] != "" ) {
      $p['search'] = array(
        "field" => "ut.nome",
        "operator" => " LIKE ",
        "value" => "%" . $p['s'] . "%"
      );
    }

    $p['_ssm.ssm_pds_registrazioni_filtri.idstatus'] = "1";
    $p['_ssm.ssm_pds_registrazioni_filtri.idscuola'] = $args['idscuola'];
    $p['_ssm.ssm_pds_registrazioni_filtri.idcontatore'] = $args['idcontatore'];

    $res = $crud->record_list( $p );

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  // get
  $groupFiltro->get('/{idscuola}/{idcoorte}/{idcontatore}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $res = $crud->record_get( $args['id'] )['data'][0];
      if( $res == "" )
        $res = [];


      $sql = sprintf( "SELECT ut.id, concat(cognome,' ',nome) as text
        FROM ssm_utenti_ruoli_lista url
        LEFT JOIN ssm_utenti ut ON ut.id=url.idutente
        WHERE url.idscuola='%s' AND ut.idstatus=1 AND url.idruolo=8 AND ut.idcoorte='%s'
        ORDER BY cognome,nome", $db->real_escape_string( $args['idscuola'] ), $db->real_escape_string($args['idcoorte']) );
      $db = new dataBase();
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        $ar[] = $rec;
      }
      $res['specializzandi_list'] = $ar;

      $response->getBody()->write( json_encode( $res, JSON_NUMERIC_CHECK ) );
      return $response;
  });

  // new
  $groupFiltro->put('/{idscuola}/{idcoorte}/{idcontatore}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $p['idscuola'] = $args['idscuola'];
    $p['idcontatore'] = $args['idcontatore'];

    $log->log( "PUT filtro - " . json_encode( $args ) . " - " . json_encode( $p ) );

    $retValidate = validate( "registrazioni_filtri", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

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
  $groupFiltro->post('/{idscuola}/{idcoorte}/{idcontatore}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "update - " . json_encode( $p ) );


    $retValidate = validate( "registrazioni_filtri", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

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
  $groupFiltro->delete('/{idscuola}/{idcoorte}/{idcontatore}/{id}', function (Request $request, Response $response, $args) use ($crud) {
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
