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




$app->group('/registrazioni_combo', function (RouteCollectorProxy $group) use ($auth) {

  $data = [
    "table" => "ssm.ssm_registrazioni_combo",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
  ];


  $crud = new CRUD( $data );

  // list
  $group->get('/{idscuola}', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $p = $request->getQueryParams();
      $p['_ssm.ssm_registrazioni_combo.idstatus'] = "1";
      $p['_ssm.ssm_registrazioni_combo.idscuola'] = $args['idscuola'];
      $p['_ssm.ssm_registrazioni_combo.idtipo'] = $p['idtipo'];

      if( $p['s'] != "" ) {
        $p['search'] = array(
          "field" => "ssm.ssm_registrazioni_combo.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        );
      }

      $res = $crud->record_list( $p );
      foreach( $res['rows'] as $k => $v ) {
        $res['rows'][$k]['elementi'] = json_decode( $v['elementi'], true );
      }
      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $group->get('/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $log = new Logger();
      $log->log( "get1 " . json_encode( $args ) );

      $Utils = new Utils();
      $res = $crud->record_get( $args['id'] )['data'][0];
      if( $res == "" )
        $res = [];

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $group->put('/{idscuola}', function (Request $request, Response $response, $args) use ($crud ) {
    $p = json_decode($request->getBody(), true);
    $p['idscuola'] = $args['idscuola'];

    $retValidate = validate( "registrazioni_combo", $p );
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
  $group->post('/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $p['idscuola'] = $args['idscuola'];

    $retValidate = validate( "registrazioni_combo", $p );
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
  $group->delete('/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud) {
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


  // new item
  $group->get('/registrazioni_combo_items/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud ) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $arSelect = $Utils->dbSelect( [
      "log" => true,
      "select" => ["id", "nome"],
      "from" => "ssm.ssm_registrazioni_combo_items",
      "where" => [
        [
        "field" => "idcombo",
        "operator" => "=",
        "value" => $args['id'],
        ]
      ],
      "order" => "nome"
      ]
    );

    /*
    $sql = sprintf( "SELECT id,nome
      FROM ssm.ssm_registrazioni_combo_items
      WHERE idcombo='%s'", $args['id'] );
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $ar[] = $rec;
    }
    */

    $response->getBody()->write( json_encode( $arSelect['data'] ) );
      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');

    return $response;
  });


  // new item
  $group->put('/registrazioni_combo_items/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud ) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();
    $uuid = new UUID();

    $p = json_decode($request->getBody(), true);
    $arUpdate = array( "id" => $uuid->v4(),
      "idcombo" => $args['id'],
      "nome" => $p['nome'],
      "idstatus" => 1,
      "date_create" => "now()",
      "date_update" => "now()"
    );
    $ret = $Utils->dbSql( true, "ssm.ssm_registrazioni_combo_items", $arUpdate, "", "" );

    if( $ret['success'] == 1 ) {
      $arSelect = $Utils->dbSelect( [
        "log" => true,
        "select" => ["id", "nome"],
        "from" => "ssm.ssm_registrazioni_combo_items",
        "where" => [
          [
          "field" => "idcombo",
          "operator" => "=",
          "value" => $args['id'],
          ],
        ],
        "order" => "nome"
        ]
      );


      $response->getBody()->write( json_encode( $arSelect['data'] ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    }


    if( $ret['success'] == 1 ) {
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

  // Edit item
  $group->post('/registrazioni_combo_items/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud ) {
    $Utils = new Utils();
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $arUpdate = array(
      "nome" => $p['nome'],
      "date_update" => "now()"
    );
    $ret = $Utils->dbSql( false, "ssm.ssm_registrazioni_combo_items", $arUpdate, "id", $p['id'] );

    if( $ret['success'] == 1 ) {
      $arSelect = $Utils->dbSelect( [
        "log" => true,
        "select" => ["id", "nome"],
        "from" => "ssm.ssm_registrazioni_combo_items",
        "where" => [
          [
          "field" => "idcombo",
          "operator" => "=",
          "value" => $args['id'],
          ],
        ],
        "order" => "nome"
        ]
      );

      $response->getBody()->write( json_encode( $arSelect['data'] ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    }

    if( $ret['success'] == 1 ) {
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



  // new item
  $group->delete('/registrazioni_combo_items/{idscuola}/{idcombo}/{id}', function (Request $request, Response $response, $args) use ($crud ) {
    $db = new dataBase();
    $Utils = new Utils();

    $sql = sprintf( "DELETE FROM ssm.ssm_registrazioni_combo_items WHERE id='%s'", $db->real_escape_string( $args['id'] ) );
    $db->query( $sql );
    if( $db->error() == "" ) {
      $arSelect = $Utils->dbSelect( [
        "log" => true,
        "select" => ["id", "nome"],
        "from" => "ssm.ssm_registrazioni_combo_items",
        "where" => [
          [
          "field" => "idcombo",
          "operator" => "=",
          "value" => $args['idcombo'],
          ]
        ],
        "order" => "nome"
        ]
      );

      $response->getBody()->write( json_encode( $arSelect['data'] ) );
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



});


?>
