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


$app->group('/registrazioni_attivita_np', function (RouteCollectorProxy $groupAttivitaNP) use ($auth, $args) {


  $data = [
    "table" => "ssm.ssm_scuole_attivita_np",
    "id" => "id",
    "sort" => "ssm.ssm_scuole_attivita_np.nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_scuole_attivita_np.id", "ssm.ssm_scuole_attivita_np.nome_attivita", "ssm.ssm_scuole_attivita_np.calendar"
    ]
  ];


  $crud = new CRUD( $data );



  // list
  $groupAttivitaNP->get('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($auth, $crud) {

      $p = $request->getQueryParams();
      $p['_ssm.ssm_scuole_attivita_np.idstatus'] = 1;
      $p['_idscuola_specializzazione'] = $args['idscuola_specializzazione'];

      if( $p['s'] != "" ) {
        $p['search'] = array(
          "field" => "ssm.ssm_scuole_attivita_np.nome_attivita",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        );
      }

      $res = $crud->record_list( $p );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });



  // get
  $groupAttivitaNP->get('/{idscuola_specializzazione}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $Utils = new Utils();
    $log = new Logger();

    $log->log( "get - " . $args['idscuola_specializzazione'] . " - " . $args['id'] );
    $res = $crud->record_get( $args['id'] )['data'][0];
    if( !$res )
      $res = [];

    $res['calendar'] = ($res['calendar'] == '1' ? true : false);

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });


  // new
  $groupAttivitaNP->put('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "put - " . json_encode( $args ) . " - " . json_encode( $p ) );

    $p['idscuola_specializzazione'] = $args['idscuola_specializzazione'];


    $retValidate = validate( "ssm.ssm_scuole_attivita_np", $p );
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
  $groupAttivitaNP->post('/{idscuola_speciaizzazione}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);


    $retValidate = validate( "ssm.ssm_scuole_attivita_np", $p );
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
  $groupAttivitaNP->delete('/{idscuola_specializzazione}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina ssm.ssm_scuole_attivita_np " . $args['id'] );
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
