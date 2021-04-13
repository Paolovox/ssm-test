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


$app->group('/pds_classi', function (RouteCollectorProxy $groupPdsClassi) use ($auth) {

  $data = [
    "table" => "ssm.ssm_pds_classi",
    "id" => "id",
    "sort" => "ssm.ssm_pds_classi.nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_pds_classi.id", "ssm.ssm_pds_classi.nome", "sc.nome as nome_area" ],
    "list_join" => [
      [
          "ssm.ssm_pds_aree sc",
          " sc.id=ssm.ssm_pds_classi.idarea "
      ]
    ]
  ];


  $crud = new CRUD( $data );



  // list
  $groupPdsClassi->get('', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $p['_ssm.ssm_pds_classi.idstatus'] = 1;

      $res = $crud->record_list( $p );
      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $groupPdsClassi->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $res = $crud->record_get( $args['id'] )['data'][0];
      if( !$res )
        $res = [];

      $ar = array( "table" => "ssm.ssm_pds_aree", "value" => "id", "text" => "nome", "order" => "nome" );
      $res['aree_list'] = $Utils->_combo_list($ar, true, "");

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupPdsClassi->put('', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $log->log( "new - " . json_encode( $p ) );


    $retValidate = validate( "pds_classi", $p );
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
  $groupPdsClassi->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "update - " . json_encode( $p ) );

    $p = json_decode($request->getBody(), true);


    $retValidate = validate( "pds_classi", $p );
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
  $groupPdsClassi->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
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
