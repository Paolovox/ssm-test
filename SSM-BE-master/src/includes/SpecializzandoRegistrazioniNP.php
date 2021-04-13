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




$app->group('/specializzando_registrazioni_np', function (RouteCollectorProxy $groupSpecRegistrazioniNP) use ($auth) {

  $data = [
    "log" => true,
    "table" => "ssm_registrazioni_np",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create"
  ];

  $crud = new CRUD( $data );

  // list
  $groupSpecRegistrazioniNP->get('', function (Request $request, Response $response, $args) use ($auth, $crud) {
    $log = new Logger();
    $Utils = new Utils();

    $user = $request->getAttribute('user');
    $log->log( "utente: " . json_encode( $user ) );

    $p = $request->getQueryParams();

    $log->log( "get - " . $args['idscuola_specializzazione'] . " - " . $args['id'] );
    $data = [
      "table" => "ssm_registrazioni_np",
      "id" => "ssm_registrazioni_np.id",
      "sort" => "ssm_registrazioni_np.data_registrazione",
      "order" => "desc",
      "status_field" => "idstatus",
      "update_field" => "date_update",
      "create_field" => "date_create",
      "list_fields" => [ "ssm_registrazioni_np.id",
        "date_format(ssm_registrazioni_np.data_registrazione,'%d-%m-%Y') as data_registrazione_text",
        "ssm_registrazioni_np.date_create",
        "at.nome_attivita as attivita_text",
        "conferma_stato",
        "case conferma_stato
          when 0 then 'Da confermare'
          when 1 then 'Confermata'
        end as conferma_stato_text",
        // "case conferma_stato
        //   when 1 then 'Da confermare'
        //   when 2 then 'Confermato'
        //   when 3 then 'Scartato'
        //   end as conferma_stato_tutor_text",
        "case conferma_stato
          when 0 then '#cc0000'
          when 1 then '#032b6b'
          end as button_color"
      ],
      "list_join" => [
        [
            "ssm.ssm_scuole_attivita_np at",
            " at.id=ssm_registrazioni_np.idattivita "
        ],
        [
          "ssm.ssm_scuole_pds_insegnamenti si",
          " si.id=ssm_registrazioni_np.idinsegnamento "
        ]
      ]
    ];

    if( $p['srt'] == "data_registrazione_text" )
      $p['srt'] = "data_registrazione";

    $p['_ssm_registrazioni_np.idstatus'] = "1";

    if( $p['s'] != "" ) {
      $p['search'] = array(
        "field" => "at.nome_attivita",
        "operator" => " LIKE ",
        "value" => "%" . $p['s'] . "%"
      );
    }

    // verifica che l'utente sia un docente
    // $ruoli = json_decode( $user['ruoli'], true );
    // $bDoc = false;
    // foreach( $ruoli as $k => $v ) {
    //   if( $k == 'idruolo' && $v == 10 ) {
    //     $bDoc = true;
    //   break;
    //   }
    // }

    // if( $user['idruolo'] == 5 ) {
    //   $res = registrazioni_docente_get($user['id'], $p);
    // } else {
    if ($user['idruolo'] != 8) {
      $p['_ssm_registrazioni_np.idutente'] = $p['idspecializzando'];
    } else {
      $p['_ssm_registrazioni_np.idutente'] = $user['id'];
    }

    if ($p['conferma_stato'] != '') {
      $p['_ssm_registrazioni_np.conferma_stato'] = $p['conferma_stato'];
    }
    if ($p['idattivita']) {
      $p['_ssm_registrazioni_np.idattivita'] = $p['idattivita'];
    }
    if ($p['data_attivita']) {
      $p['_ssm_registrazioni_np.data_registrazione'] = $p['data_attivita'];
    }

    $p['_ssm_registrazioni_np.idscuola_specializzazione'] = $user['idscuola'];

    $crudRegistrazioni = new CRUD( $data );

    $res = $crudRegistrazioni->record_list( $p );
    // }

    if( !$res )
      $res = [];

    $res['attivita_list'] = getAttivitaNPDistinct($p['_ssm_registrazioni_np.idutente']);
    $res['status_list'] = getStatusNPDistinct($p['_ssm_registrazioni_np.idutente']);

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });

  function getStatusNPDistinct($idSpecializzando)
  {
    $db = new dataBase();
    $log = new Logger();
    $sql = sprintf("SELECT DISTINCT conferma_stato as id, rs.stato as text
      FROM ssm_registrazioni_np sr
      LEFT JOIN ssm.ssm_registrazioni_stato_np rs ON rs.id=sr.conferma_stato
      WHERE sr.idutente='%s' AND conferma_stato is not null
      AND sr.idstatus=1", $db->real_escape_string($idSpecializzando));
    $db->query($sql);
    while ($row = $db->fetchassoc()) {
        $ar[] = $row;
    }
    return $ar;
  }

  function getAttivitaNPDistinct($idSpecializzando)
  {
    $log = new Logger();
    $db = new dataBase();
    $sql = sprintf("SELECT DISTINCT idattivita as id, san.nome_attivita as text
      FROM ssm_registrazioni_np sr
      LEFT JOIN ssm.ssm_scuole_attivita_np san ON san.id=sr.idattivita
      WHERE sr.idutente='%s'
      AND sr.idstatus=1", $db->real_escape_string($idSpecializzando));
    $log->log("SQL: " . $sql);
    $db->query($sql);
    while ($row = $db->fetchassoc()) {
        $ar[] = $row;
    }
    return $ar;
  }

  $groupSpecRegistrazioniNP->get('/settori_scientifici', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $user = $request->getAttribute('user');
    $specializzando = specializzando_get($user['id']);
    $idcoorte = $specializzando['idcoorte'];
    $idscuola = $user['idscuola'];
    $anno_scuola = $specializzando['anno_scuola'];

    $sql = sprintf( "SELECT pds.id, concat( ss.nome, ' - ', sa.nome ) as nome
      FROM ssm.ssm_pds pds
      LEFT JOIN ssm.ssm_settori_scientifici ss ON ss.id=pds.idsettore_scientifico AND ss.idstatus=1
      LEFT JOIN ssm.ssm_pds_ambiti_disciplinari sa ON sa.id=pds.idambito_disciplinare AND sa.idstatus=1
     WHERE idcoorte='%s'
      AND anno=%d
      AND idtipologia_attivita IN (2,3)
      AND pds.idstatus=1
      ORDER BY ss.nome",
      $db->real_escape_string( $idcoorte ),
      $anno_scuola );
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $ar[] = $rec;
    }

    $log->log( "SETTORI SCIENTIFICI: " . $sql );

    $response->getBody()->write(json_encode($ar));
    return $response;

  });



  $groupSpecRegistrazioniNP->post('/insegnamenti', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $p = json_decode($request->getBody(), true);
    $arPds = array();
    foreach ($p['idpds'] as $v) {
      $arPds[] = $db->real_escape_string( $v );
    }

    // TODO: SQL_INJECTION_TEST
    $sql = sprintf( "SELECT ins.id, ins.nome, concat( ut.nome, ' ', ut.cognome ) as nome_docente
      FROM ssm.ssm_scuole_pds_insegnamenti ins
      LEFT JOIN ssm_utenti ut ON ut.id=ins.iddocente AND ut.idstatus=1
     WHERE idpiano_studi IN ('%s')
      AND ins.idstatus=1
      ORDER BY ins.nome",
      implode( "','", $arPds ) );
    $db->query( $sql );
    $ar = [];
    while( $rec = $db->fetchassoc() ) {
      $ar[] = $rec;
    }

    $log->log( "INSEGNAMENTI: " . $sql );

    $response->getBody()->write(json_encode($ar));
    return $response;

  });




  $groupSpecRegistrazioniNP->get('/attivita_np', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $user = $request->getAttribute('user');
    $idscuola = $user['idscuola'];

    $sql = sprintf( "SELECT id, nome_attivita
      FROM ssm.ssm_scuole_attivita_np atnp
     WHERE idscuola_specializzazione='%s'
      AND atnp.idstatus=1
      ORDER BY atnp.nome_attivita", $db->real_escape_string( $idscuola ) );
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $ar[] = $rec;
    }

    $log->log( "ATTIVITA NP LIST: " . $sql );

    $response->getBody()->write(json_encode($ar));
    return $response;
  });




  $groupSpecRegistrazioniNP->get('/attivita_np/{idattivita}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $user = $request->getAttribute('user');
    $idscuola = $user['idscuola'];

    // verifica se calendar è attivo
    $sql = sprintf( "SELECT calendar
    FROM ssm.ssm_scuole_attivita_np
      WHERE idattivita='%s'",
    $db->real_escape_string( $args['idattivita'] ) );
    $db->query( $sql );
    $rec = $db->fetchassoc();
    if( $rec['calendar'] == 1 )
      $ar['calendar'] = true;


    $sql = sprintf( "SELECT id, nome_campo, idtipo_campo
      FROM ssm.ssm_scuole_attivita_np_dati
     WHERE idattivita='%s'
      AND idstatus=1
      ORDER BY idtipo_campo", $db->real_escape_string( $args['idattivita'] ) );
    $db->query( $sql );
    $ar['list'] = [];
    while( $rec = $db->fetchassoc() ) {
      $ar['list'][] = $rec;
    }

    $log->log( "ATTIVITA NP DATI LIST: " . $sql );

    $response->getBody()->write( json_encode($ar, JSON_NUMERIC_CHECK ) );
    return $response;
  });





  // salva registrazione NP
  $groupSpecRegistrazioniNP->put('/np', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();
    $Utils = new Utils();
    $uuid = new UUID();

    $p = json_decode($request->getBody(), true);
    $log->log( "REGISTRAZIONE NP: " . json_encode( $p ) );

    $user = $request->getAttribute('user');

    $add = array(
      "id" => $uuid->v4(),
      "idutente" => $user['id'],
      "idscuola_specializzazione" => $user['idscuola'],
      "idattivita" => $p['idattivita'],
      "idpds" => json_encode( $p['idpds'] ),
      "idinsegnamento" => json_encode( $p['idinsegnamento'] ),
      "dati_aggiuntivi" => json_encode( $p['dati_aggiuntivi'] ),
      "data_registrazione" => substr( $p['data_registrazione'], 0, 10 ),
      "attach" => $p['attach'] != "" ? json_encode( $p['attach'] ) : "[]",
      "idstatus" => 1,
      "date_create" => "now()",
      "date_update" => "now()"
    );

    $log->log( "REGISTRAZIONE NP ADD: " . json_encode( $p ) );

    $ret = $Utils->dbSql( true, "ssm_registrazioni_np", $add, "", "" );

    if( $ret['success'] == 1 ) {
      $response->getBody()->write( json_encode( $ret ) );
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

  $groupSpecRegistrazioniNP->post('/np/{idattivita}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();
    $Utils = new Utils();
    $uuid = new UUID();

    $p = json_decode($request->getBody(), true);
    $log->log( "REGISTRAZIONE NP: " . json_encode( $p ) );

    $user = $request->getAttribute('user');

    $add = array(
      "idattivita" => $p['idattivita'],
      "idpds" => json_encode( $p['idpds'] ),
      "attach" => $p['attach'] != "" ? json_encode( $p['attach'] ) : "[]",
      "idinsegnamento" => json_encode( $p['idinsegnamento'] ),
      "dati_aggiuntivi" => json_encode( $p['dati_aggiuntivi'] ),
      "data_registrazione" => substr( $p['data_registrazione'], 0, 10 ),
      "date_update" => "now()"
    );

    $ret = $Utils->dbSql( false, "ssm_registrazioni_np", $add, "id", $args['idattivita'] );

    if( $ret['success'] == 1 ) {
      $response->getBody()->write( json_encode( $ret ) );
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





  // get
  $groupSpecRegistrazioniNP->get('/{id}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();


    $user = $request->getAttribute('user');
    $log->log( "user: " . json_encode( $user ) );
    $p = $request->getQueryParams();

    $specializzando = specializzando_get($p['idspecializzando'] ? $p['idspecializzando'] : $user['id']);
    $idcoorte = $specializzando['idcoorte'];
    $idscuola = $user['idscuola'];
    $anno_scuola = $specializzando['anno_scuola'];


    $res = $crud->record_get( $args['id'] )['data'][0];
    $log->log($args['id']. " " . json_encode($res));

    if( $args['id'] != 0 ) {
      $log->log("dentro");

    }
    if( !isset($res['id']) ) {
      $res = [];
    } else {
      $res['dati_aggiuntivi'] = json_decode( $res['dati_aggiuntivi'], true );
      $res['idpds'] = json_decode( $res['idpds'], true );
      $res['idinsegnamento'] = json_decode( $res['idinsegnamento'], true );
      $res['attach'] = $res['attach'] == "" ? [] : json_decode( $res['attach'], true );
    }
    $log->log("b: " . json_encode($res));

    $sql = sprintf( "SELECT pds.id, concat( ss.nome, ' - ', sa.nome, ' (', ta.nome_tipologia, ')'  ) as nome
      FROM ssm.ssm_pds pds
      LEFT JOIN ssm.ssm_settori_scientifici ss ON ss.id=pds.idsettore_scientifico AND ss.idstatus=1
      LEFT JOIN ssm.ssm_pds_ambiti_disciplinari sa ON sa.id=pds.idambito_disciplinare AND sa.idstatus=1
      LEFT JOIN ssm.ssm_tipologie_attivita ta ON ta.id=pds.idtipologia_attivita
     WHERE idcoorte='%s'
      AND anno=%d
      AND idtipologia_attivita IN (2,3)
      AND pds.idstatus=1
      ORDER BY ss.nome",
      $db->real_escape_string( $idcoorte ),
      $anno_scuola );
    $log->log($sql);
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $res['settori_scientifici_list'][] = $rec;
    }


    $sql = sprintf( "SELECT id, nome_attivita
      FROM ssm.ssm_scuole_attivita_np atnp
     WHERE idscuola_specializzazione='%s'
      AND atnp.idstatus=1
      ORDER BY atnp.nome_attivita", $db->real_escape_string( $idscuola ) );
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $res['attivita_list'][] = $rec;
    }



    // insegnamenti
    $sql = sprintf( "SELECT ins.id, ins.nome, concat( ut.nome, ' ', ut.cognome ) as nome_docente
      FROM ssm.ssm_scuole_pds_insegnamenti ins
      LEFT JOIN ssm_utenti ut ON ut.id=ins.iddocente AND ut.idstatus=1
     WHERE idpiano_studi IN ('%s')
      AND ins.idstatus=1
      ORDER BY ins.nome",
      implode( "','", json_decode( $res['idpds'], true ) ) );
    $db->query( $sql );
    $res['insegnamenti_list'] = [];
    while( $rec = $db->fetchassoc() ) {
      $res['insegnamenti_list'][] = $rec;
    }


    // dati aggiuntivi
    $sql = sprintf( "SELECT id, nome_campo, idtipo_campo
      FROM ssm.ssm_scuole_attivita_np_dati
     WHERE idattivita='%s'
      AND idstatus=1
      ORDER BY idtipo_campo", $res['idattivita'] );
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $res['dati_aggiuntivi_list'][] = $rec;
    }

    $response->getBody()->write( json_encode( $res, JSON_NUMERIC_CHECK ) );
    return $response;
  });

  // aggiorna lo stato della registrazione
  $groupSpecRegistrazioniNP->post('/set_status/all/{conferma_stato}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $db = new dataBase();

    $user = $request->getAttribute('user');
    $p = $request->getQueryParams();

    if ($user['idruolo'] == 5 && $args['conferma_stato'] == 1) {
      $sql = sprintf("UPDATE ssm_registrazioni_np SET conferma_stato=1 WHERE idutente='%s' AND conferma_stato = 0", $db->real_escape_string( $p['idSpecializzando'] ) );
      $log->log($sql);
      $res['success'] = $db->query($sql);
    }

    if ($res['success'] == 1) {
        $response->getBody()->write(json_encode($res));
        return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write("Errore aggiornamento");
        return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }
  });

  // aggiorna lo stato della registrazione
  $groupSpecRegistrazioniNP->post('/set_status/{id}/{idstatus}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $Utils = new Utils();

    $update = array(
      "conferma_stato" => $args['idstatus'],
      "conferma_data" => "now()"
    );

    $res = $Utils->dbSql( false, "ssm_registrazioni_np", $update, "id", $args['id'] );
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



  $groupSpecRegistrazioniNP->delete('/{idattivita}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $log->log( "DELETE registrazioni_attivita_np - " . $args['idattivita'] );

    $res = $crud->record_delete( $args['idattivita'] );
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





})->add( $authMW );


function registrazioni_docente_get( $idScuola, $paging )  {

  $db = new dataBase();
  $log = new Logger();

  $sql = sprintf("SELECT
        date_format(srn.data_registrazione,'%%d-%%m-%%Y') as data_registrazione_text,
        srn.date_create,
        at.nome_attivita as attivita_text,
        conferma_stato,
        case conferma_stato
          when 0 then 'Da confermare'
          when 1 then 'Confermata'
        end as conferma_stato_text,
        case conferma_stato
          when 0 then '#cc0000'
          when 1 then '#032b6b'
        end as button_color
        FROM
          ssm_registrazioni_np srn
        LEFT JOIN ssm.ssm_scuole_attivita_np at ON at.id=srn.idattivita
        WHERE idscuola_specializzazione = '%s' AND srn.idstatus=1 AND si.idstatus=1 AND conferma_stato > 0
        ORDER BY %s %s
        LIMIT %d, %d", $db->real_escape_string( $idScuola ), $db->real_escape_string( $paging['srt'] ), $db->real_escape_string( $paging['o'] ), ($paging['p'] - 1) * $paging['c'], $paging['c'] );
  $log->log($sql);
  $db->query($sql);

  $ar = array();
  while( $rec = $db->fetchassoc() ) {
    $ar[] = $rec;
  }
  $db->query("SELECT FOUND_ROWS()");
  $ret['total'] = intval($db->fetcharray()[0]);
  $ret['count'] = sizeof($ar);
  $ret['rows'] = $ar;
  return $ret;

}

function registrazioni_docente_get_old( $idDoc, $paging )  {

  $db = new dataBase();
  $log = new Logger();

  $sql = sprintf("SELECT
        date_format(srn.data_registrazione,'%%d-%%m-%%Y') as data_registrazione_text,
        srn.date_create,
        at.nome_attivita as attivita_text,
        conferma_stato,
        case conferma_stato
          when 0 then 'Da inviare'
          when 1 then 'Inviato'
          when 2 then 'Confermato'
          when 3 then 'Scartato'
        end as conferma_stato_text,
        case conferma_stato
          when 1 then 'Da confermare'
          when 2 then 'Confermato'
          when 3 then 'Scartato'
          end as conferma_stato_tutor_text,
        case conferma_stato
          when 0 then '#cc0000'
          when 1 then '#032b6b'
          when 2 then '#258e7a'
          when 3 then '#6e6e6e'
          end as button_color
        FROM
          ssm_registrazioni_np srn
        LEFT JOIN ssm.ssm_scuole_pds_insegnamenti si ON JSON_CONTAINS(srn.idinsegnamento, JSON_QUOTE(si.id), '$')
        LEFT JOIN ssm.ssm_scuole_attivita_np at ON at.id=srn.idattivita
        WHERE iddocente = '%s' AND srn.idstatus=1 AND si.idstatus=1 AND conferma_stato > 0
        ORDER BY %s %s
        LIMIT %d, %d", $db->real_escape_string( $idDoc ), $db->real_escape_string( $paging['srt'] ), $db->real_escape_string( $paging['o'] ), ($paging['p'] - 1) * $paging['c'], $paging['c'] );
  $log->log($sql);
  $db->query($sql);

  $ar = array();
  while( $rec = $db->fetchassoc() ) {
    $ar[] = $rec;
  }
  $db->query("SELECT FOUND_ROWS()");
  $ret['total'] = intval($db->fetcharray()[0]);
  $ret['count'] = sizeof($ar);
  $ret['rows'] = $ar;
  return $ret;

}

?>
