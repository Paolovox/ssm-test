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




$app->group('/turni', function (RouteCollectorProxy $groupTurni) use ($auth) {

  $data = [
    "log" => true,
    "table" => "ssm_turni",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm_turni.id", "concat(su_sp.cognome,' ',su_sp.nome) as sp_nome",
      "concat(su_tu.cognome, ' ', su_tu.nome) as tu_nome",
      "date_format(data_inizio,'%d-%m-%Y') as data_inizio_text",
      "date_format(data_fine,'%d-%m-%Y') as data_fine_text",
      "concat(uo.nome, ' - ', pr.nome) as nome_uo" ],
    "list_join" => [
      [
          "ssm_utenti su_sp",
          " su_sp.id=ssm_turni.idspecializzando "
      ],
      [
          "ssm_utenti su_tu",
          " su_tu.id=ssm_turni.idtutor "
      ],
      [
          "ssm.ssm_scuole_unita suo",
          " suo.id=ssm_turni.idunita "
      ],
      [
          "ssm.ssm_unita_operative uo",
          " uo.id=suo.idunita "
      ],
      [
          "ssm.ssm_presidi pr",
          " pr.id=uo.idpresidio "
      ]
    ]
  ];

  $crud = new CRUD( $data );

  // list
  $groupTurni->get('/{idscuola}', function (Request $request, Response $response, $args) use ($auth, $crud) {
    $p = $request->getQueryParams();
    $p['_ssm_turni.idstatus'] = "1";
    $p['_ssm_turni.idscuola'] = $args['idscuola'];

    if( $p['s'] != "" ) {
      $p['multi_search'] = array(
        [
          "field" => "su_sp.cognome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%",
          "operatorAfter" => " OR "
        ],
        [
          "field" => "su_sp.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%",
          "operatorAfter" => " OR "
        ],
        [
          "field" => "su_tu.cognome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%",
          "operatorAfter" => " OR "
        ],
        [
          "field" => "su_tu.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%",
          "operatorAfter" => " OR "
        ],
        [
          "field" => "uo.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%",
          "operatorAfter" => " OR "
        ],
        [
          "field" => "pr.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        ],
        [
          "custom" => "ssm_turni.idspecializzando is not null"
        ]
      );
    } else {
      $p['customWhere'] = [
        "custom" => " ssm_turni.idspecializzando is not null"
      ];
    }

    if ($p['srt'] == 'data_inizio_text')  {
      $p['srt'] = 'data_inizio';
    }
    if ($p['srt'] == 'data_fine_text')  {
      $p['srt'] = 'data_fine';
    }

    $res = $crud->record_list( $p );
    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  // get
  $groupTurni->get('/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $res = $crud->record_get( $args['id'] )['data'][0];
      if( $res == "" )
        $res = [];


      /*
      $ar = array( "table" => "ssm_utenti", "value" => "id", "text" => "concat(cognome,' ',nome)", "order" => "cognome,nome" );
      $res['specializzandi_list'] = $Utils->_combo_list( $ar, true, "" );
      */
      $res['specializzandi_list'] = _utente_get_by_ruolo( $args['idscuola'], 8 );


      /*
      $ar = array( "table" => "ssm_utenti", "value" => "id", "text" => "concat(cognome,' ',nome)", "order" => "cognome,nome" );
      $res['tutor_list'] = $Utils->_combo_list( $ar, true, "" );
      */

      $res['tutor_list'] = [];
      $sql = sprintf( "SELECT idunita FROM ssm.ssm_scuole_unita WHERE idscuola_specializzazione='%s' AND idstatus=1", $db->real_escape_string( $args['idscuola'] ) );
      $db->query( $sql );
      $log->log( "UNITA_SCUOLA " . $sql );
      while( $rec = $db->fetchassoc() ) {
        $arunita[] = $rec['idunita'];
      }
      $res['tutor_list'] = _tutor_list( $arunita );


      $sql = sprintf( "SELECT su.id, concat(uo.nome, ' (', ut.nome, ') - ' , pr.nome, ' - Azienda: ', a.nome) as text
        FROM ssm.ssm_scuole_unita su
        LEFT JOIN ssm.ssm_unita_operative uo ON uo.id=su.idunita
        LEFT JOIN ssm.ssm_unita_tipologie ut ON ut.id=su.idtipologia_sede
        LEFT JOIN ssm.ssm_presidi pr ON pr.id=uo.idpresidio
        LEFT JOIN ssm.ssm_aziende a ON a.id=pr.idazienda
        WHERE su.idscuola_specializzazione='%s' AND su.idstatus=1 AND uo.idstatus=1 AND pr.idstatus=1
        ORDER BY uo.nome, pr.nome",
        $db->real_escape_string( $args['idscuola'] ) );
      $db->query( $sql );
      $log->log( "UNITA LIST: " . $sql );

      $res['unita_list'] = [];
      while( $rec = $db->fetchassoc() ) {
        $res['unita_list'][] = $rec;
      }

        /*
      $ar = array(
        "table" => "ssm.ssm_unita_operative uo LEFT JOIN ssm.ssm_presidi pr ON pr.id=uo.idpresidio",
        "value" => "uo.id",
        "text" => "concat(uo.nome, ' - ' , pr.nome)",
        "order" => "uo.nome, pr.nome",
        "where" => "uo.idstatus=1 AND pr.idstatus=1" );
      $res['unita_list'] = $Utils->_combo_list( $ar, true, "" );
      */


      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupTurni->put('/{idscuola}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $p['data_inizio'] = substr( $p['data_inizio'], 0, 10 );
    $p['data_fine'] = substr( $p['data_fine'], 0, 10 );
    $p['idscuola'] = $args['idscuola'];

    $log->log( "/turni/idscuola - " . json_encode( $p ) );

    $retValidate = validate( "turni", $p );
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
  $groupTurni->post('/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "update turni - " . $args['id'] . " - " . json_encode( $p ) );

    $p['data_inizio'] = substr( $p['data_inizio'], 0, 10 );
    $p['data_fine'] = substr( $p['data_fine'], 0, 10 );

    $retValidate = validate( "turni", $p );
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
  $groupTurni->delete('/{idscuola}/{id}', function (Request $request, Response $response, $args) use ($crud) {
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
