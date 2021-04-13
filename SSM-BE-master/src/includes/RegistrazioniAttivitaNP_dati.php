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


$app->group('/registrazioni_attivita_np_dati', function (RouteCollectorProxy $groupAttivitaNP_dati) use ($auth, $args) {


  $data = [
    "table" => "ssm.ssm_scuole_attivita_np_dati",
    "id" => "id",
    "sort" => "ssm.ssm_scuole_attivita_np_dati.nome_campo",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_scuole_attivita_np_dati.id", "ssm.ssm_scuole_attivita_np_dati.nome_campo",
      "tc.tipo_campo as tipo_text" ],
    "list_join" => [
      [
          "ssm.ssm_scuole_attivita_np_tipi_campo tc",
          " tc.id=ssm.ssm_scuole_attivita_np_dati.idtipo_campo "
      ],
    ]
  ];


  $crud = new CRUD( $data );



  // list
  $groupAttivitaNP_dati->get('/{idattivita}', function (Request $request, Response $response, $args) use ($auth, $crud) {

      $p = $request->getQueryParams();
      $p['_ssm.ssm_scuole_attivita_np_dati.idstatus'] = 1;
      $p['_idattivita'] = $args['idattivita'];

      if( $p['s'] != "" ) {
        $p['multi_search'] = array(
          [
            "field" => "ssm.ssm_scuole_attivita_np_dati.nome_campo",
            "operator" => " LIKE ",
            "value" => "%" . $p['s'] . "%",
            "operatorAfter" => " OR "
          ]
        );
      }

      $res = $crud->record_list( $p );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });



  // get
  $groupAttivitaNP_dati->get('/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $Utils = new Utils();
    $log = new Logger();

    $log->log( "get - " . $args['ssm.ssm_scuole_attivita_np_dati'] . " - " . $args['id'] );
    $res = $crud->record_get( $args['id'] )['data'][0];
    if( !$res )
      $res = [];

    $ar = array( "table" => "ssm.ssm_scuole_attivita_np_tipi_campo", "value" => "id", "text" => "tipo_campo", "order" => "id" );
    $res['tipi_campo_list'] = $Utils->_combo_list( $ar, true, "" );

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });


  // new
  $groupAttivitaNP_dati->put('/{idattivita}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "put - " . json_encode( $args ) . " - " . json_encode( $p ) );

    $p['idattivita'] = $args['idattivita'];


    $retValidate = validate( "ssm.ssm_scuole_attivita_np_dati", $p );
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
  $groupAttivitaNP_dati->post('/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);


    $retValidate = validate( "ssm.ssm_scuole_attivita_np_dati", $p );
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
  $groupAttivitaNP_dati->delete('/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina ssm.ssm_scuole_attivita_np_dati " . $args['id'] );
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
