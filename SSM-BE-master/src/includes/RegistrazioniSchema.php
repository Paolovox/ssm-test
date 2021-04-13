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


$app->group('/registrazioni_schema', function (RouteCollectorProxy $groupSchema) use ($auth) {

  $data = [
    "log" => true,
    "table" => "ssm.ssm_registrazioni_schema",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_registrazioni_schema.id", "ra.nome as attivita_text", "ci.nome as prestazione_text", "ci.id as idprestazione", "ssm.ssm_registrazioni_schema.combo" ],
    "list_join" => [
      [
          "ssm.ssm_registrazioni_attivita ra",
          " ra.id=ssm.ssm_registrazioni_schema.idattivita "
      ],
      [
          "ssm.ssm_registrazioni_combo_items ci",
          " ci.id=ssm.ssm_registrazioni_schema.idprestazione "
      ]
    ]
  ];

  $crud = new CRUD( $data );

  // list
  $groupSchema->get('/{idscuola}/{idattivita}', function (Request $request, Response $response, $args) use ($auth, $crud) {
    $log = new Logger();
    $db = new dataBase();

    $p = $request->getQueryParams();

    if( $p['srt'] == "nome" )
      $p['srt'] = "ci.nome";

    if( $p['s'] != "" ) {
      $p['search'] = array(
        "field" => "ci.nome",
        "operator" => " LIKE ",
        "value" => "%" . $p['s'] . "%"
      );
    }

    $p['_ssm.ssm_registrazioni_schema.idstatus'] = "1";
    $p['_ssm.ssm_registrazioni_schema.idscuola'] = $args['idscuola'];
    $p['_ssm.ssm_registrazioni_schema.idattivita'] = $args['idattivita'];

    $res = $crud->record_list( $p );

    // ripercorre tutta la selezione per rendere visibili
    // le descrizioni delle combo selezionate
    for( $n=0; $n<sizeof( $res['rows'] ); $n++ ) {
      $combos = json_decode( $res['rows'][$n]['combo'], 1 );
      unset( $idvalue );
      // $log->log( "Combo: " . json_encode( $combos ) );
      foreach( $combos as $v ) {
        $v['idvalue'] = $db->real_escape_string( $v['idvalue']);
        $idvalue[] = $v['idvalue'];
      }

      //$log->log( "idvalue " . json_encode( $idvalue ) );

      // TODO: SQL_INJECTION_TEST
      $sql = sprintf( "SELECT co.nome as co_nome,ci.nome as ci_nome
        FROM ssm.ssm_registrazioni_combo_items ci
        LEFT JOIN ssm.ssm_registrazioni_combo co ON co.id=ci.idcombo
        WHERE ci.id IN ('%s')", implode( "','", $idvalue ) );
      $db->query( $sql );
      $o = "";
      while( $rec = $db->fetchassoc() ) {
        if( $o != "" )
          $o .= ", ";
        $o .= $rec['co_nome'] . " -> " . $rec['ci_nome'];
      }
      //$log->log( "VALUES: " . $sql );
      //$log->log( "VALUES OUT: " . $o );

      $res['rows'][$n]['combo_text'] = $o;
    }

    //$log->log( "registrazioni_schema - list " . json_encode( $res ) );

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  // get
  $groupSchema->get('/{idscuola}/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $res = $crud->record_get( $args['id'] )['data'][0];
      if( $res == "" )
        $res = [];

      $log->log( "GET /attivita" . json_encode( $res ) );

      $combo = json_decode( $res['combo'], true );
      foreach( $combo as $k => $v ) {
        $res[$v['idcombo']] = $v['idvalue'];
      }

      $combo = json_decode( $res['combo_implicite'], true );
      foreach( $combo as $k => $v ) {
        $res[$v['idcombo']] = $v['idvalue'];
      }

      unset( $res['combo'] );

      /*
      $where = sprintf( "idstatus=1 AND idscuola='%s'", $args['idscuola'] );
      $ar = array( "table" => "ssm.ssm_registrazioni_attivita", "value" => "id", "text" => "nome", "order" => "nome", "where" => $where );
      $res['attivita_list'] = $Utils->_combo_list( $ar, true, "" );
      */
      $sql = sprintf("SELECT prestazioni FROM ssm.ssm_registrazioni_attivita WHERE id = '%s' AND idstatus=1", $db->real_escape_string($args['idattivita'] ) );
      $db->query($sql);
      $prestazioni = json_decode($db->fetchassoc()['prestazioni'], true);
      $prestazioni = implode("','", $prestazioni);

      // lista prestazioni
      $sql = sprintf( "SELECT ci.id, ci.nome as text
        FROM ssm.ssm_registrazioni_combo_items ci
        LEFT JOIN ssm.ssm_registrazioni_combo co ON co.id=ci.idcombo
        WHERE co.idscuola='%s' AND co.idtipo=1 AND co.idstatus=1 AND ci.idstatus=1 AND ci.id in ('%s')
        ORDER BY ci.nome",
          $db->real_escape_string( $args['idscuola'] ), $prestazioni );
      $log->log( "PRESTAZIONI: " . $sql );
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        $res['prestazioni_list'][] = $rec;
      }



      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });




  // new
  $groupSchema->put('/{idscuola}/{idattivita}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "/registrazioni_schema/idscuola - " . json_encode( $p ) );


    $retValidate = validate( "registrazioni_schema", $ar );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $prestazioni_list = $p['idprestazione'];
    $idattivita = $p['idattivita'];
    unset( $p['idprestazione'] );
    unset( $p['idattivita'] );
    foreach( $p as $k => $v ) {
      $tipo = "E";
      if( substr( $k, 0, 4 ) == "imp_" )
        $tipo = "I";

      $ar['combo'][] = array( "idcombo" => $k, "idvalue" => $v, "tipo" => $tipo );
    }

    $ar['combo'] = json_encode( $ar['combo'] );
    $ar['idscuola'] = $args['idscuola'];
    $ar['idattivita'] = $args['idattivita'];

    foreach( $prestazioni_list as $prestazione ) {
      $ar["idprestazione"] = $prestazione;
      $res = $crud->record_new( $ar );
    }

    $log->log( "prestazioni new - " . json_encode( $ar ) );


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
  $groupSchema->post('/{idscuola}/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "update - " . json_encode( $p ) );

    $ar = array(
      "idscuola" => $args['idscuola'],
      "idprestazione" => $p['idprestazione'],
      "idattivita" => $args['idattivita']
    );

    unset( $p['idprestazione'] );
    unset( $p['idattivita'] );

    foreach( $p as $k => $v ) {
      $tipo = "E";
      if( substr( $k, 0, 4 ) == "imp_" )
        $tipo = "I";

      $ar['combo'][] = array( "idcombo" => $k, "idvalue" => $v, "tipo" => $tipo );
    }
    $ar['combo'] = json_encode( $ar['combo'] );


    $retValidate = validate( "registrazioni_schema", $ar );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crud->record_update( $args['id'], $ar );
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
  $groupSchema->delete('/{idscuola}/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud) {
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


// prestazioni autocomplete
$app->get('/prestazioni_autocomplete/{idscuola}/{find}', function (Request $request, Response $response, $args) use ($crud) {
  $Utils = new Utils();
  $log = new Logger();
  $db = new dataBase();

  $log->log( "prestazioni_autocomplete - " . $args['find'] );

  $sql = sprintf( "SELECT ci.id, ci.nome as text
    FROM ssm.ssm_registrazioni_combo_items ci
    LEFT JOIN ssm.ssm_registrazioni_combo co ON co.id=ci.idcombo
    WHERE co.idscuola='%s' AND co.idtipo=1 AND ci.nome LIKE '%%%s%%'",
      $db->real_escape_string( $args['idscuola'] ), $db->real_escape_string( $args['find'] ) );
  $log->log( "PRESTAZIONI: " . $sql );
  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {
    $res[] = $rec;
  }

  $response->getBody()->write( json_encode( $res ) );
    return $response
      ->withStatus(200)
      ->withHeader('Content-Type', 'application/json');

});

// get
$app->get('/attivita_combo/{idattivita}', function (Request $request, Response $response, $args) use ($crud) {
  $Utils = new Utils();
  $log = new Logger();

  // seleziona l'attivita
  $arSelectAttivita = $Utils->dbSelect( [
    "log" => true,
    "select" => ["combo", "combo_implicite"],
    "from" => "ssm.ssm_registrazioni_attivita",
    "where" => [
      [
      "field" => "id",
      "operator" => "=",
      "value" => $args['idattivita'],
      ]
    ]
  ]);


  // seleziona tutte le combo dell'attivita selezionata
  $combos = json_decode( $arSelectAttivita['data'][0]['combo'], true );

  if( $combos ) {
    // seleziona l'attivita
    $arSelect = $Utils->dbSelect( [
      "log" => true,
      "select" => ["*"],
      "from" => "ssm.ssm_registrazioni_combo",
      "where" => [
        [
        "field" => "id",
        "operator" => "IN",
        "value" => $combos,
        "operatorAfter" => "AND"
        ],
        [
        "field" => "idtipo",
        "operator" => "=",
        "value" => 2,
        "operatorAfter" => "AND"
        ],
        [
        "field" => "idstatus",
        "operator" => "=",
        "value" => 1,
        ]
      ]
    ]);


    $items['esplicite'] = array();
    foreach( $arSelect['data'] as $k => $v ) {

      $where = sprintf( "ci.idcombo='%s' AND co.idtipo=2", $v['id'] );
      $ar = array( "table" => "ssm.ssm_registrazioni_combo_items ci LEFT JOIN ssm.ssm_registrazioni_combo co ON co.id=ci.idcombo", "value" => "ci.id", "text" => "ci.nome", "order" => "ci.nome", "where" => $where );
      $items['esplicite'][] = array( "nome" => $v['nome'], "id" => $v['id'], "opzioni" => $Utils->_combo_list( $ar, true, "" ) );

    }

    $log->log( "Esplicite: " . $where . " - " . json_encode( $items ) );
  }

  // seleziona tutte le combo dell'attivita selezionata

  $combos = json_decode( $arSelectAttivita['data'][0]['combo_implicite'], true );

  if( sizeof( $combos ) ) {
    $log->log( "COMBOS: " . json_encode( $combos ) );
    // seleziona l'attivita
    $arSelect = $Utils->dbSelect( [
      "log" => true,
      "select" => ["*"],
      "from" => "ssm.ssm_registrazioni_combo",
      "where" => [
        [
        "field" => "id",
        "operator" => "IN",
        "value" => $combos,
        "operatorAfter" => "AND"
        ],
        [
        "field" => "idtipo",
        "operator" => "=",
        "value" => 2,
        "operatorAfter" => "AND"
        ],
        [
        "field" => "idstatus",
        "operator" => "=",
        "value" => 1,
        ]
      ]
    ]);

    $items['implicite'] = array();
    foreach( $arSelect['data'] as $k => $v ) {
      $where = sprintf( "ci.idcombo='%s' AND co.idtipo=2", $v['id'] );
      $ar = array( "table" => "ssm.ssm_registrazioni_combo_items ci LEFT JOIN ssm.ssm_registrazioni_combo co ON co.id=ci.idcombo", "value" => "ci.id", "text" => "ci.nome", "order" => "ci.nome", "where" => $where );
      $items['implicite'][] = array( "nome" => $v['nome'], "id" => "imp_" . $v['id'], "opzioni" => $Utils->_combo_list( $ar, true, "" ) );
    }
  }

  if( !$items['esplicite'] )
    $items['esplicite'] = [];
  if( !$items['implicite'] )
    $items['implicite'] = [];

  $response->getBody()->write( json_encode( $items ) );
    return $response
      ->withStatus(200)
      ->withHeader('Content-Type', 'application/json');

});


?>
