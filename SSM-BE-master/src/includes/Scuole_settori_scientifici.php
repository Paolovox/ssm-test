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


$app->group('/scuole_settori', function (RouteCollectorProxy $groupScuoleSettori) use ($auth) {

  $data = [
    "table" => "ssm.ssm_scuole_settori_scientifici",
    "id" => "ssm.ssm_scuole_settori_scientifici.id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_scuole_settori_scientifici.id", "sscn.nome", "sta.nome_tipologia",
      "if(ssm.ssm_scuole_settori_scientifici.obbligatorio=1,'Sì','No') as obbligatorio" ],
    "list_join" => [
      [
          "ssm.ssm_settori_scientifici sscn",
          " sscn.id=ssm.ssm_scuole_settori_scientifici.idsettore "
      ],
      [
          "ssm.ssm_tipologie_attivita sta",
          " sta.id=ssm.ssm_scuole_settori_scientifici.idprofessionalizzante "
      ]
    ]

  ];
  $crud = new CRUD( $data );



  // list
  $groupScuoleSettori->get('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $p['_idscuola_specializzazione'] = $args['idscuola_specializzazione'];
      $p['_ssm.ssm_scuole_settori_scientifici.idstatus'] = 1;

      $res = $crud->record_list( $p );
      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $groupScuoleSettori->get('/{idscuola_specializzazione}/{idsettore_relazione}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $res = $crud->record_get( $args['idsettore_relazione'] )['data'][0];
      if( !$res )
        $res = [];

      $ar = array( "table" => "ssm.ssm_settori_scientifici", "value" => "id", "text" => "nome", "order" => "nome" );
      $res['settori_scientifici_list'] = $Utils->_combo_list($ar, true, "");

      $ar = array( "table" => "ssm.ssm_tipologie_attivita", "value" => "id", "text" => "nome_tipologia", "order" => "id" );
      $res['professionalizzante_list'] = $Utils->_combo_list($ar, true, "");

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupScuoleSettori->put('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $p['idscuola_specializzazione'] = $args['idscuola_specializzazione'];

    $log->log( "new - " . json_encode( $p ) );


    $retValidate = validate( "settore_scientifico_relazione", $p );
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
  $groupScuoleSettori->post('/{idscuola_specializzazione}/{idsettore_relazione}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "update - " . json_encode( $p ) );

    $p = json_decode($request->getBody(), true);
    $p['idscuola_specializzazione'] = $args['idscuola_specializzazione'];


    $retValidate = validate( "settore_scientifico_relazione", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crud->record_update( $args['idsettore_relazione'], $p );
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
  $groupScuoleSettori->delete('/{idscuola_specializzazione}/{idsettore_relazione}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina: " . json_encode( $args ) );

    $res = $crud->record_delete( $args['idsettore_relazione'] );
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
