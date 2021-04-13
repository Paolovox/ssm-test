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


$app->group('/job_tabella_set', function (RouteCollectorProxy $groupJobColonne) use ($auth) {

  $data = [
    "table" => "jobdescription_colonne",
    "id" => "id",
    "sort" => "nome_colonna",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "id", "nome_colonna", "date_format( date_update, '%d-%m-%Y') as data_aggiornamento_text" ],
  ];


  $crud = new CRUD( $data );

  // list
  $groupJobColonne->get('/{idtabella}', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $db = new dataBase();
      $log = new Logger();

      $user = $request->getAttribute('user');
      $p = $request->getQueryParams();

      $q[] = "jobdescription_colonne.idstatus= 1";
      $q[] = sprintf( "jobdescription_colonne.idtabella='%s'", $args['idtabella'] );

      if( $p['s'] != "" ) {
        $q[] = sprintf( "jobdescription_tabelle.nome_colonna LIKE '%%%s%%'", $db->real_escape_string( $p['s'] ) );
      }

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $groupJobColonne->get('/{idtabella}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $user = $request->getAttribute('user');
      $res = $crud->record_get( $args['id'] )['data'][0];

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupJobColonne->put('/{idtabella}', function (Request $request, Response $response) use ($crud ) {

    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $user = $request->getAttribute('user');

    $p['idscuola'] = $user['idscuola'];
    $p['idtabella'] = $args['idtabella'];

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
  $groupJobColonne->post('/{idtabella}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "job_colonne", $p );
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
  $groupJobColonne->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
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

})->add($authMW);

