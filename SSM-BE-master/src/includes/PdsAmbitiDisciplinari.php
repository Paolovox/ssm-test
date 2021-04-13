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


$app->group('/pds_ambiti_disciplinari', function (RouteCollectorProxy $groupPdsAmbitiDisciplinari) use ($auth) {

  $data = [
    "table" => "ssm.ssm_pds_ambiti_disciplinari",
    "id" => "id",
    "sort" => "ssm.ssm_pds_ambiti_disciplinari.nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_pds_ambiti_disciplinari.id", "ssm.ssm_pds_ambiti_disciplinari.nome", "saf.nome as attivita_formativa_text", "cla.nome as classe_text" ],
    "list_join" => [
      [
          "ssm.ssm_pds_attivita_formative saf",
          " saf.id=ssm.ssm_pds_ambiti_disciplinari.idtipologia_attivita_formativa "
      ],
      [
          "ssm.ssm_pds_classi cla",
          " cla.id=ssm.ssm_pds_ambiti_disciplinari.idclasse "
      ]
    ]

  ];


  $crud = new CRUD( $data );



  // list
  $groupPdsAmbitiDisciplinari->get('', function (Request $request, Response $response, $args) use ($auth, $crud) {
    $log = new Logger();

    $p = $request->getQueryParams();
    $log->log( "Ambiti disciplinari - " . json_encode( $p ) );

    $p['_ssm.ssm_pds_ambiti_disciplinari.idstatus'] = 1;

    if( $p['s'] != "" ) {
      $p['search'] = array(
        "field" => "ssm.ssm_pds_ambiti_disciplinari.nome",
        "operator" => " LIKE ",
        "value" => "%" . $p['s'] . "%"
      );
    }


    $res = $crud->record_list( $p );
    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  // get
  $groupPdsAmbitiDisciplinari->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $res = $crud->record_get( $args['id'] )['data'][0];
      if( !$res )
        $res = [];

      $res['idsettori_scientifici'] = json_decode( $res['idsettori_scientifici'], true );

      $ar = array( "table" => "ssm.ssm_settori_scientifici", "value" => "id", "text" => "nome", "order" => "nome", "where" => "idstatus=1" );
      $res['settori_scientifici_list'] = $Utils->_combo_list($ar, true, "");

      $ar = array( "table" => "ssm.ssm_pds_attivita_formative", "value" => "id", "text" => "nome", "order" => "nome", "where" => "idstatus=1" );
      $res['attivita_formative_list'] = $Utils->_combo_list($ar, true, "");

      $ar = array( "table" => "ssm.ssm_pds_classi", "value" => "id", "text" => "nome", "order" => "nome", "where" => "idstatus=1" );
      $res['pds_classi_list'] = $Utils->_combo_list($ar, true, "");


      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupPdsAmbitiDisciplinari->put('', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $p['idsettori_scientifici'] = json_encode( $p['idsettori_scientifici'] );

    $log->log( "new - " . json_encode( $p ) );


    $retValidate = validate( "ssm.ssm_pds_ambiti_disciplinari", $p );
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
  $groupPdsAmbitiDisciplinari->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "update - " . json_encode( $p ) );

    $p = json_decode($request->getBody(), true);
    $p['idsettori_scientifici'] = json_encode( $p['idsettori_scientifici'] );


    $retValidate = validate( "ssm.ssm_pds_ambiti_disciplinari", $p );
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
  $groupPdsAmbitiDisciplinari->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
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
