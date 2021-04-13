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


/** Crud che gestisce la relazione tra scuola e unità operative di essa */


$app->group('/pds_tipologie', function (RouteCollectorProxy $groupPdsTipologie) use ($auth) {

  $data = [
    "table" => "ssm_pds_tipologie",
    "id" => "id",
    "sort" => "ssm_pds_tipologie.nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm_pds_tipologie.id", "ssm_pds_tipologie.nome", "cl.nome as classe_nome", "ar.nome as area_nome" ],
    "list_join" => [
      [
          "ssm.ssm_pds_classi cl",
          " cl.id=ssm_pds_tipologie.idclasse "
      ],
      [
          "ssm.ssm_pds_aree ar",
          " ar.id=cl.idarea "
      ]
    ]
  ];


  $crud = new CRUD( $data );



  // list
  $groupPdsTipologie->get('', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $p['_ssm_pds_tipologie.idstatus'] = 1;

      $res = $crud->record_list( $p );
      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $groupPdsTipologie->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $res = $crud->record_get( $args['id'] )['data'][0];
      if( !$res )
        $res = [];

      $ar = array( "table" => "ssm.ssm_pds_classi", "value" => "id", "text" => "nome", "order" => "nome" );
      $res['classi_list'] = $Utils->_combo_list($ar, true, "");

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupPdsTipologie->put('', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $log->log( "new - " . json_encode( $p ) );


    $retValidate = validate( "ssm_pds_tipologie", $p );
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
  $groupPdsTipologie->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "update - " . json_encode( $p ) );

    $p = json_decode($request->getBody(), true);


    $retValidate = validate( "ssm_pds_tipologie", $p );
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
  $groupPdsTipologie->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina: " . json_encode( $args ) );

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
