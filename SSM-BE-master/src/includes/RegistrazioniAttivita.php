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




$app->group('/registrazioni_attivita', function (RouteCollectorProxy $groupAttivita) use ($auth) {

  $data = [
    "table" => "ssm.ssm_registrazioni_attivita",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_registrazioni_attivita.id", "ssm.ssm_registrazioni_attivita.nome",
       "at.nome as tipo_attivita_text", "rt.nome as tipo_registrazione_text" ],
    "list_join" => [
      [
          "ssm.ssm_registrazioni_attivita_tipologie at",
          " at.id=ssm.ssm_registrazioni_attivita.idtipo_attivita "
      ],
      [
          "ssm.ssm_registrazioni_registrazioni_tipi rt",
          " rt.id=ssm.ssm_registrazioni_attivita.idtipo_registrazione "
      ]
    ]
  ];


  $crud = new CRUD( $data );

  // list
  $groupAttivita->get('/{idscuola}/{idcoorte}', function (Request $request, Response $response, $args) use ($auth, $crud) {

    $p = $request->getQueryParams();
    $log = new Logger();
    $log->log( "attivita/daaaa " . json_encode( $args ) );
    $p['_ssm.ssm_registrazioni_attivita.idstatus'] = "1";
    $p['_ssm.ssm_registrazioni_attivita.idscuola'] = $args['idscuola'];
    $p['_ssm.ssm_registrazioni_attivita.idcoorte'] = $args['idcoorte'];

    if( $p['s'] != "" ) {
      $p['multi_search'] = array(
        [
          "field" => "ssm.ssm_registrazioni_attivita.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%",
          "operatorAfter" => " OR "
        ],
        [
          "field" => "at.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        ]
      );
    }

    $res = $crud->record_list( $p );
    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  // get
  $groupAttivita->get('/{idscuola}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $res = $crud->record_get( $args['id'] )['data'][0];


      $res['opzione_note'] = $res['opzione_note'] == 1 ? true:false;
      $res['opzione_upload'] = $res['opzione_upload'] == 1 ? true:false;
      $res['opzione_protocollo'] = $res['opzione_protocollo'] == 1 ? true:false;

      $res['prestazioni'] = json_decode( $res['prestazioni'], true );

      $res['combo'] = json_decode( $res['combo'], true );
      $res['combo_implicite'] = json_decode( $res['combo_implicite'], true );

      $where = sprintf( "idscuola='%s' AND idstatus=1 and idtipo=2", $args['idscuola'] );
      $ar = array( "table" => "ssm.ssm_registrazioni_combo", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
      $res['combo_list'] = $Utils->_combo_list( $ar, true, "" );

      // tipologie attività
      $sql = sprintf( "SELECT at.id, concat( at.nome, ' - ', ss.nome) as text
        FROM ssm.ssm_registrazioni_attivita_tipologie at
        LEFT JOIN ssm.ssm_settori_scientifici ss ON ss.id=at.idsettore_scientifico
        WHERE at.idscuola='%s' AND idcoorte='%s' AND at.idstatus=1", $db->real_escape_string( $args['idscuola'] ), $db->real_escape_string( $args['idcoorte'] ) );
      $log->log($sql);

      $db = new dataBase();
      $art = array();
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        $art[] = $rec;
      }
      $res['attivita_tipologie_list'] = $art;
      $log->log( "attivita_tipologie_list - " . $sql . " " . json_encode( $art ) );


      /*
      $where = sprintf( "idscuola='%s' AND idstatus=1", $args['idscuola'] );
      $ar = array( "table" => "ssm.ssm_registrazioni_attivita_tipologie", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
      $res['attivita_tipologie_list'] = $Utils->_combo_list( $ar, true, "" );
      */

      // tipologie registrazioni
      $where = sprintf( "idstatus=1" );
      $ar = array( "table" => "ssm.ssm_registrazioni_registrazioni_tipi", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
      $res['registrazioni_tipi_list'] = $Utils->_combo_list( $ar, true, "" );

      $res['prestazioni_list'] = getPrestazioniScuola($args['idscuola']);


      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupAttivita->put('/{idscuola}/{idcoorte}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();
    $p = json_decode($request->getBody(), true);

    $p['idscuola'] = $args['idscuola'];
    $p['idcoorte'] = $args['idcoorte'];

    $p['opzione_note'] = ($p['opzione_note'] == "true") ? 1:0;
    $p['opzione_upload'] = ($p['opzione_upload'] == "true") ? 1:0;
    $p['opzione_protocollo'] = ($p['opzione_protocollo'] == "true") ? 1:0;

    $p['prestazioni'] = $p['prestazioni'] == "" ? "[]" : json_encode( $p['prestazioni'] );

    $retValidate = validate( "registrazioni_attivita", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $p['combo'] = json_encode( $p['combo'] );
    $p['combo_implicite'] = json_encode( $p['combo_implicite'] );

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
  $groupAttivita->post('/{idscuola}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "p:" . json_encode( $p ) );

    $p['combo'] = json_encode( $p['combo'] );
    $p['combo_implicite'] = json_encode( $p['combo_implicite'] );
    $p['opzione_note'] = ($p['opzione_note'] == "true") ? 1:0;
    $p['opzione_upload'] = ($p['opzione_upload'] == "true") ? 1:0;
    $p['opzione_protocollo'] = ($p['opzione_protocollo'] == "true") ? 1:0;

    $p['prestazioni'] = $p['prestazioni'] == "" ? "[]" : json_encode( $p['prestazioni'] );

    $retValidate = validate( "registrazioni_attivita", $p );
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
  $groupAttivita->delete('/{idscuola}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
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

function getPrestazioniScuola($idScuola) {
  $db = new dataBase();
  $sql = sprintf("SELECT ci.id, ci.nome as text
      FROM ssm.ssm_registrazioni_combo_items ci
      LEFT JOIN ssm.ssm_registrazioni_combo co ON co.id=ci.idcombo
      WHERE co.idscuola='%s' AND co.idtipo=1 AND co.idstatus=1 AND ci.idstatus=1
      ORDER BY ci.nome", $db->real_escape_string( $idScuola ) );
  $db->query($sql);
  while ($rec = $db->fetchassoc()) {
      $res[] = $rec;
  }
  return $res;
}


?>
