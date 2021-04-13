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


$app->group('/pds_obiettivi', function (RouteCollectorProxy $groupObiettivi) use ($auth, $args) {


  $data = [
    "table" => "ssm.ssm_scuole_pds_obiettivi",
    "id" => "id",
    "sort" => "ssm.ssm_scuole_pds_obiettivi.nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_scuole_pds_obiettivi.id", "ssm.ssm_scuole_pds_obiettivi.cfu", "am.nome as ambito_nome"],
    "list_join" => [
      [
          "ssm.ssm_pds_ambiti_disciplinari am",
          " am.id=ssm.ssm_scuole_pds_obiettivi.idambito "
      ]
    ]
  ];

/*

*/

  $crud = new CRUD( $data );



  // list
  $groupObiettivi->get('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($auth, $crud) {

      $p = $request->getQueryParams();
      $p['_ssm.ssm_scuole_pds_obiettivi.idstatus'] = 1;
      $p['_idscuola_specializzazione'] = $args['idscuola_specializzazione'];

      if( $p['s'] != "" ) {
        $p['search'] = array(
          "field" => "am.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        );
      }

      $res = $crud->record_list( $p );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $groupObiettivi->get('/{idscuola_specializzazione}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $log->log( "get - " . $args['idscuola_specializzazione'] . " - " . $args['id'] );

      $res = $crud->record_get( $args['id'] )['data'][0];
      $res['idtipologie_attivita'] = json_decode( $res['idtipologie_attivita'] );

      $arSelect = $Utils->dbSelect( [
        "log" => true,
        "select" => ["sc.idpds_classe"],
        "from" => "ssm.ssm_scuole_atenei",
        "where" => [
          [
          "field" => "ssm.ssm_scuole_atenei.id",
          "operator" => "=",
          "value" => $args['idscuola_specializzazione'],
          "operatorAfter" => " AND "
          ],
          [
          "field" => "ssm.ssm_scuole_atenei.idstatus",
          "operator" => "=",
          "value" => 1
          ]
        ],
        "join" => [
          [
            "ssm.ssm_scuole sc",
            "sc.id=ssm.ssm_scuole_atenei.idscuola"
          ]
        ]
      ]);


      $log->log( "arSelect " . json_encode( $arSelect ) );


      // prendo la tipologia della scuola
      // prendo gli ambiti disciplinari di quella tipologia

      $ar = array( "table" => "ssm.ssm_tipologie_attivita", "value" => "id", "text" => "nome_tipologia", "order" => "id" );
      $res['tipologie_attivita_list'] = $Utils->_combo_list( $ar, true, "" );


      $where = sprintf( "idstatus=1 AND idclasse='%s'", $arSelect['data'][0]['idpds_classe'] );
      $ar = array( "table" => "ssm.ssm_pds_ambiti_disciplinari", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
      $res['ambiti_disciplinari_list'] = $Utils->_combo_list( $ar, true, "" );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupObiettivi->put('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "put - " . json_encode( $args ) . " - " . json_encode( $p ) );

    $p['idscuola_specializzazione'] = $args['idscuola_specializzazione'];
    $p['idtipologie_attivita'] = json_encode( $p['idtipologie_attivita'] );

    $retValidate = validate( "ssm.ssm_scuole_pds_obiettivi", $p );
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
  $groupObiettivi->post('/{idscuola_speciaizzazione}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $p['idtipologie_attivita'] = json_encode( $p['idtipologie_attivita'] );

    $retValidate = validate( "ssm.ssm_scuole_pds_obiettivi", $p );
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
  $groupObiettivi->delete('/{idscuola_specializzazione}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina Obiettivo " . $args['id'] );
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
