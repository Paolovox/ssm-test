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




$app->group('/registrazioni_attivita_np_calendario', function (RouteCollectorProxy $groupRegistrazioniNPCalendario) use ($auth) {

  $data = [
    "log" => true,
    "table" => "ssm.ssm_scuole_registrazioni_np_calendario",
    "id" => "id",
    "sort" => "nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_scuole_registrazioni_np_calendario.*",
      "date_format(data_lezione,'%d-%m-%Y') as data_lezione_text",
      "ins.nome as insegnamento_text"
      ],
    "list_join" => [
      [
          "ssm.ssm_scuole_pds_insegnamenti ins",
          " ins.id=ssm.ssm_scuole_registrazioni_np_calendario.idinsegnamento "
      ],
    ]
  ];

  $crud = new CRUD( $data );


  // list
  $groupRegistrazioniNPCalendario->get('/{idscuola_specializzazione}/{idattivita}', function (Request $request, Response $response, $args) use ($auth, $crud) {
    $log = new Logger();
    $Utils = new Utils();

    $p = $request->getQueryParams();
    $p['_ssm.ssm_scuole_registrazioni_np_calendario.idscuola_specializzazione'] = $args['idscuola_specializzazione'];
    $p['_idattivita'] = $args['idattivita'];
    $p['_ssm.ssm_scuole_registrazioni_np_calendario.idstatus'] = 1;
    $res = $crud->record_list( $p );

    if( !$res )
      $res = [];

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });




  $groupRegistrazioniNPCalendario->get('/settori_scientifici/{idscuola}/{idcoorte}/{anno_scuola}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $idscuola = $args['idscuola'];
    $idcoorte = $args['idcoorte'];
    $anno_scuola = $args['anno_scuola'];

    $sql = sprintf( "SELECT pds.id, concat( ss.nome, ' - ', sa.nome ) as nome
      FROM ssm.ssm_pds pds
      LEFT JOIN ssm.ssm_settori_scientifici ss ON ss.id=pds.idsettore_scientifico AND ss.idstatus=1
      LEFT JOIN ssm.ssm_pds_ambiti_disciplinari sa ON sa.id=pds.idambito_disciplinare AND sa.idstatus=1
     WHERE idcoorte='%s'
      AND anno='%s'
      AND idtipologia_attivita IN (2,3)
      AND pds.idstatus=1
      ORDER BY ss.nome",
      $db->real_escape_string( $idcoorte ),
      $db->real_escape_string( $anno_scuola ) );
    $db->query( $sql );

    $ar = [];
    while( $rec = $db->fetchassoc() ) {
      $ar[] = $rec;
    }

    $log->log( "SETTORI SCIENTIFICI: " . $sql );

    $response->getBody()->write(json_encode($ar));
    return $response;

  });



  $groupRegistrazioniNPCalendario->post('/insegnamenti', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $p = json_decode($request->getBody(), true);

    $sql = sprintf( "SELECT ins.id, ins.nome, concat( ut.nome, ' ', ut.cognome ) as nome_docente
      FROM ssm.ssm_scuole_pds_insegnamenti ins
      LEFT JOIN ssm_utenti ut ON ut.id=ins.iddocente AND ut.idstatus=1
     WHERE idpiano_studi='%s'
      AND ins.idstatus=1
      ORDER BY ins.nome",
      $db->real_escape_string( $p['idpds'] ) );
    $db->query( $sql );
    $ar = [];
    while( $rec = $db->fetchassoc() ) {
      $ar[] = $rec;
    }

    $log->log( "INSEGNAMENTI: " . $sql );

    $response->getBody()->write(json_encode($ar));
    return $response;

  });




  $groupRegistrazioniNPCalendario->get('/attivita_np', function (Request $request, Response $response, $args) use ($crud, $authMW) {
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




  $groupRegistrazioniNPCalendario->get('/attivita_np/{idattivita}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
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





  // salva attivita calendario NP
  $groupRegistrazioniNPCalendario->put('/{idscuola_specializzazione}/{idattivita}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();
    $Utils = new Utils();
    $uuid = new UUID();

    $p = json_decode($request->getBody(), true);
    $log->log( "REGISTRAZIONE CALENDARIO NP: " . json_encode( $p ) );

    $user = $request->getAttribute('user');

    $add = array(
      //"id" => $uuid->v4(),
      "idscuola_specializzazione" => $args['idscuola_specializzazione'],
      "idattivita" => $args['idattivita'],
      "idpds" =>$p['idpds'],
      "anno_scuola" => $p['anno_scuola'],
      "idcoorte" => $p['idcoorte'],
      "idinsegnamento" => $p['idinsegnamento'],
      "data_lezione" => substr( $p['data_lezione'], 0, 10 ),
      "idstatus" => 1,
      "date_create" => "now()",
      "date_update" => "now()"
    );


    $res = $crud->record_new( $add );
    $log->log( "SALVA: " . json_encode( $add ) . " - " . json_encode( $ret ) );

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


  // modifica attivita calendario NP
  $groupRegistrazioniNPCalendario->post('/{idscuola_specializzazione}/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();
    $Utils = new Utils();
    $uuid = new UUID();

    $p = json_decode($request->getBody(), true);
    $log->log( "REGISTRAZIONE CALENDARIO NP: " . json_encode( $p ) );

    $user = $request->getAttribute('user');

    $edit = array(
      "idscuola_specializzazione" => $args['idscuola_specializzazione'],
      "idattivita" => $args['idattivita'],
      "idpds" =>$p['idpds'],
      "anno_scuola" => $p['anno_scuola'],
      "idcoorte" => $p['idcoorte'],
      "idinsegnamento" => $p['idinsegnamento'],
      "data_lezione" => substr( $p['data_lezione'], 0, 10 ),
      "date_update" => "now()"
    );


    $res = $crud->record_update( $args['id'], $edit );
    $log->log( "MODIFICA: " . json_encode( $edit ) . " - " . json_encode( $ret ) );

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


  // get
  $groupRegistrazioniNPCalendario->get('/{idscuola_specializzazione}/{idattivita}/{id}', function (Request $request, Response $response, $args) use ($crud, $authMW) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $res = $crud->record_get( $args['id'] )['data'][0];
    if( !$res ) {
      $res = [];
    }

    // coorti
    $sql = sprintf( "SELECT id, nome
        FROM ssm.ssm_pds_coorti
        WHERE idscuola_specializzazione='%s'
          AND idstatus=1",
      $db->real_escape_string( $args['idscuola_specializzazione'] ) );
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $res['coorti_list'][] = $rec;
    }


    $sql = sprintf( "SELECT pds.id, concat( ss.nome, ' - ', sa.nome ) as nome
      FROM ssm.ssm_pds pds
      LEFT JOIN ssm.ssm_settori_scientifici ss ON ss.id=pds.idsettore_scientifico AND ss.idstatus=1
      LEFT JOIN ssm.ssm_pds_ambiti_disciplinari sa ON sa.id=pds.idambito_disciplinare AND sa.idstatus=1
     WHERE idcoorte='%s'
      AND anno='%s'
      AND idtipologia_attivita IN (2,3)
      AND pds.idstatus=1
      ORDER BY ss.nome",
      $db->real_escape_string( $res['idcoorte'] ),
      $db->real_escape_string($res['anno_scuola'] ) );
    $db->query( $sql );
    $res['settori_scientifici_list'] = [];
    while( $rec = $db->fetchassoc() ) {
      $res['settori_scientifici_list'][] = $rec;
    }


    // insegnamenti
    $sql = sprintf( "SELECT ins.id, ins.nome, concat( ut.nome, ' ', ut.cognome ) as nome_docente
      FROM ssm.ssm_scuole_pds_insegnamenti ins
      LEFT JOIN ssm_utenti ut ON ut.id=ins.iddocente AND ut.idstatus=1
     WHERE idpiano_studi='%s'
      AND ins.idstatus=1
      ORDER BY ins.nome",
      $db->real_escape_string( $res['idpds'] ) );
    $db->query( $sql );
    $res['insegnamenti_list'] = [];
    while( $rec = $db->fetchassoc() ) {
      $res['insegnamenti_list'][] = $rec;
    }


    $response->getBody()->write( json_encode( $res, JSON_NUMERIC_CHECK ) );
    return $response;
  });



  // delete
  $groupRegistrazioniNPCalendario->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();
    $log->log( "Elimina ssm.ssm_scuole_registrazioni_np_calendario " . $args['id'] );
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


})->add( $authMW );


?>
