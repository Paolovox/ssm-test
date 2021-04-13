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


$app->group('/scuole_unita', function (RouteCollectorProxy $groupScuole) use ($auth) {

  $data = [
    "table" => "ssm.ssm_scuole_unita",
    "id" => "ssm.ssm_scuole_unita.id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_scuole_unita.id", "un.nome", "ti.nome as tipologia_sede", "pr.nome as presidio",
      "ssm.ssm_scuole_unita.idunita", "ssm.ssm_scuole_unita.idunita_text" ],
    "list_join" => [
      [
          "ssm.ssm_unita_operative un",
          " un.id=ssm.ssm_scuole_unita.idunita "
      ],
      [
          "ssm.ssm_presidi pr",
          " pr.id=un.idpresidio "
      ],
      [
          "ssm.ssm_unita_tipologie ti",
          " ssm.ssm_scuole_unita.idtipologia_sede=ti.id "
      ]
    ]

  ];
  $crud = new CRUD( $data );



  // list
  $groupScuole->get('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $p = $request->getQueryParams();
      $p['_idscuola_specializzazione'] = $args['idscuola_specializzazione'];
      $p['_ssm.ssm_scuole_unita.idstatus'] = 1;

      if( $p['s'] != "" ) {
        $p['multi_search'] = array(
          [
            "field" => "ssm.ssm_scuole_unita.idunita_text",
            "operator" => " LIKE ",
            "value" => "%" . $p['s'] . "%",
            "operatorAfter" => " OR "
          ],
          [
            "field" => "un.nome",
            "operator" => " LIKE ",
            "value" => "%" . $p['s'] . "%",
            "operatorAfter" => " OR "
          ],
          [
            "field" => "pr.nome",
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
  $groupScuole->get('/{idscuola_specializzazione}/{idunita_relazione}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $res = $crud->record_get( $args['idunita_relazione'] )['data'][0];
      if( !$res )
        $res = [];

//      $res['nome_scuola'] = nome_scuola_get( $res['idscuola'] );
      $sUnita = unita_scuole_get( $res['idscuola_specializzazione'] );
      $log->log( "sUnita: " . $sUnita . " -  res:" . json_encode( $res ) );

      $sWhere = sprintf( " id NOT IN (%s) ", $sUnita );

      $res['idunita'] = array(
        "id" => $res['idunita'],
        "text" => $res['idunita_text']
      );

      /*
      $ar = array(
        "table" => "ssm.ssm_unita_operative uo
          left join ssm.ssm_presidi pr ON pr.id=uo.idpresidio ",
        "value" => "uo.id",
        "text" => "concat(uo.nome, ' - ', pr.nome)",
        "order" => "concat(uo.nome, ' - ', pr.nome)",
        "where" => "uo.idstatus=1 AND pr.idstatus=1" );
      $res['unita_list'] = $Utils->_combo_list( $ar, true, "" );
*/

      $ar = array( "table" => "ssm.ssm_unita_tipologie", "value" => "id", "text" => "nome", "order" => "ordine" );
      $res['tipologie_sede_list'] = $Utils->_combo_list( $ar, true, "" );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupScuole->put('/{idscuola_specializzazione}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $p['idscuola_specializzazione'] = $args['idscuola_specializzazione'];
    $log->log( "new - " . json_encode( $p ) );

    $idunita = $p['idunita'];
    unset( $p['idunita'] );
    $p['idunita'] = $idunita['id'];
    $p['idunita_text'] = $idunita['text'];



    $retValidate = validate( "unita_relazione", $p );
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
  $groupScuole->post('/{idscuola_specializzazione}/{idunita_relazione}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "update - " . json_encode( $p ) );

    $p = json_decode($request->getBody(), true);
    $p['idscuola_specializzazione'] = $args['idscuola_specializzazione'];

    $idunita = $p['idunita'];
    unset( $p['idunita'] );
    $p['idunita'] = $idunita['id'];
    $p['idunita_text'] = $idunita['text'];


    $retValidate = validate( "unita_relazione", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crud->record_update( $args['idunita_relazione'], $p );
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
  $groupScuole->delete('/{idscuola_specializzazione}/{idunita_relazione}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina: " . json_encode( $args ) );

    $res = $crud->record_delete( $args['idunita_relazione'] );
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


  // prestazioni autocomplete
  $groupScuole->get('/unita_autocomplete/{idscuola_specializzazione}/{find}', function (Request $request, Response $response, $args) use ($crud) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $log->log( "unita_autocomplete - " . $args['find'] );

    $sWhere = sprintf( "'%%%s%%'", $args['find'] );

    $ar = array(
      "table" => "ssm.ssm_unita_operative uo
        left join ssm.ssm_presidi pr ON pr.id=uo.idpresidio
        left join ssm.ssm_aziende a ON a.id=pr.idazienda ",
      "value" => "uo.id",
      "text" => "concat(uo.nome, ' - ', pr.nome, ' - ', a.nome)",
      "order" => "concat(uo.nome, ' - ', pr.nome, ' - ', a.nome)",
      "where" => "uo.idstatus=1 AND pr.idstatus=1 AND uo.nome LIKE " . $sWhere );
    $res = $Utils->_combo_list( $ar, true, "" );

    $response->getBody()->write( json_encode( $res ) );
      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');

  });

});


function unita_scuole_get( $idscuola ) {
  $db = new dataBase();
  $sql = sprintf( "SELECT idunita from ssm.ssm_scuole_unita WHERE idscuola_specializzazione='%s' AND idstatus=1 ORDER BY nome", $db->real_escape_string( $idscuola ) );
  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {
    $ar[] = sprintf( "'%s'", $rec['idunita'] );
  }

  return implode( ",", $ar );
}




?>
