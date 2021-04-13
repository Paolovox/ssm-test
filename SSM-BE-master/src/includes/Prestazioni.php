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




$app->group('/prestazioni', function (RouteCollectorProxy $groupPrestazioni) use ($auth) {

  $data = [
    "table" => "ssm.ssm_registrazioni_combo_items",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
  ];


  $crud = new CRUD( $data );

  // list
  $groupPrestazioni->get('/{idscuola}', function (Request $request, Response $response, $args) use ($auth, $crud) {
    $log = new Logger();
    $db = new dataBase();

    $p = $request->getQueryParams();
    if( $p['s'] != "" ) {
      $p['search'] = array(
        "field" => "ssm.ssm_registrazioni_combo_items.nome",
        "operator" => " LIKE ",
        "value" => "%" . $p['s'] . "%"
      );
    }
    $log->log( "/prestazioni list " . $args['idscuola'] . " - " . json_encode( $p ) );

    $sql = sprintf( "SELECT id FROM ssm.ssm_registrazioni_combo WHERE idscuola='%s' AND idtipo=1 ORDER BY nome", $db->real_escape_string( $args['idscuola'] ) );
    $db->query( $sql );
    $res = $db->fetchassoc();
    $log->log( "prestazioni - combo " . $sql );

    $p['_ssm.ssm_registrazioni_combo_items.idstatus'] = "1";
    $p['_ssm.ssm_registrazioni_combo_items.idcombo'] = $res['id'];

    $log->log( "prestazioni - combo - " . json_encode( $p ) );

    $res = $crud->record_list( $p );
    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });


  // get
  $groupPrestazioni->get('/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $res = $crud->record_get( $args['id'] )['data'][0];

      if (!$res)  {
        $res = [];
      }

      // $where = sprintf( "idscuola='%s'", $args['idscuola'] );
      // $ar = array( "table" => "ssm.ssm_registrazioni_combo", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
      // $res['combo_list'] = $Utils->_combo_list( $ar, true, "" );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupPrestazioni->put('/{idscuola}', function (Request $request, Response $response, $args) use ($crud ) {
    $db = new dataBase();
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $sql = sprintf( "SELECT id FROM ssm.ssm_registrazioni_combo WHERE idscuola='%s' AND idtipo=1 ORDER BY nome", $db->real_escape_string( $args['idscuola'] ) );
    $db->query( $sql );
    $resCombo = $db->fetchassoc();

    $p['idcombo'] = $resCombo['id'];

    $log->log( "prestazioni new:" . $sql );
    $log->log( "Prestazioni new: " . json_encode( $p ) );

    $retValidate = validate( "prestazioni", $p );
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
        $response->getBody()->write( "Errore aggiornamento" . json_encode( $res ) );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }

    return $response;
  });


  //update
  $groupPrestazioni->post('/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "prestazioni", $p );
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
  $groupPrestazioni->delete('/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud) {
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
